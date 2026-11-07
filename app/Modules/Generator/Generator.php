<?php
declare(strict_types=1);

namespace App\Modules\Generator;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\FileSystem;
use App\Core\Logger;
use App\Modules\Websites\SiteService;
use RuntimeException;
use Throwable;

/**
 * Staged, resumable website generation (no cron: the browser calls step() repeatedly).
 *
 * Every step is idempotent and fast. Steps 1–7 only touch a private staging folder, so a failure
 * there never affects an existing website. Step 8 swaps the new code in (uploads/ is never touched)
 * and keeps the previous code as a backup; if the website checks in step 9 fail, the backup is restored.
 */
final class Generator
{
    public const STEPS = [
        'validate' => 'Validate configuration',
        'prepare' => 'Create staging folder',
        'copy_runtime' => 'Copy website engine',
        'copy_theme' => 'Copy template files',
        'copy_assets' => 'Copy fonts and libraries',
        'migrate_db' => 'Check database tables',
        'write_config' => 'Write configuration files',
        'validate_files' => 'Verify generated files',
        'swap' => 'Publish to website folder',
        'smoke_test' => 'Run website checks',
        'finish' => 'Mark website as Generated',
    ];

    /** Top-level entries in the site folder that belong to the generated code (everything except uploads/ and storage/). */
    private const CODE_ENTRIES = ['index.php', '.htaccess', 'robots.txt', 'core', 'vendor', 'theme', 'assets', 'config', 'VERSION', 'generation.json'];

    private array $run;
    private SiteService $site;
    private array $log = [];

    private function __construct(array $run)
    {
        $this->run = $run;
        $site = SiteService::load((int) $run['website_id']);
        if (!$site) {
            throw new RuntimeException('Website not found.');
        }
        $this->site = $site;
    }

    /** Start a new run. Refuses if another run for this website is still running. */
    public static function start(int $websiteId, string $type = 'generate'): int
    {
        $running = Database::value("SELECT id FROM wb_generation_runs WHERE website_id = ? AND status = 'running'", [$websiteId]);
        if ($running) {
            throw new RuntimeException('A generation is already in progress for this website.');
        }
        $runId = Database::insert('wb_generation_runs', [
            'website_id' => $websiteId, 'type' => $type, 'status' => 'running', 'current_step' => 'validate',
            'triggered_by' => Auth::id(), 'started_at' => now(),
        ]);
        foreach (array_keys(self::STEPS) as $key) {
            Database::insert('wb_generation_steps', ['run_id' => $runId, 'step_key' => $key, 'status' => 'pending']);
        }
        Database::update('wb_websites', ['status' => 'generating', 'updated_at' => now()], $websiteId);
        Activity::log('website.generation_started', 'website', $websiteId, "Run #$runId");
        return $runId;
    }

    public static function load(int $runId): self
    {
        $run = Database::fetch('SELECT * FROM wb_generation_runs WHERE id = ?', [$runId]);
        if (!$run) {
            throw new RuntimeException('Generation run not found.');
        }
        return new self($run);
    }

    /** Execute the next pending step. Returns the run state. */
    public function step(): array
    {
        if ($this->run['status'] !== 'running') {
            return $this->state();
        }
        $step = Database::fetch("SELECT * FROM wb_generation_steps WHERE run_id = ? AND status IN ('pending','running','failed') ORDER BY id LIMIT 1", [$this->run['id']]);
        if (!$step) {
            return $this->state();
        }
        @set_time_limit(120);
        Database::update('wb_generation_steps', ['status' => 'running', 'attempts' => (int) $step['attempts'] + 1, 'started_at' => now(), 'log' => null], (int) $step['id']);
        Database::update('wb_generation_runs', ['current_step' => $step['step_key']], (int) $this->run['id']);
        try {
            $result = $this->{'step' . str_replace('_', '', ucwords($step['step_key'], '_'))}();
            Database::update('wb_generation_steps', ['status' => $result === 'skipped' ? 'skipped' : 'success', 'finished_at' => now(), 'log' => implode("\n", $this->log)], (int) $step['id']);
        } catch (Throwable $e) {
            $this->log[] = 'ERROR: ' . $e->getMessage();
            Logger::error('Generation step failed', ['run' => $this->run['id'], 'step' => $step['step_key'], 'error' => $e->getMessage(), 'file' => $e->getFile() . ':' . $e->getLine()]);
            Database::update('wb_generation_steps', ['status' => 'failed', 'finished_at' => now(), 'log' => implode("\n", $this->log)], (int) $step['id']);
            Database::update('wb_generation_runs', ['status' => 'failed', 'error_message' => self::STEPS[$step['step_key']] . ': ' . $e->getMessage(), 'finished_at' => now()], (int) $this->run['id']);
            Database::update('wb_websites', ['status' => 'failed', 'updated_at' => now()], $this->site->id());
            Activity::log('website.generation_failed', 'website', $this->site->id(), "Run #{$this->run['id']} at " . $step['step_key'] . ': ' . $e->getMessage());
            // Never leave a half-built staging folder behind (unless the swap already happened: restored in smoke_test).
            if (!in_array($step['step_key'], ['swap', 'smoke_test', 'finish'], true)) {
                $this->cleanupStaging();
            }
        }
        $this->run = Database::fetch('SELECT * FROM wb_generation_runs WHERE id = ?', [$this->run['id']]);
        return $this->state();
    }

