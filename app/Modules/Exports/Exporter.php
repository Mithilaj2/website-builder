<?php
declare(strict_types=1);

namespace App\Modules\Exports;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Modules\Websites\SiteService;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Staged export of a generated website (no cron: the browser calls step() repeatedly).
 *
 *  - full package:   code + images + database dump + installer + guides, for a first deployment on any cPanel host
 *  - update package: code only (no config, images or data), for websites that are already deployed
 *
 * Nothing secret leaves the builder: config/*.php (builder database credentials, website key) is replaced by
 * example files, admin logins and login attempts are not exported, encrypted values (SMTP password, payment keys)
 * are blanked, test payments and payment logs are dropped, and the developer-only payment emulator is removed.
 */
final class Exporter
{
    public const STEPS_FULL = [
        'prepare' => 'Check the website',
        'database' => 'Export the database',
        'code' => 'Package the website code',
        'uploads' => 'Package images and files',
        'installer' => 'Add the installer and guides',
        'verify' => 'Verify the package',
        'finish' => 'Finish',
    ];
    public const STEPS_UPDATE = [
        'prepare' => 'Check the website',
        'code' => 'Package the website code',
        'installer' => 'Add the update guide',
        'verify' => 'Verify the package',
        'finish' => 'Finish',
    ];
    public const DEFAULT_PREFIX = 'site_';
    /** Code that goes into both package types (relative to the website folder). */
    private const CODE = ['index.php', '.htaccess', 'robots.txt', 'core', 'vendor', 'theme', 'assets', 'VERSION', 'generation.json'];
    private const UPLOAD_BATCH = 400;          // files per request
    private const UPLOAD_BATCH_BYTES = 40_000_000;
    private const SQL_CHUNK_ROWS = 100;
    private const SQL_CHUNK_BYTES = 500_000;

    private array $export;
    private SiteService $site;
    private array $state;
    private array $log = [];

    private function __construct(array $export)
    {
        $this->export = $export;
        $site = SiteService::load((int) $export['website_id']);
        if (!$site) {
            throw new RuntimeException('Website not found.');
        }
        $this->site = $site;
        $this->state = json_decode((string) ($export['state'] ?? ''), true) ?: [];
    }

    public static function dir(): string
    {
        return STORAGE_PATH . '/exports';
    }

    /** @param array{include_enquiries?:bool, include_payments?:bool, prefix?:string} $options */
    public static function start(SiteService $site, string $type, array $options): int
    {
        $w = $site->website();
        if (Database::value("SELECT id FROM wb_exports WHERE website_id = ? AND status = 'running' AND created_at > ?", [$site->id(), date('Y-m-d H:i:s', time() - 3600)])) {
            throw new RuntimeException('An export is already running for this website.');
        }
        if ($type === 'full' && !in_array($w['status'], ['generated', 'testing', 'ready'], true)) {
            throw new RuntimeException($w['status'] === 'deployed'
                ? 'This website is deployed: its content now lives on the client’s hosting. Export an update package instead.'
                : 'Generate the website before exporting it.');
        }
        if ($type === 'update' && !in_array($w['status'], ['generated', 'testing', 'ready', 'deployed'], true)) {
            throw new RuntimeException('Generate the website before exporting an update package.');
        }
        if (!is_file($site->folder() . '/generation.json')) {
            throw new RuntimeException('The website has not been generated yet.');
        }
        $prefix = strtolower(trim((string) ($options['prefix'] ?? self::DEFAULT_PREFIX)));
        if (!preg_match('/^[a-z][a-z0-9]{0,14}_$/', $prefix)) {
            throw new RuntimeException('The table prefix must be letters and digits ending with “_”, e.g. site_.');
        }
        $steps = $type === 'full' ? self::STEPS_FULL : self::STEPS_UPDATE;
        $id = Database::insert('wb_exports', [
            'website_id' => $site->id(), 'type' => $type, 'status' => 'running', 'step' => array_key_first($steps), 'progress' => 0,
            'options' => json_encode(['include_enquiries' => !empty($options['include_enquiries']), 'include_payments' => !empty($options['include_payments']), 'prefix' => $prefix]),
            'state' => json_encode(['done' => []]), 'created_by' => Auth::id(), 'created_at' => now(),
        ]);
        Activity::log('website.export_started', 'website', $site->id(), ucfirst($type) . " package #$id");
        return $id;
    }

