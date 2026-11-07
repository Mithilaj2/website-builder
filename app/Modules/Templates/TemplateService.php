<?php
declare(strict_types=1);

namespace App\Modules\Templates;

use App\Core\Auth;
use App\Core\Database;
use App\Core\FileSystem;
use App\Core\Uploader;
use App\Core\UploadException;
use RuntimeException;

/**
 * File-system and database operations for templates: install from ZIP or folder,
 * versions (snapshots), duplicate, move, delete. Every folder operation is staged
 * so a failure never leaves a half-written template in templates/.
 */
final class TemplateService
{
    public const BACKUP_DIR = STORAGE_PATH . '/backups/templates';

    public function categorySlugs(): array
    {
        return array_column(Database::fetchAll('SELECT slug FROM wb_categories'), 'slug');
    }

    public function categoryBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM wb_categories WHERE slug = ?', [$slug]);
    }

    public function folderFor(string $categorySlug, string $code): string
    {
        return TEMPLATES_PATH . '/' . $categorySlug . '/' . $code;
    }

    public function validate(string $dir, ?string $expectedId = null, ?string $expectedCategory = null): ManifestValidator
    {
        return (new ManifestValidator())->validate($dir, $this->categorySlugs(), $expectedId, $expectedCategory);
    }

    /** Extract an uploaded ZIP into a fresh temp folder and return the template root inside it. */
    public function extractUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new UploadException(Uploader::uploadErrorMessage((int) $file['error']));
        }
        if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'zip') {
            throw new UploadException('Please upload a .zip file.');
        }
        if ($file['size'] > 100 * 1024 * 1024) {
            throw new UploadException('ZIP is larger than 100 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'], true)) {
            throw new UploadException('The uploaded file is not a ZIP archive.');
        }
        $tmp = $this->tempPath('tpl');
        FileSystem::safeExtract($file['tmp_name'], $tmp);
        return [$tmp, $this->locateRoot($tmp)];
    }

    /** Accept either manifest.json at the ZIP root or inside a single top-level folder. */
    public function locateRoot(string $tmp): string
    {
        if (is_file("$tmp/manifest.json")) {
            return $tmp;
        }
        $entries = array_values(array_filter((array) scandir($tmp), static fn ($e) => $e !== '.' && $e !== '..' && $e !== '__MACOSX'));
        if (count($entries) === 1 && is_dir("$tmp/{$entries[0]}") && is_file("$tmp/{$entries[0]}/manifest.json")) {
            return "$tmp/{$entries[0]}";
        }
        throw new UploadException('manifest.json was not found. The ZIP must contain the template folder (or its contents) with manifest.json at the top.');
    }

    /** Register a validated template folder that already sits at templates/<cat>/<code>. */
    public function register(array $manifest, string $folder): int
    {
        $cat = $this->categoryBySlug($manifest['category']);
        if (!$cat) {
            throw new RuntimeException('Category not found.');
        }
        $id = Database::insert('wb_templates', [
            'category_id' => (int) $cat['id'],
            'code' => $manifest['id'],
            'name' => mb_substr((string) $manifest['name'], 0, 150),
            'description' => isset($manifest['description']) ? mb_substr((string) $manifest['description'], 0, 5000) : null,
            'status' => 'inactive',
            'current_version' => $manifest['version'],
            'features' => json_encode(['features' => $manifest['features'] ?? [], 'optional' => $manifest['optional_features'] ?? []]),
            'folder_path' => $this->relativeFolder($folder),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $preview = $this->importPreviewImage($folder);
        if ($preview) {
            Database::update('wb_templates', ['preview_image' => $preview], $id);
        }
        $this->snapshot($id, $manifest['version'], 'Initial version');
        return $id;
    }

    /** Create a ZIP snapshot of the template's current folder and record it as a version. */
    public function snapshot(int $templateId, string $version, ?string $changelog): int
    {
        $tpl = Database::fetch('SELECT * FROM wb_templates WHERE id = ?', [$templateId]);
        $folder = BASE_PATH . '/' . $tpl['folder_path'];
        $dir = self::BACKUP_DIR . '/' . $tpl['code'];
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create the template backup folder.');
        }
        $zip = $dir . '/v' . $version . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip';
        FileSystem::zipDir($folder, $zip);
        return Database::insert('wb_template_versions', [
            'template_id' => $templateId,
            'version' => $version,
            'manifest_hash' => hash_file('sha256', "$folder/manifest.json"),
            'changelog' => $changelog ? mb_substr($changelog, 0, 2000) : null,
            'archive_path' => substr($zip, strlen(BASE_PATH) + 1),
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    /** Replace a template's folder with $newRoot atomically (old folder kept until success). */
    public function swapFolder(string $target, string $newRoot): void
    {
        $old = null;
        if (is_dir($target)) {
            $old = $this->tempPath('old');
            $this->move($target, $old);
        }
        try {
            $this->move($newRoot, $target);
        } catch (\Throwable $e) {
            if ($old) {
                $this->move($old, $target);
            }
            throw $e;
        }
        if ($old) {
            FileSystem::deleteDir($old);
        }
    }

    public function move(string $from, string $to): void
    {
        $parent = dirname($to);
        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new RuntimeException('Could not create folder ' . $parent);
        }
        if (@rename($from, $to)) {
            return;
        }
        // Cross-device fallback
        FileSystem::copyDir($from, $to);
        FileSystem::deleteDir($from);
    }

    /** Copy preview.(jpg|png|webp) from a template folder into public uploads (re-encoded). */
    public function importPreviewImage(string $folder): ?string
    {
        foreach (['preview.jpg', 'preview.jpeg', 'preview.png', 'preview.webp'] as $f) {
            if (is_file("$folder/$f")) {
                $tmp = STORAGE_PATH . '/temporary/prev_' . bin2hex(random_bytes(6)) . '.' . pathinfo($f, PATHINFO_EXTENSION);
                copy("$folder/$f", $tmp);
                try {
                    return Uploader::image(['name' => $f, 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)], 'templates', 8_388_608, 6000, true);
                } catch (\Throwable) {
                    return null;
                } finally {
                    @unlink($tmp);
                }
            }
        }
        return null;
    }

    /** Write manifest.json back with changed keys (pretty-printed). */
    public function updateManifest(string $folder, array $changes): void
    {
        $file = "$folder/manifest.json";
        $m = json_decode((string) file_get_contents($file), true);
        if (!is_array($m)) {
            throw new RuntimeException('manifest.json could not be read.');
        }
        foreach ($changes as $k => $v) {
            $m[$k] = $v;
        }
        if (file_put_contents($file, json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX) === false) {
            throw new RuntimeException('manifest.json could not be written.');
        }
    }

    public function relativeFolder(string $abs): string
    {
        return ltrim(substr($abs, strlen(BASE_PATH)), '/');
    }

    public function absoluteFolder(array $tpl): string
    {
        $abs = FileSystem::within(TEMPLATES_PATH, BASE_PATH . '/' . $tpl['folder_path']);
        if ($abs === null) {
            throw new RuntimeException('Template folder path is invalid.');
        }
        return $abs;
    }

    /**
     * A unique path under storage/temporary that is removed automatically when the request ends,
     * even if the request terminates early (Response::fail/json call exit, which skips finally blocks).
     */
    public function tempPath(string $prefix): string
    {
        $path = STORAGE_PATH . '/temporary/' . $prefix . '_' . bin2hex(random_bytes(6));
        register_shutdown_function(function () use ($path): void {
            $this->cleanup($path);
        });
        return $path;
    }

    public function cleanup(?string $tmp): void
    {
        if ($tmp && str_starts_with($tmp, STORAGE_PATH . '/temporary/') && is_dir($tmp)) {
            FileSystem::deleteDir($tmp);
        }
    }
}