    /** Retry a failed run from its failed step (steps before it that touch staging are re-run). */
    public function retry(): array
    {
        if ($this->run['status'] !== 'failed') {
            throw new RuntimeException('Only a failed generation can be retried.');
        }
        if (Database::value("SELECT id FROM wb_generation_runs WHERE website_id = ? AND status = 'running'", [$this->site->id()])) {
            throw new RuntimeException('Another generation is running for this website.');
        }
        $failed = Database::fetch("SELECT * FROM wb_generation_steps WHERE run_id = ? AND status = 'failed' ORDER BY id LIMIT 1", [$this->run['id']]);
        // The staging folder is removed on failure, so restart from "prepare" when the failure was before the swap.
        $order = array_keys(self::STEPS);
        $from = $failed ? array_search($failed['step_key'], $order, true) : 0;
        if ($from !== false && $from <= array_search('validate_files', $order, true)) {
            $from = 0;
        }
        foreach (array_slice($order, (int) $from) as $key) {
            Database::query("UPDATE wb_generation_steps SET status = 'pending', log = NULL WHERE run_id = ? AND step_key = ?", [$this->run['id'], $key]);
        }
        Database::update('wb_generation_runs', ['status' => 'running', 'error_message' => null, 'finished_at' => null], (int) $this->run['id']);
        Database::update('wb_websites', ['status' => 'generating', 'updated_at' => now()], $this->site->id());
        Activity::log('website.generation_retried', 'website', $this->site->id(), "Run #{$this->run['id']}");
        $this->run = Database::fetch('SELECT * FROM wb_generation_runs WHERE id = ?', [$this->run['id']]);
        return $this->state();
    }

    public function state(): array
    {
        $steps = Database::fetchAll('SELECT step_key, status, attempts, log, started_at, finished_at FROM wb_generation_steps WHERE run_id = ? ORDER BY id', [$this->run['id']]);
        foreach ($steps as &$s) {
            $s['label'] = self::STEPS[$s['step_key']] ?? $s['step_key'];
        }
        $done = count(array_filter($steps, static fn ($s) => in_array($s['status'], ['success', 'skipped'], true)));
        return [
            'run_id' => (int) $this->run['id'],
            'status' => $this->run['status'],
            'error' => $this->run['error_message'],
            'progress' => (int) round($done / max(1, count($steps)) * 100),
            'steps' => $steps,
            'site_url' => QaAccess::siteUrl($this->site->website()),
        ];
    }

    // ================================================================== steps

    private function stepValidate(): void
    {
        $report = Checklist::run($this->site);
        foreach ($report['warnings'] as $w) {
            $this->log[] = 'Warning: ' . $w;
        }
        if ($report['errors']) {
            throw new RuntimeException(implode(' ', $report['errors']));
        }
        if (!is_dir($this->site->themeDir())) {
            throw new RuntimeException('Template folder is missing.');
        }
        $this->log[] = 'Configuration is complete (' . count($report['warnings']) . ' warning(s)).';
    }

    private function stepPrepare(): void
    {
        $this->cleanupStaging();
        if (!mkdir($this->staging(), 0755, true)) {
            throw new RuntimeException('Could not create the staging folder.');
        }
        $free = @disk_free_space(STORAGE_PATH);
        if ($free !== false && $free < 50 * 1024 * 1024) {
            throw new RuntimeException('Less than 50 MB of disk space is free.');
        }
        $this->log[] = 'Staging folder: ' . substr($this->staging(), strlen(BASE_PATH) + 1);
    }