    public static function load(int $id): self
    {
        $e = Database::fetch('SELECT * FROM wb_exports WHERE id = ?', [$id]);
        if (!$e) {
            throw new RuntimeException('Export not found.');
        }
        return new self($e);
    }

    public function steps(): array
    {
        return $this->export['type'] === 'full' ? self::STEPS_FULL : self::STEPS_UPDATE;
    }

    public function step(): array
    {
        if ($this->export['status'] !== 'running') {
            return $this->state();
        }
        $key = (string) $this->export['step'];
        @set_time_limit(180);
        try {
            $more = $this->{'step' . ucfirst($key)}();
            if ($more !== true) {
                $this->state['done'][] = $key;
                $keys = array_keys($this->steps());
                $next = $keys[array_search($key, $keys, true) + 1] ?? null;
                $this->save(['step' => $next ?? $key, 'status' => $next ? 'running' : 'done', 'finished_at' => $next ? null : now()]);
            } else {
                $this->save([]);
            }
        } catch (Throwable $e) {
            $this->log[] = 'ERROR: ' . $e->getMessage();
            Logger::error('Export step failed', ['export' => $this->export['id'], 'step' => $key, 'error' => $e->getMessage(), 'file' => $e->getFile() . ':' . $e->getLine()]);
            $this->save(['status' => 'failed', 'error_message' => mb_substr(($this->steps()[$key] ?? $key) . ': ' . $e->getMessage(), 0, 500), 'finished_at' => now()]);
            $this->cleanupWork();
            if (!empty($this->export['zip_path']) && is_file(BASE_PATH . '/' . $this->export['zip_path'])) {
                @unlink(BASE_PATH . '/' . $this->export['zip_path']);
            }
            Activity::log('website.export_failed', 'website', $this->site->id(), "#{$this->export['id']}: " . $e->getMessage());
        }
        return $this->state();
    }

    public function state(): array
    {
        $done = $this->state['done'] ?? [];
        $steps = [];
        foreach ($this->steps() as $k => $label) {
            $status = in_array($k, $done, true) ? 'success' : ($k === $this->export['step'] ? ($this->export['status'] === 'failed' ? 'failed' : ($this->export['status'] === 'running' ? 'running' : 'success')) : 'pending');
            $steps[] = ['step_key' => $k, 'label' => $label, 'status' => $status];
        }
        $progress = (int) round(count($done) / max(1, count($steps)) * 100);
        if ($this->export['step'] === 'uploads' && $this->export['status'] === 'running' && !empty($this->state['upload_total'])) {
            $progress += (int) round(($this->state['upload_offset'] ?? 0) / $this->state['upload_total'] * (100 / count($steps)));
        }
        return [
            'export_id' => (int) $this->export['id'], 'type' => $this->export['type'], 'status' => $this->export['status'],
            'error' => $this->export['error_message'], 'progress' => min(100, $progress), 'steps' => $steps,
            'log' => array_slice(array_filter(explode("\n", (string) $this->export['log'])), -20),
            'detail' => $this->export['step'] === 'uploads' && !empty($this->state['upload_total']) ? ($this->state['upload_offset'] ?? 0) . ' of ' . $this->state['upload_total'] . ' files' : null,
        ];
    }

    // ================================================================== steps

