<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Database;
use App\Core\FileSystem;
use App\Core\Html;
use App\Core\Logger;
use RuntimeException;
use SiteRuntime\Db;
use SiteRuntime\DbSource;
use SiteRuntime\Renderer;
use SiteRuntime\SectionTypes;
use SiteRuntime\Seeder;

/**
 * Everything about one website's own data: its prefixed tables, its folder, its template.
 * The builder never touches a site table except through the SiteRuntime\Db returned here.
 */
final class SiteService
{
    public const MEDIA_FOLDERS = ['logo' => 'Logo & favicon', 'banners' => 'Banners', 'gallery' => 'Gallery', 'pages' => 'Page images', 'team' => 'Team & people', 'general' => 'General'];

    private array $cache = [];

    public function __construct(private array $website)
    {
    }

    public static function load(int $id): ?self
    {
        $w = Database::fetch(
            'SELECT w.*, t.code AS template_code, t.folder_path AS template_folder, t.name AS template_name, c.business_name AS client_name, cat.name AS category_name
             FROM wb_websites w JOIN wb_clients c ON c.id = w.client_id JOIN wb_categories cat ON cat.id = w.category_id
             LEFT JOIN wb_templates t ON t.id = w.template_id WHERE w.id = ?',
            [$id]
        );
        return $w ? new self($w) : null;
    }

    public function website(): array
    {
        return $this->website;
    }

    public function id(): int
    {
        return (int) $this->website['id'];
    }

    public function db(): Db
    {
        if (empty($this->website['table_prefix'])) {
            throw new RuntimeException('This website has no tables yet.');
        }
        return $this->cache['db'] ??= new Db(Database::pdo(), (string) $this->website['table_prefix']);
    }

    public function folder(): string
    {
        $abs = FileSystem::within(SITES_PATH, BASE_PATH . '/' . $this->website['folder_path']);
        if ($abs === null) {
            throw new RuntimeException('Website folder path is invalid.');
        }
        return $abs;
    }

    public function uploadsDir(): string
    {
        return $this->folder() . '/uploads';
    }

    public function themeDir(): string
    {
        $abs = FileSystem::within(TEMPLATES_PATH, BASE_PATH . '/' . $this->website['template_folder']);
        if ($abs === null || !is_dir($abs)) {
            throw new RuntimeException('The template folder for this website is missing.');
        }
        return $abs;
    }

    public function manifest(): array
    {
        return $this->cache['manifest'] ??= (json_decode((string) file_get_contents($this->themeDir() . '/manifest.json'), true) ?: []);
    }

    public function sectionTypes(): SectionTypes
    {
        return $this->cache['types'] ??= new SectionTypes(RUNTIME_PATH . '/section-types', $this->themeDir(), $this->manifest());
    }

    /** Schemas of the section types this website's template supports. */
    public function schemas(): array
    {
        return $this->sectionTypes()->only((array) ($this->manifest()['section_types'] ?? []));
    }

    public function source(): DbSource
    {
        return new DbSource($this->db());
    }

    public function renderer(): Renderer
    {
        $id = $this->id();
        $tid = (int) $this->website['template_id'];
        return new Renderer($this->source(), $this->themeDir(), $this->manifest(), self::previewUrls("websites/$id/preview", $tid, "websites/$id/media"), RUNTIME_PATH . '/fonts', true);
    }

    /** URL patterns used when the builder renders a site or template preview. */
    public static function previewUrls(string $pageBase, int $templateId, string $mediaBase): array
    {
        return [
            'home' => url($pageBase),
            'page' => url($pageBase) . '?page={slug}',
            'theme_asset' => url("preview-assets/template/$templateId/{path}"),
            'upload' => url($mediaBase . '/{path}'),
            'font' => url('preview-assets/runtime/fonts/{path}'),
            'lib' => url('preview-assets/runtime/lib/{path}'),
            'form' => '',
        ];
    }

    // ------------------------------------------------------------------ settings

    public function settings(): array
    {
        return $this->source()->settings();
    }

    public function saveSetting(string $key, array $value): void
    {
        $db = $this->db();
        $db->query(
            'REPLACE INTO ' . $db->t('settings') . ' (setting_key, setting_value, updated_at) VALUES (?, ?, ?)',
            [$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now()]
        );
    }

    // ------------------------------------------------------------------ validation helpers

    /** Callback for SectionTypes::sanitize: image references must belong to this site or its template. */
    public function imageValidator(): callable
    {
        return function (string $ref): bool {
            if (str_starts_with($ref, 'theme:')) {
                $rel = substr($ref, 6);
                return !str_contains($rel, '..') && is_file($this->themeDir() . '/assets/' . $rel);
            }
            if (str_starts_with($ref, 'uploads/')) {
                return (int) $this->db()->value('SELECT COUNT(*) FROM ' . $this->db()->t('media') . ' WHERE path = ?', [$ref]) > 0;
            }
            return false;
        };
    }

    public function richtext(): callable
    {
        return static fn (string $html): string => Html::purify($html);
    }