    private function stepCopyRuntime(): void
    {
        $st = $this->staging();
        $this->fresh("$st/core");
        // Runtime classes, the website admin (core/Admin) and the shared editor partials (core/editor).
        FileSystem::copyDir(RUNTIME_PATH . '/core', "$st/core");
        FileSystem::copyDir(RUNTIME_PATH . '/section-types', "$st/core/section-types");
        FileSystem::copyDir(RUNTIME_PATH . '/schema', "$st/core/schema");
        copy(RUNTIME_PATH . '/skeleton/_private/.htaccess', "$st/core/.htaccess");
        // Bundled PHP libraries (HTML Purifier, PHPMailer): no Composer needed on the hosting.
        $this->fresh("$st/vendor");
        copy(BASE_PATH . '/vendor/autoload.php', "$st/vendor/autoload.php");
        foreach (['ezyang', 'phpmailer'] as $lib) {
            FileSystem::copyDir(BASE_PATH . "/vendor/$lib", "$st/vendor/$lib");
        }
        copy(RUNTIME_PATH . '/skeleton/_private/.htaccess', "$st/vendor/.htaccess");
        foreach (['index.php', '.htaccess', 'robots.txt'] as $f) {
            copy(RUNTIME_PATH . '/skeleton/' . $f, "$st/$f");
        }
        $count = iterator_count(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$st/core", \FilesystemIterator::SKIP_DOTS)));
        $this->log[] = "Copied the runtime and website admin ($count files), section types, schema and PHP libraries (runtime v" . $this->runtimeVersion() . ').';
    }

    private function stepCopyTheme(): void
    {
        $st = $this->staging();
        $this->fresh("$st/theme");
        $src = $this->site->themeDir();
        foreach (['layouts', 'partials', 'sections', 'assets'] as $d) {
            if (is_dir("$src/$d")) {
                FileSystem::copyDir("$src/$d", "$st/theme/$d");
            }
        }
        copy("$src/manifest.json", "$st/theme/manifest.json");
        $this->log[] = 'Copied template ' . $this->site->website()['template_code'] . ' (layouts, partials, ' . count(glob("$st/theme/sections/*.php") ?: []) . ' section views, assets).';
    }

    private function stepCopyAssets(): void
    {
        $st = $this->staging();
        $this->fresh("$st/assets");
        FileSystem::copyDir(RUNTIME_PATH . '/fonts', "$st/assets/fonts");
        FileSystem::copyDir(RUNTIME_PATH . '/assets/lib', "$st/assets/lib");
        FileSystem::copyDir(RUNTIME_PATH . '/assets/pay', "$st/assets/pay");
        // Website admin scripts and styles (the same editor scripts the builder uses).
        $adm = "$st/assets/admin";
        FileSystem::copyDir(RUNTIME_PATH . '/admin-assets', $adm);
        foreach (['jquery', 'sortablejs', 'tinymce'] as $lib) {
            FileSystem::copyDir(PUBLIC_PATH . "/assets/vendor/$lib", "$adm/vendor/$lib");
        }
        copy(PUBLIC_PATH . '/assets/css/app.css', "$adm/css/app.css");
        copy(PUBLIC_PATH . '/assets/js/app.js', "$adm/js/app.js");
        copy(PUBLIC_PATH . '/assets/js/pages/website-workspace.js', "$adm/js/website-workspace.js");
        $this->log[] = 'Copied bundled fonts, front-end libraries and website admin assets (no external CDNs).';
    }

    private function stepMigrateDb(): void
    {
        $db = $this->site->db();
        $applied = (new \SiteRuntime\Migrator($db, RUNTIME_PATH . '/schema'))->migrate(); // idempotent
        if ($applied) {
            $this->log[] = 'Applied migrations: ' . implode(', ', $applied) . '.';
        }
        $expected = ['settings', 'pages', 'sections', 'media', 'admin_users', 'login_attempts', 'contact_submissions', 'menu_items', 'payment_transactions', 'payment_events'];
        $have = array_map(fn ($t) => substr($t, strlen($db->prefix())), $db->tables());
        $missing = array_diff($expected, $have);
        if ($missing) {
            throw new RuntimeException('Missing tables: ' . implode(', ', $missing));
        }
        $pages = (int) $db->value('SELECT COUNT(*) FROM ' . $db->t('pages'));
        $sections = (int) $db->value('SELECT COUNT(*) FROM ' . $db->t('sections'));
        $this->log[] = count($have) . " tables with prefix {$db->prefix()} ($pages pages, $sections sections).";
    }

    private function stepWriteConfig(): void
    {
        $st = $this->staging();
        $w = $this->site->website();
        $this->fresh("$st/config");
        // Keep the existing site key on regeneration (it signs form tokens and will encrypt payment secrets).
        $key = null;
        $live = $this->site->folder() . '/config/app.php';
        if (is_file($live)) {
            $old = (static fn ($f) => require $f)($live);
            $key = is_array($old) ? ($old['key'] ?? null) : null;
        }
        // The template version is the one actually copied (regeneration always copies the template's current files).
        $tplVersion = (string) ((json_decode((string) file_get_contents("$st/theme/manifest.json"), true) ?: [])['version'] ?? $w['template_version']);
        if ($tplVersion !== (string) $w['template_version']) {
            Database::update('wb_websites', ['template_version' => $tplVersion, 'updated_at' => now()], $this->site->id());
            $this->log[] = "Template version recorded: {$w['template_version']} → $tplVersion.";
        }
        $features = ['contact'];
        if (!empty($w['payment_gateway']) && !empty(json_decode((string) $w['features'], true)['payments'])) {
            $features[] = 'payments';
        }
        $app = [
            'site_code' => $w['site_code'],
            'name' => $w['name'],
            'environment' => 'qa',
            'debug' => false,
            'timezone' => 'Asia/Kolkata',
            'clean_urls' => true,
            'base_path' => '',
            'key' => $key ?: base64_encode(random_bytes(32)),
            'runtime_version' => $this->runtimeVersion(),
            'template' => $w['template_code'],
            'template_version' => $tplVersion,
            'features' => $features,
            'generated_at' => date('c'),
        ];
        // Developer setting only (config/config.php "payment_emulator"): points test-mode payments at a local gateway imitation.
        if (is_string($emu = \App\Core\Config::get('payment_emulator')) && $emu !== '') {
            $app['payment_emulator'] = $emu;
        }
        $cfg = (array) \App\Core\Config::get('db', []);
        $database = [
            'host' => $cfg['host'] ?? 'localhost', 'port' => (int) ($cfg['port'] ?? 3306), 'name' => $cfg['name'] ?? '',
            'user' => $cfg['user'] ?? '', 'pass' => $cfg['pass'] ?? '', 'prefix' => $w['table_prefix'],
        ];
        $payments = json_decode((string) $w['features'], true)['payments'] ?? null;
        $payment = [
            'enabled' => (bool) ($payments['enabled'] ?? false),
            'gateway' => $payments['gateway'] ?? null,
            'mode' => $payments['mode'] ?? 'test',
            // Gateway credentials are never written here by the builder; they are entered in the website's own admin.
        ];
        $this->writePhp("$st/config/app.php", $app, 'Website settings');
        $this->writePhp("$st/config/database.php", $database, 'Database connection. QA copy on the builder server: replaced by the installer when the website is deployed');
        $this->writePhp("$st/config/payment.php", $payment, 'Payment gateway selection (credentials are stored encrypted in the database, never in files)');
        copy(RUNTIME_PATH . '/skeleton/_private/.htaccess', "$st/config/.htaccess");
        file_put_contents("$st/VERSION", json_encode([
            'runtime' => $this->runtimeVersion(), 'template' => $w['template_code'], 'template_version' => $tplVersion,
            'generated_at' => date('c'), 'run' => (int) $this->run['id'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $this->log[] = 'Wrote config/app.php, config/database.php (prefix ' . $w['table_prefix'] . '), config/payment.php, VERSION.';
    }

    private function stepValidateFiles(): void
    {
        $st = $this->staging();
        $required = ['index.php', '.htaccess', 'core/autoload.php', 'core/Site.php', 'core/Renderer.php', 'core/View.php', 'core/Db.php', 'core/FormHandler.php',
            'theme/manifest.json', 'theme/layouts/default.php', 'theme/partials/header.php', 'theme/partials/footer.php',
            'assets/fonts/fonts.json', 'assets/lib/libraries.json', 'config/app.php', 'config/database.php', 'config/.htaccess', 'core/.htaccess',
            'core/Admin/App.php', 'core/Admin/Auth.php', 'core/Admin/views/layout-main.php', 'core/editor/editor-modals.php', 'vendor/autoload.php', 'vendor/.htaccess',
            'assets/admin/js/app.js', 'assets/admin/js/website-workspace.js', 'assets/pay/pay.js', 'core/Payments/PaymentPage.php', 'assets/admin/vendor/jquery/jquery.min.js', 'assets/admin/vendor/tinymce/tinymce.min.js'];
        $manifest = json_decode((string) file_get_contents("$st/theme/manifest.json"), true) ?: [];
        foreach ((array) ($manifest['section_types'] ?? []) as $t) {
            $required[] = "theme/sections/$t.php";
        }
        $missing = array_values(array_filter($required, static fn ($f) => !is_file("$st/$f")));
        if ($missing) {
            throw new RuntimeException('Missing files: ' . implode(', ', $missing));
        }
        $lint = $this->lint($st);
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($st, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $rel = substr(str_replace('\\', '/', $f->getPathname()), strlen($st) + 1);
                if (!str_starts_with($rel, 'config/')) {
                    $files[$rel] = hash_file('sha256', $f->getPathname());
                }
            }
        }
        ksort($files);
        file_put_contents("$st/generation.json", json_encode(['generated_at' => date('c'), 'run' => (int) $this->run['id'], 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->log[] = count($required) . ' required files present; ' . count($files) . ' files recorded with checksums.';
        $this->log[] = $lint;
    }

    private function stepSwap(): void
    {
        $live = $this->site->folder();
        $st = $this->staging();
        if (!is_dir($live) && !mkdir($live, 0755, true)) {
            throw new RuntimeException('Could not create the website folder.');
        }
        foreach (['uploads', 'storage'] as $keep) {
            if (!is_dir("$live/$keep")) {
                mkdir("$live/$keep", 0755, true);
            }
        }
        foreach (['storage/logs', 'storage/cache'] as $d) {
            if (!is_dir("$live/$d")) {
                mkdir("$live/$d", 0755, true);
            }
        }
        copy(RUNTIME_PATH . '/skeleton/_private/.htaccess', "$live/storage/.htaccess");
        copy(RUNTIME_PATH . '/skeleton/uploads/.htaccess', "$live/uploads/.htaccess");

        // Move the current code aside (backup), then move the new code in.
        $backup = STORAGE_PATH . '/backups/sites/site_' . $this->site->website()['site_code'] . '/' . date('Ymd-His') . '-run' . $this->run['id'];
        $moved = [];
        foreach (self::CODE_ENTRIES as $entry) {
            if (file_exists("$live/$entry")) {
                if (!is_dir($backup) && !mkdir($backup, 0755, true)) {
                    throw new RuntimeException('Could not create the backup folder.');
                }
                if (!@rename("$live/$entry", "$backup/$entry")) {
                    $this->restore($backup, $moved);
                    throw new RuntimeException("Could not move $entry to the backup folder.");
                }
                $moved[] = $entry;
            }
        }
        foreach (self::CODE_ENTRIES as $entry) {
            if (file_exists("$st/$entry") && !@rename("$st/$entry", "$live/$entry")) {
                $this->restore($backup, $moved);
                throw new RuntimeException("Could not move $entry into the website folder.");
            }
        }
        $this->cleanupStaging();
        $this->invalidateOpcache($live);
        Database::update('wb_generation_runs', ['error_message' => null], (int) $this->run['id']);
        $this->setMeta('backup', $moved ? substr($backup, strlen(BASE_PATH) + 1) : null);
        $this->log[] = 'Published to ' . $this->site->website()['folder_path'] . ($moved ? '. Previous code kept in ' . substr($backup, strlen(BASE_PATH) + 1) : ' (first generation).') . ' uploads/ was not touched.';
    }

    /** Request every active page over HTTP (as a visitor would) and check the responses. */
    private function stepSmokeTest(): string
    {
        $url = QaAccess::siteUrl($this->site->website());
        $this->waitForOpcache();
        $db = $this->site->db();
        $pages = $db->fetchAll('SELECT slug, title, is_home FROM ' . $db->t('pages') . " WHERE status = 'active' ORDER BY sort_order");
        $probe = $this->http($url);
        if ($probe['code'] === 0) {
            // Server cannot reach its own QA URL (e.g. QA URL not set up yet). Fall back to rendering through the generated files' data.
            $this->log[] = "Warning: could not reach $url ({$probe['error']}). Check Settings → QA access. Pages were checked by rendering instead.";
            foreach ($pages as $p) {
                $out = $this->site->renderer()->render((int) $p['is_home'] === 1 ? '' : $p['slug']);
                if ($out['status'] !== 200 || !str_contains($out['html'], '</html>')) {
                    $this->failSmoke("Page “{$p['title']}” did not render.");
                }
            }
            $this->log[] = count($pages) . ' page(s) rendered successfully.';
            return 'skipped';
        }
        $checked = 0;
        foreach ($pages as $p) {
            $u = (int) $p['is_home'] === 1 ? $url : $url . rawurlencode($p['slug']);
            $r = (int) $p['is_home'] === 1 ? $probe : $this->http($u);
            if ($r['code'] !== 200 || !str_contains($r['body'], '</html>') || preg_match('/(Fatal error|Parse error|Warning:|Notice:|Deprecated:)/', $r['body'])) {
                $this->failSmoke("“{$p['title']}” ($u) returned HTTP {$r['code']}" . ($r['code'] === 401 ? ' — the QA password check failed.' : '.'));
            }
            $checked++;
        }
        $nf = $this->http($url . 'this-page-does-not-exist-' . bin2hex(random_bytes(3)));
        if ($nf['code'] !== 404) {
            $this->failSmoke("Unknown pages should return 404, got {$nf['code']}.");
        }
        $cfg = $this->http($url . 'config/database.php');
        if ($cfg['code'] === 200 && $cfg['body'] !== '') {
            $this->failSmoke('config/ is readable from the web. Check that .htaccess files are supported.');
        }
        $adm = $this->http($url . 'admin/login');
        if ($adm['code'] !== 200 || !str_contains($adm['body'], 'name="password"')) {
            $this->failSmoke("The website admin login (admin/login) returned HTTP {$adm['code']}.");
        }
        $vendor = $this->http($url . 'vendor/autoload.php');
        if ($vendor['code'] === 200 && $vendor['body'] !== '') {
            $this->failSmoke('vendor/ is readable from the web.');
        }
        preg_match('#href="([^"]+/theme/assets/css/[^"?]+)#', $probe['body'], $m);
        if (!empty($m[1])) {
            $css = $this->http($this->absolute($m[1], $url));
            if ($css['code'] !== 200) {
                $this->failSmoke("Template stylesheet returned HTTP {$css['code']}.");
            }
        }
        $this->log[] = "$checked page(s) returned 200 with no PHP errors; unknown URL returned 404; config/ and vendor/ are protected; admin login loads; assets load. ($url)";
        return 'success';
    }

    private function stepFinish(): void
    {
        $w = $this->site->website();
        Database::update('wb_generation_runs', ['status' => 'success', 'finished_at' => now(), 'current_step' => null], (int) $this->run['id']);
        Database::update('wb_websites', ['status' => 'generated', 'runtime_version' => $this->runtimeVersion(), 'updated_at' => now()], $this->site->id());
        // Keep only the 3 most recent code backups for this site.
        $dir = STORAGE_PATH . '/backups/sites/site_' . $w['site_code'];
        $all = is_dir($dir) ? array_values(array_filter((array) scandir($dir), static fn ($e) => $e[0] !== '.')) : [];
        rsort($all);
        foreach (array_slice($all, 3) as $old) {
            FileSystem::deleteDir("$dir/$old");
        }
        @unlink(STORAGE_PATH . '/staging/run' . $this->run['id'] . '.meta.json');
        Activity::log('website.generated', 'website', $this->site->id(), "Run #{$this->run['id']}");
        $this->log[] = 'Website generated. QA address: ' . QaAccess::siteUrl($w);
    }

    // ================================================================== helpers

    private function failSmoke(string $message): never
    {
        $backup = $this->getMeta('backup');
        $live = $this->site->folder();
        if ($backup) {
            // Put the previous working code back.
            foreach (self::CODE_ENTRIES as $entry) {
                if (file_exists(BASE_PATH . "/$backup/$entry")) {
                    $this->removePath("$live/$entry");
                    rename(BASE_PATH . "/$backup/$entry", "$live/$entry");
                }
            }
            $this->invalidateOpcache($live);
            $this->log[] = 'The previous version of the website was restored.';
        } else {
            // First generation: remove the broken code (uploads are kept).
            foreach (self::CODE_ENTRIES as $entry) {
                $this->removePath("$live/$entry");
            }
            $this->log[] = 'The generated code was removed (uploads were kept).';
        }
        throw new RuntimeException($message);
    }

    /**
     * PHP's OPcache may keep serving the previously compiled version of a file for a few seconds
     * (opcache.revalidate_freq). Invalidate the new files in this PHP pool, and give other pools time to notice.
     */
    private function invalidateOpcache(string $dir): void
    {
        if (!function_exists('opcache_invalidate')) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === 'php') {
                @opcache_invalidate($f->getPathname(), true);
            }
        }
    }

    private function waitForOpcache(): void
    {
        if (ini_get('opcache.enable') && ini_get('opcache.validate_timestamps')) {
            $freq = (int) ini_get('opcache.revalidate_freq');
            if ($freq > 0) {
                sleep(min(5, $freq + 1));
                $this->log[] = "Waited {$freq}s for PHP's code cache to pick up the new files.";
            }
        }
    }

    private function restore(string $backup, array $moved): void
    {
        $live = $this->site->folder();
        foreach ($moved as $entry) {
            $this->removePath("$live/$entry");
            @rename("$backup/$entry", "$live/$entry");
        }
    }

    private function http(string $url): array
    {
        if (!function_exists('curl_init')) {
            return ['code' => 0, 'body' => '', 'error' => 'the PHP curl extension is not installed'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERPWD => QaAccess::USER . ':' . QaAccess::password(), CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERAGENT => 'ChannelSoftech-Builder-Check/1.0',
        ]);
        $body = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['code' => $code, 'body' => $body, 'error' => $err];
    }

    private function absolute(string $href, string $siteUrl): string
    {
        $href = html_entity_decode($href);
        if (preg_match('#^https?://#', $href)) {
            return $href;
        }
        $p = parse_url($siteUrl);
        return $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '') . $href;
    }

    private function lint(string $dir): string
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (!function_exists('exec') || in_array('exec', $disabled, true) || PHP_BINARY === '' || !preg_match('/php(\d|\.\d)*(\.exe)?$/i', basename(PHP_BINARY))) {
            return 'PHP syntax check skipped (exec() is not available on this server); the website checks cover it.';
        }
        $count = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === 'php') {
                $out = [];
                $rc = 0;
                exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($f->getPathname()) . ' 2>&1', $out, $rc);
                if ($rc !== 0) {
                    throw new RuntimeException('PHP syntax error: ' . implode(' ', $out));
                }
                $count++;
            }
        }
        return "PHP syntax check passed for $count files.";
    }