    private function stepPrepare(): void
    {
        $w = $this->site->website();
        $this->cleanupWork();
        if (!is_dir(self::dir()) && !mkdir(self::dir(), 0755, true)) {
            throw new RuntimeException('Cannot create storage/exports.');
        }
        if (!is_file(self::dir() . '/.htaccess')) {
            file_put_contents(self::dir() . '/.htaccess', "Require all denied\n");
        }
        if (!mkdir($this->work(), 0755, true)) {
            throw new RuntimeException('Cannot create the working folder.');
        }
        $free = @disk_free_space(self::dir());
        $need = $this->export['type'] === 'full' ? self::dirSize($this->site->folder() . '/uploads') * 2 + 50_000_000 : 50_000_000;
        if ($free !== false && $free < $need) {
            throw new RuntimeException('Not enough free disk space for the package (' . self::mb($need) . ' needed).');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to export websites.');
        }
        $gen = json_decode((string) file_get_contents($this->site->folder() . '/generation.json'), true) ?: [];
        $version = json_decode((string) @file_get_contents($this->site->folder() . '/VERSION'), true) ?: [];
        $tplCurrent = (string) Database::value('SELECT current_version FROM wb_templates WHERE id = ?', [(int) $w['template_id']]);
        if ($tplCurrent !== '' && ($version['template_version'] ?? '') !== '' && version_compare($tplCurrent, (string) $version['template_version'], '>')) {
            $this->log[] = "Warning: the website was generated with template {$version['template_version']}, but {$tplCurrent} is available. Regenerate first to include the template’s latest fixes.";
        }
        $runtime = trim((string) @file_get_contents(RUNTIME_PATH . '/VERSION'));
        if (($version['runtime'] ?? '') !== $runtime) {
            $this->log[] = "Warning: the website was generated with runtime " . ($version['runtime'] ?? '?') . ", but {$runtime} is available. Regenerate first to include it.";
        }
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $w['slug'])) ?: 'website';
        $name = sprintf('site_%06d_%s_%s_%s.zip', (int) $w['id'], $slug, $this->export['type'] === 'full' ? 'full' : 'update', date('Ymd-His'));
        $this->state['zip'] = 'storage/exports/' . $name;
        $this->state['install_key'] = $this->export['type'] === 'full' ? strtoupper(implode('-', str_split(bin2hex(random_bytes(6)), 4))) : null;
        $this->save(['zip_path' => $this->state['zip'], 'runtime_version' => $version['runtime'] ?? null, 'template_version' => $version['template_version'] ?? null]);
        $this->log[] = 'Website generated ' . ($gen['generated_at'] ?? '?') . ' (run #' . ($gen['run'] ?? '?') . '). Package: ' . $name;
    }

    /** schema.sql + data.sql, written in PHP (mysqldump is usually unavailable on shared hosting). */
    private function stepDatabase(): void
    {
        $db = $this->site->db();
        $old = $db->prefix();
        $new = $this->options()['prefix'];
        $pdo = Database::pdo();
        $dir = $this->work() . '/database';
        mkdir($dir, 0755, true);
        $head = "-- Website: {$this->site->website()['name']} ({$this->site->website()['site_code']})\n-- Exported " . date('c') . " by Channel Softech Website Builder\n-- Table prefix: {$new}\n"
            . "-- Import schema.sql first, then data.sql (or let install.php do both).\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
        $schema = $head;
        $data = fopen("$dir/data.sql", 'w');
        fwrite($data, $head);
        $skipped = ['admin_users' => 'administrator logins (created by the installer)', 'login_attempts' => 'login history', 'payment_events' => 'gateway message log'];
        $counts = [];
        foreach ($db->tables() as $table) {
            $short = substr($table, strlen($old));
            $create = (string) $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '', $table) . '`')->fetch(\PDO::FETCH_NUM)[1];
            $create = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);
            $create = self::swapPrefix($create, $old, $new);
            $schema .= "DROP TABLE IF EXISTS `{$new}{$short}`;\n" . $create . ";\n\n";
            if (isset($skipped[$short])) {
                $this->log[] = "Not exported: $short ({$skipped[$short]}).";
                continue;
            }
            [$where, $params] = $this->rowFilter($short);
            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '`' . $where . ' ORDER BY 1');
            $stmt->execute($params);
            $rows = 0;
            $batch = [];
            $bytes = 0;
            $cols = null;
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($short === 'settings') {
                    $row = $this->cleanSetting($row);
                }
                $cols ??= '(`' . implode('`, `', array_keys($row)) . '`)';
                $vals = '(' . implode(', ', array_map(static fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), $row)) . ')';
                $batch[] = $vals;
                $bytes += strlen($vals);
                $rows++;
                if (count($batch) >= self::SQL_CHUNK_ROWS || $bytes >= self::SQL_CHUNK_BYTES) {
                    fwrite($data, "INSERT INTO `{$new}{$short}` $cols VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                    $bytes = 0;
                }
            }
            if ($batch) {
                fwrite($data, "INSERT INTO `{$new}{$short}` $cols VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            fwrite($data, "\n");
            $counts[] = "$short $rows";
        }
        fwrite($data, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($data);
        file_put_contents("$dir/schema.sql", $schema . "SET FOREIGN_KEY_CHECKS = 1;\n");
        file_put_contents("$dir/.htaccess", "Require all denied\n");
        $this->log[] = 'Tables (rows): ' . implode(', ', $counts) . '.';
        $this->log[] = 'Encrypted values (SMTP password, payment keys) blanked: the client enters them again in their admin, where they are encrypted with the new website key.';
        // A database-only ZIP for hosts where the client imports through phpMyAdmin.
        $sqlZip = substr($this->state['zip'], 0, -4) . '-database.zip';
        $z = new ZipArchive();
        if ($z->open(BASE_PATH . '/' . $sqlZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create the database ZIP.');
        }
        $z->addFile("$dir/schema.sql", 'schema.sql');
        $z->addFile("$dir/data.sql", 'data.sql');
        $z->close();
        $this->save(['sql_path' => $sqlZip]);
    }

    private function stepCode(): void
    {
        $src = $this->site->folder();
        $z = $this->zip();
        $n = 0;
        foreach (self::CODE as $entry) {
            $path = "$src/$entry";
            if (is_file($path)) {
                $z->addFile($path, $entry);
                $n++;
            } elseif (is_dir($path)) {
                $n += $this->addDir($z, $path, $entry);
            }
        }
        // Folders the website writes to, with their protection files only (no logs or cache).
        foreach (['storage', 'storage/logs', 'storage/cache', 'config'] as $d) {
            $z->addEmptyDir($d);
        }
        $z->addFromString('storage/.htaccess', is_file("$src/storage/.htaccess") ? (string) file_get_contents("$src/storage/.htaccess") : "Require all denied\n");
        $z->addFromString('config/.htaccess', (string) file_get_contents(RUNTIME_PATH . '/skeleton/_private/.htaccess'));
        $app = $this->appConfig();
        $z->addFromString('config/app.example.php', self::php($app, 'Website settings. The installer copies this to app.php with a new website key.'));
        $z->addFromString('config/database.example.php', self::php(['host' => 'localhost', 'port' => 3306, 'name' => '', 'user' => '', 'pass' => '', 'prefix' => $this->options()['prefix']], 'Database connection, written by install.php.'));
        $pay = $this->paymentConfig();
        $z->addFromString('config/payment.example.php', self::php($pay, 'Payment gateway selection. Keys are entered in the website admin (Payments → Settings), never in files.'));
        if ($this->export['type'] === 'full') {
            $z->addFile($this->work() . '/database/schema.sql', 'database/schema.sql');
            $z->addFile($this->work() . '/database/data.sql', 'database/data.sql');
            $z->addFromString('database/.htaccess', "Require all denied\n");
        }
        $this->closeZip($z);
        $this->state['code_files'] = $n;
        $this->log[] = "$n code files (engine, template, libraries); configuration replaced by example files.";
    }

    /** Images and files, in batches so large media libraries never hit a time limit. Returns true while more remain. */
    private function stepUploads(): bool
    {
        $base = $this->site->folder() . '/uploads';
        if (!isset($this->state['upload_total'])) {
            $files = self::listFiles($base);
            file_put_contents($this->work() . '/uploads.list', implode("\n", $files));
            $this->state['upload_total'] = count($files);
            $this->state['upload_offset'] = 0;
            $this->state['upload_bytes'] = 0;
        }
        $files = array_values(array_filter(explode("\n", (string) file_get_contents($this->work() . '/uploads.list'))));
        $z = $this->zip();
        $z->addEmptyDir('uploads');
        $i = (int) $this->state['upload_offset'];
        $bytes = 0;
        $added = 0;
        while ($i < count($files) && $added < self::UPLOAD_BATCH && $bytes < self::UPLOAD_BATCH_BYTES) {
            $rel = $files[$i++];
            $abs = "$base/$rel";
            if (is_file($abs)) {
                $z->addFile($abs, 'uploads/' . $rel);
                if (method_exists($z, 'setCompressionName') && preg_match('/\.(jpe?g|png|webp|gif|zip)$/i', $rel)) {
                    $z->setCompressionName('uploads/' . $rel, ZipArchive::CM_STORE); // already compressed
                }
                $bytes += (int) filesize($abs);
                $added++;
            }
        }
        $this->closeZip($z);
        $this->state['upload_offset'] = $i;
        $this->state['upload_bytes'] += $bytes;
        if ($i < count($files)) {
            return true;
        }
        $this->log[] = $this->state['upload_total'] . ' images and files (' . self::mb((int) $this->state['upload_bytes']) . ').';
        return false;
    }

    private function stepInstaller(): void
    {
        $z = $this->zip();
        $vars = $this->docVars();
        $docs = $this->export['type'] === 'full'
            ? ['INSTALLATION.md', 'REQUIREMENTS.md', 'DEPLOYMENT_CHECKLIST.md']
            : ['UPDATE.md', 'REQUIREMENTS.md'];
        foreach ($docs as $doc) {
            $z->addFromString($doc, strtr((string) file_get_contents(RUNTIME_PATH . '/install/docs/' . $doc), $vars));
        }
        if ($this->export['type'] === 'full') {
            $z->addFile(RUNTIME_PATH . '/install/install.php', 'install.php');
            // Only the hash of the installation key is shipped; the key itself is shown in the builder and the checklist.
            $z->addFromString('config/install.php', self::php(['key_hash' => password_hash((string) $this->state['install_key'], PASSWORD_DEFAULT), 'created_at' => date('c'),
                'prefix' => $this->options()['prefix'], 'site_name' => $this->site->website()['name']], 'Used once by install.php, then deleted.'));
        }
        $this->closeZip($z);
        $this->log[] = 'Added ' . implode(', ', $docs) . ($this->export['type'] === 'full' ? ' and install.php.' : '.');
    }

    /** Re-open the finished ZIP and check it: required files present, nothing secret inside. */
    private function stepVerify(): void
    {
        $zipFile = BASE_PATH . '/' . $this->state['zip'];
        $z = new ZipArchive();
        if ($z->open($zipFile, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('The package is damaged. Please export again.');
        }
        $names = [];
        for ($i = 0; $i < $z->numFiles; $i++) {
            $names[] = (string) $z->getNameIndex($i);
        }
        $required = ['index.php', '.htaccess', 'core/Site.php', 'core/Admin/App.php', 'core/schema/core.sql', 'vendor/autoload.php', 'theme/manifest.json', 'config/.htaccess', 'config/app.example.php', 'REQUIREMENTS.md'];
        $required = array_merge($required, $this->export['type'] === 'full'
            ? ['install.php', 'config/install.php', 'database/schema.sql', 'database/data.sql', 'INSTALLATION.md', 'DEPLOYMENT_CHECKLIST.md', 'uploads/.htaccess']
            : ['UPDATE.md']);
        $missing = array_diff($required, $names);
        if ($missing) {
            throw new RuntimeException('Missing from the package: ' . implode(', ', $missing));
        }
        foreach (['config/app.php', 'config/database.php', 'config/payment.php'] as $forbidden) {
            if (in_array($forbidden, $names, true)) {
                throw new RuntimeException("$forbidden must not be in the package.");
            }
        }
        if ($this->export['type'] === 'update') {
            foreach ($names as $n) {
                if (str_starts_with($n, 'uploads/') || str_starts_with($n, 'database/') || $n === 'install.php') {
                    throw new RuntimeException("An update package must not contain $n.");
                }
            }
        }
        // Builder secrets must not appear in any text file of the package.
        $db = (array) \App\Core\Config::get('db', []);
        $needles = array_filter([(string) ($db['pass'] ?? ''), (string) \App\Core\Config::get('app.key', ''), (string) \App\Core\Config::get('payment_emulator', ''), BASE_PATH]);
        $live = @include $this->site->folder() . '/config/app.php';
        if (is_array($live) && !empty($live['key'])) {
            $needles[] = (string) $live['key']; // the QA copy's website key stays on the builder
        }
        foreach ($names as $n) {
            if (!preg_match('/\.(php|sql|md|json|txt|htaccess|js|css|html)$|^\.htaccess$|\/\.htaccess$/', $n) || str_starts_with($n, 'uploads/') || str_starts_with($n, 'vendor/')) {
                continue;
            }
            $content = (string) $z->getFromName($n);
            foreach ($needles as $s) {
                if (strlen($s) >= 6 && str_contains($content, $s)) {
                    throw new RuntimeException("A builder-only value was found in $n. Export stopped.");
                }
            }
        }
        if (str_contains((string) $z->getFromName('config/app.example.php'), 'payment_emulator')) {
            throw new RuntimeException('The developer payment emulator setting must not be exported.');
        }
        $z->close();
        $size = (int) filesize($zipFile);
        $this->save(['size_bytes' => $size, 'file_count' => count($names), 'checksum' => hash_file('sha256', $zipFile)]);
        $this->log[] = count($names) . ' entries, ' . self::mb($size) . '; no builder credentials, website key or secrets inside.';
    }

    private function stepFinish(): void
    {
        $this->cleanupWork();
        $this->keepLatest(5);
        Activity::log('website.exported', 'website', $this->site->id(), ucfirst((string) $this->export['type']) . ' package #' . $this->export['id'] . ' (' . self::mb((int) $this->export['size_bytes']) . ')');
        $this->log[] = 'Package ready.';
    }

    // ================================================================== helpers

    private function options(): array
    {
        return (json_decode((string) $this->export['options'], true) ?: []) + ['include_enquiries' => false, 'include_payments' => false, 'prefix' => self::DEFAULT_PREFIX];
    }

    /** Which rows of a table are exported. */
    private function rowFilter(string $table): array
    {
        $o = $this->options();
        return match ($table) {
            'contact_submissions' => $o['include_enquiries'] ? [" WHERE status <> 'spam'", []] : [' WHERE 1 = 0', []],
            'payment_transactions' => $o['include_payments'] ? [" WHERE mode = 'live'", []] : [' WHERE 1 = 0', []],
            default => ['', []],
        };
    }

    /** Settings rows: blank every encrypted value (it was encrypted with the QA copy's key). */
    private function cleanSetting(array $row): array
    {
        $v = json_decode((string) $row['setting_value'], true);
        if (!is_array($v)) {
            return $row;
        }
        $walk = static function (array $a) use (&$walk): array {
            foreach ($a as $k => $x) {
                if (is_array($x)) {
                    $a[$k] = $walk($x);
                } elseif (is_string($x) && str_starts_with($x, 'v1:')) {
                    $a[$k] = '';
                }
            }
            return $a;
        };
        $v = $walk($v);
        $row['setting_value'] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $row;
    }

    private function appConfig(): array
    {
        $live = @include $this->site->folder() . '/config/app.php';
        $live = is_array($live) ? $live : [];
        unset($live['payment_emulator'], $live['generated_at']);
        return array_merge($live, ['environment' => 'production', 'debug' => false, 'base_path' => '', 'key' => '', 'exported_at' => date('c')]);
    }

    private function paymentConfig(): array
    {
        $p = @include $this->site->folder() . '/config/payment.php';
        return is_array($p) ? array_intersect_key($p, array_flip(['enabled', 'gateway', 'mode'])) : ['enabled' => false, 'gateway' => null, 'mode' => 'test'];
    }

    private function docVars(): array
    {
        $w = $this->site->website();
        $settings = $this->site->db()->fetch('SELECT setting_value FROM ' . $this->site->db()->t('settings') . " WHERE setting_key = 'payments'");
        $pay = json_decode((string) ($settings['setting_value'] ?? ''), true) ?: [];
        $gateways = \SiteRuntime\Payments\Gateways::definitions();
        $payText = !empty($pay['enabled']) && isset($gateways[$pay['gateway'] ?? ''])
            ? 'Online payments are ON with ' . $gateways[$pay['gateway']]['name'] . ' in ' . strtoupper((string) ($pay['mode'] ?? 'test')) . ' mode. Enter the ' . $gateways[$pay['gateway']]['name'] . ' keys in the website admin → Payments → Settings (they are not in this package).'
            : 'Online payments are switched off. They can be turned on later in the website admin → Payments → Settings.';
        return [
            '{{SITE_NAME}}' => (string) $w['name'], '{{SITE_CODE}}' => (string) $w['site_code'], '{{DOMAIN}}' => (string) ($w['domain'] ?: 'yourdomain.com'),
            '{{EXPORT_DATE}}' => date('d M Y, H:i'), '{{RUNTIME_VERSION}}' => (string) ($this->export['runtime_version'] ?? ''), '{{TEMPLATE}}' => (string) $w['template_code'] . ' ' . ($this->export['template_version'] ?? ''),
            '{{PREFIX}}' => $this->options()['prefix'], '{{INSTALL_KEY}}' => (string) ($this->state['install_key'] ?? ''), '{{PAYMENTS}}' => $payText,
            '{{ENQUIRIES}}' => $this->options()['include_enquiries'] ? 'Enquiries received so far are included.' : 'Enquiries from testing are not included.',
        ];
    }

    /** Replace the QA table prefix by the export prefix in table and constraint names. */
    public static function swapPrefix(string $sql, string $old, string $new): string
    {
        return (string) preg_replace('/`(fk_)?' . preg_quote($old, '/') . '/', '`$1' . $new, $sql);
    }

    private function zip(): ZipArchive
    {
        $z = new ZipArchive();
        $file = BASE_PATH . '/' . $this->state['zip'];
        if ($z->open($file, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot open the package file.');
        }
        return $z;
    }

    private function closeZip(ZipArchive $z): void
    {
        if (!$z->close()) {
            throw new RuntimeException('Could not write the package (disk full?).');
        }
    }

    private function addDir(ZipArchive $z, string $dir, string $prefix): int
    {
        $n = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $f) {
            $rel = $prefix . '/' . str_replace('\\', '/', substr($f->getPathname(), strlen($dir) + 1));
            if ($f->isDir()) {
                $z->addEmptyDir($rel);
            } elseif ($f->isFile() && !preg_match('#(^|/)\.(git|DS_Store|htpasswd)#', $rel)) {
                $z->addFile($f->getPathname(), $rel);
                $n++;
            }
        }
        return $n;
    }

    private static function listFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $out[] = str_replace('\\', '/', substr($f->getPathname(), strlen($dir) + 1));
            }
        }
        sort($out);
        return $out;
    }

    private static function dirSize(string $dir): int
    {
        $s = 0;
        foreach (self::listFiles($dir) as $f) {
            $s += (int) @filesize("$dir/$f");
        }
        return $s;
    }

    public static function mb(int $bytes): string
    {
        return $bytes >= 1_048_576 ? round($bytes / 1_048_576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
    }

    public static function php(array $data, string $comment): string
    {
        return "<?php\n// $comment\nreturn " . var_export($data, true) . ";\n";
    }

    private function work(): string
    {
        return self::dir() . '/work/' . (int) $this->export['id'];
    }

    private function cleanupWork(): void
    {
        if (is_dir($this->work())) {
            \App\Core\FileSystem::deleteDir($this->work());
        }
    }

    /** Keep the newest $n packages per website (older ZIPs are removed; their history stays). */
    private function keepLatest(int $n): void
    {
        // Runs before this export is marked done: keep it plus the $n - 1 newest finished ones.
        $keep = max(0, $n - 1);
        $old = Database::fetchAll("SELECT id, zip_path, sql_path FROM wb_exports WHERE website_id = ? AND status = 'done' AND id <> ? AND zip_path IS NOT NULL ORDER BY id DESC LIMIT 100 OFFSET $keep", [$this->site->id(), (int) $this->export['id']]);
        foreach ($old as $e) {
            foreach (['zip_path', 'sql_path'] as $k) {
                if ($e[$k] && is_file(BASE_PATH . '/' . $e[$k])) {
                    @unlink(BASE_PATH . '/' . $e[$k]);
                }
            }
            Database::update('wb_exports', ['zip_path' => null, 'sql_path' => null], (int) $e['id']);
        }
    }

    private function save(array $fields): void
    {
        $log = trim(((string) $this->export['log']) . "\n" . implode("\n", $this->log));
        $this->log = [];
        $fields += ['state' => json_encode($this->state), 'log' => mb_substr($log, -60000)];
        Database::update('wb_exports', $fields, (int) $this->export['id']);
        $this->export = Database::fetch('SELECT * FROM wb_exports WHERE id = ?', [(int) $this->export['id']]);
    }
}