    /** Where an uploaded file is used (sections and settings), as human-readable labels. */
    public function mediaUsage(string $path): array
    {
        $db = $this->db();
        $like = '%' . addcslashes(json_encode($path, JSON_UNESCAPED_SLASHES), '%_\\') . '%';
        $out = [];
        foreach ($db->fetchAll('SELECT s.section_type, p.title FROM ' . $db->t('sections') . ' s JOIN ' . $db->t('pages') . ' p ON p.id = s.page_id WHERE s.data LIKE ? OR s.style LIKE ?', [$like, $like]) as $r) {
            $out[] = $r['title'] . ' → ' . (($this->schemas()[$r['section_type']]['label'] ?? $r['section_type']));
        }
        foreach ($db->fetchAll('SELECT setting_key FROM ' . $db->t('settings') . ' WHERE setting_value LIKE ?', [$like]) as $r) {
            $out[] = 'Settings → ' . $r['setting_key'];
        }
        return $out;
    }

    // ------------------------------------------------------------------ lifecycle

    /**
     * Create the site's tables (prefix sNNNNNN_), its folder, and seed it from the template.
     * Rolls back (drops tables, removes folder) if anything fails.
     */
    public static function provision(int $websiteId): void
    {
        $w = Database::fetch('SELECT w.*, t.folder_path AS template_folder FROM wb_websites w JOIN wb_templates t ON t.id = w.template_id WHERE w.id = ?', [$websiteId]);
        if (!$w) {
            throw new RuntimeException('Website not found.');
        }
        $code = str_pad((string) $websiteId, 6, '0', STR_PAD_LEFT);
        $prefix = 's' . $code . '_';
        $folderRel = 'generated_websites/site_' . $code . '_' . (slugify((string) $w['slug'], '-') ?: 'site');
        Database::update('wb_websites', ['site_code' => $code, 'table_prefix' => $prefix, 'folder_path' => $folderRel, 'updated_at' => now()], $websiteId);

        $svc = self::load($websiteId);
        $db = $svc->db();
        if ($db->tables()) {
            throw new RuntimeException("Tables with the prefix $prefix already exist. Refusing to overwrite them.");
        }
        $folder = BASE_PATH . '/' . $folderRel;
        try {
            foreach (array_keys(self::MEDIA_FOLDERS) as $f) {
                if (!is_dir("$folder/uploads/$f") && !mkdir("$folder/uploads/$f", 0755, true)) {
                    throw new RuntimeException('Could not create the website folder.');
                }
            }
            $db->runSchema(RUNTIME_PATH . '/schema/core.sql');
            $demoFile = $svc->themeDir() . '/demo.json';
            $demo = is_file($demoFile) ? (json_decode((string) file_get_contents($demoFile), true) ?: []) : [];
            $client = Database::fetch('SELECT * FROM wb_clients WHERE id = ?', [$w['client_id']]) ?? [];
            $seed = Seeder::build($svc->manifest(), $svc->sectionTypes(), $demo, [
                'name' => $w['name'],
                'tagline' => mb_substr((string) $w['description'], 0, 150),
                'email' => $client['email'] ?? null,
                'phone' => $client['phone'] ?? null,
                'address' => $client['address'] ?? null,
            ]);
            Seeder::insert($db, $seed);
            Database::update('wb_websites', ['template_version' => Database::value('SELECT current_version FROM wb_templates WHERE id = ?', [$w['template_id']]), 'updated_at' => now()], $websiteId);
        } catch (\Throwable $e) {
            Logger::error('Provisioning failed', ['website' => $websiteId, 'error' => $e->getMessage()]);
            $svc->dropTables();
            if (is_dir($folder)) {
                FileSystem::deleteDir($folder);
            }
            throw $e;
        }
    }

    public function dropTables(): void
    {
        $db = $this->db();
        $tables = $db->tables();
        if (!$tables) {
            return;
        }
        $pdo = $db->pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($tables as $t) {
                // Only ever drop tables that carry this site's own prefix.
                if (str_starts_with($t, $db->prefix()) && preg_match('/^s\d{6}_[a-z0-9_]+$/', $t)) {
                    $pdo->exec('DROP TABLE `' . $t . '`');
                }
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /** ZIP backup of the site folder plus a JSON dump of every site table. Returns the backup path. */
    public function backup(string $reason): string
    {
        $dir = STORAGE_PATH . '/backups/websites';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $zipPath = $dir . '/site_' . $this->website['site_code'] . '_' . date('Ymd-His') . '_' . $reason . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the backup archive.');
        }
        $db = $this->db();
        $dump = ['website' => $this->website, 'tables' => []];
        foreach ($db->tables() as $t) {
            $dump['tables'][substr($t, strlen($db->prefix()))] = $db->pdo()->query('SELECT * FROM `' . $t . '`')->fetchAll(\PDO::FETCH_ASSOC);
        }
        $zip->addFromString('database.json', (string) json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $folder = BASE_PATH . '/' . $this->website['folder_path'];
        if (is_dir($folder)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && !$f->isLink()) {
                    $zip->addFile($f->getPathname(), 'files/' . substr($f->getPathname(), strlen($folder) + 1));
                }
            }
        }
        $zip->close();
        return $zipPath;
    }
}