    private function staging(): string
    {
        return STORAGE_PATH . '/staging/site_' . $this->site->website()['site_code'] . '_run' . $this->run['id'];
    }

    private function cleanupStaging(): void
    {
        $dir = $this->staging();
        if (is_dir($dir)) {
            FileSystem::deleteDir($dir);
        }
    }

    private function fresh(string $dir): void
    {
        if (is_dir($dir)) {
            FileSystem::deleteDir($dir);
        }
        if (!mkdir($dir, 0755, true)) {
            throw new RuntimeException('Could not create ' . basename($dir));
        }
    }

    private function removePath(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            FileSystem::deleteDir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }

    private function writePhp(string $file, array $data, string $comment): void
    {
        $php = "<?php\n// $comment.\n// Generated by the Channel Softech Website Builder on " . date('Y-m-d H:i:s') . ". Keep this file private.\nreturn " . var_export($data, true) . ";\n";
        if (file_put_contents($file, $php, LOCK_EX) === false) {
            throw new RuntimeException('Could not write ' . basename($file));
        }
        @chmod($file, 0640);
    }

    private function runtimeVersion(): string
    {
        return trim((string) @file_get_contents(RUNTIME_PATH . '/VERSION')) ?: '1.0.0';
    }

    private function setMeta(string $key, ?string $value): void
    {
        $file = STORAGE_PATH . '/staging/run' . $this->run['id'] . '.meta.json';
        $m = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        $m[$key] = $value;
        file_put_contents($file, json_encode($m));
    }

    private function getMeta(string $key): ?string
    {
        $file = STORAGE_PATH . '/staging/run' . $this->run['id'] . '.meta.json';
        $m = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        return $m[$key] ?? null;
    }
}
