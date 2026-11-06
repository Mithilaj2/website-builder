<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\FileSystem;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Core\UploadException;

/**
 * Media library of one website. Files live in that website's own uploads/ folder and are
 * recorded in its own media table, so one client's images can never appear on another's site.
 */
final class MediaController extends SiteController
{
    private const MAX_BYTES = 8 * 1024 * 1024;
    private const MAX_DIM = 6000;

    public function list(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $db = $svc->db();
        $where = ['1=1'];
        $params = [];
        $folder = $request->str('folder');
        if (isset(SiteService::MEDIA_FOLDERS[$folder])) {
            $where[] = 'folder = :folder';
            $params['folder'] = $folder;
        }
        if ($q = $request->str('q')) {
            $where[] = '(original_name LIKE :q1 OR alt_text LIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . addcslashes($q, '%_\\') . '%';
        }
        $perPage = max(1, min(60, $request->int('per_page', 24)));
        $total = (int) $db->value('SELECT COUNT(*) FROM ' . $db->t('media') . ' WHERE ' . implode(' AND ', $where), $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $request->int('page', 1)), $pages);
        $rows = $db->fetchAll('SELECT * FROM ' . $db->t('media') . ' WHERE ' . implode(' AND ', $where) . " ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        foreach ($rows as &$r) {
            $r['url'] = $this->url($svc, $r['path']);
        }
        Response::json(['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage]);
    }

    public function upload(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $folder = $request->str('folder');
        if (!isset(SiteService::MEDIA_FOLDERS[$folder])) {
            $folder = 'general';
        }
        $files = $request->files('files');
        if (!$files) {
            Response::fail('Choose one or more images.', ['files' => 'Choose at least one image.']);
        }
        if (count($files) > 30) {
            Response::fail('Upload at most 30 files at a time.');
        }
        $saved = [];
        $errors = [];
        $db = $svc->db();
        foreach ($files as $f) {
            try {
                $stored = Uploader::storeImage($f, $svc->uploadsDir() . '/' . $folder, self::MAX_BYTES, self::MAX_DIM, false, true);
                $path = "uploads/$folder/" . $stored['name'];
                $alt = trim(preg_replace('/[_\-]+/', ' ', pathinfo((string) $f['name'], PATHINFO_FILENAME)) ?? '');
                $id = $db->insert('media', [
                    'folder' => $folder, 'original_name' => mb_substr((string) $f['name'], 0, 190), 'path' => $path,
                    'mime' => $stored['mime'], 'size' => $stored['size'], 'width' => $stored['width'], 'height' => $stored['height'],
                    'alt_text' => mb_substr($alt, 0, 255), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $saved[] = ['id' => $id, 'path' => $path, 'url' => $this->url($svc, $path)];
            } catch (UploadException $e) {
                $errors[] = $f['name'] . ': ' . $e->getMessage();
            }
        }
        if ($saved) {
            $this->touch($svc);
            Activity::log('website.media_uploaded', 'website', $svc->id(), count($saved) . " file(s) to $folder");
        }
        if (!$saved) {
            Response::fail(implode(' ', $errors), ['files' => implode(' ', $errors)]);
        }
        Response::ok(count($saved) . ' image(s) uploaded.' . ($errors ? ' Skipped: ' . implode(' ', $errors) : ''), ['items' => $saved, 'skipped' => $errors]);
    }

    public function update(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $m = $this->media($svc, (int) $request->params['mid']);
        $folder = $request->str('folder');
        $alt = mb_substr($request->str('alt_text'), 0, 255);
        $data = ['alt_text' => $alt, 'updated_at' => now()];
        if (isset(SiteService::MEDIA_FOLDERS[$folder])) {
            $data['folder'] = $folder; // logical grouping only; the file keeps its path so existing references stay valid
        }
        $svc->db()->update('media', $data, (int) $m['id']);
        Response::ok('Image details saved.');
    }

    /** Replace the file but keep the same path, so every place that uses the image updates. */
    public function replace(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $m = $this->media($svc, (int) $request->params['mid']);
        $file = $request->file('file');
        if (!$file) {
            Response::fail('Choose a replacement image.', ['file' => 'Choose an image.']);
        }
        $tmpDir = $svc->uploadsDir() . '/.replace_' . bin2hex(random_bytes(4));
        try {
            $stored = Uploader::storeImage($file, $tmpDir, self::MAX_BYTES, self::MAX_DIM, false, true);
            $oldExt = pathinfo($m['path'], PATHINFO_EXTENSION);
            $newExt = pathinfo($stored['name'], PATHINFO_EXTENSION);
            $path = $oldExt === $newExt ? $m['path'] : preg_replace('/\.[a-z0-9]+$/', '.' . $newExt, $m['path']);
            $target = FileSystem::within($svc->uploadsDir(), $svc->folder() . '/' . $path);
            if ($target === null) {
                throw new UploadException('Invalid media path.');
            }
            rename($tmpDir . '/' . $stored['name'], $target);
            if ($path !== $m['path']) {
                $old = FileSystem::within($svc->uploadsDir(), $svc->folder() . '/' . $m['path']);
                if ($old && is_file($old)) {
                    unlink($old);
                }
                $this->rewriteReferences($svc, $m['path'], $path);
            }
            $svc->db()->update('media', ['path' => $path, 'mime' => $stored['mime'], 'size' => $stored['size'], 'width' => $stored['width'], 'height' => $stored['height'], 'original_name' => mb_substr((string) $file['name'], 0, 190), 'updated_at' => now()], (int) $m['id']);
        } catch (UploadException $e) {
            Response::fail($e->getMessage(), ['file' => $e->getMessage()]);
        } finally {
            if (is_dir($tmpDir)) {
                FileSystem::deleteDir($tmpDir);
            }
        }
        $this->touch($svc);
        Response::ok('Image replaced everywhere it is used.', ['url' => $this->url($svc, $path) . '?v=' . time()]);
    }

    public function destroy(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $m = $this->media($svc, (int) $request->params['mid']);
        $usage = $svc->mediaUsage($m['path']);
        if ($usage && $request->str('force') !== '1') {
            Response::json(['ok' => false, 'in_use' => true, 'message' => 'This image is in use: ' . implode('; ', array_slice($usage, 0, 5)) . (count($usage) > 5 ? '…' : '') . '. Delete anyway? Those places will show no image.'], 409);
        }
        $file = FileSystem::within($svc->uploadsDir(), $svc->folder() . '/' . $m['path']);
        if ($file && is_file($file)) {
            unlink($file);
        }
        $svc->db()->delete('media', (int) $m['id']);
        if ($usage) {
            $this->rewriteReferences($svc, $m['path'], '');
        }
        $this->touch($svc);
        Activity::log('website.media_deleted', 'website', $svc->id(), $m['original_name'] ?: $m['path']);
        Response::ok('Image deleted.');
    }

    /** Serve a file from the website's uploads folder (builder preview only; login required). */
    public function serve(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $rel = (string) $request->params['path'];
        $file = FileSystem::within($svc->uploadsDir(), $svc->uploadsDir() . '/' . $rel);
        if ($file === null || !is_file($file) || str_contains($rel, '/.')) {
            Response::abort(404, 'File not found.');
        }
        AssetStreamer::send($file);
    }

    /** Replace an image reference inside sections and settings JSON (used by replace/delete). */
    private function rewriteReferences(SiteService $svc, string $from, string $to): void
    {
        $db = $svc->db();
        $needle = json_encode($from, JSON_UNESCAPED_SLASHES);
        $repl = json_encode($to, JSON_UNESCAPED_SLASHES);
        $like = '%' . addcslashes($needle, '%_\\') . '%';
        $db->query('UPDATE ' . $db->t('sections') . ' SET data = REPLACE(data, ?, ?), style = REPLACE(style, ?, ?) WHERE data LIKE ? OR style LIKE ?', [$needle, $repl, $needle, $repl, $like, $like]);
        $db->query('UPDATE ' . $db->t('settings') . ' SET setting_value = REPLACE(setting_value, ?, ?) WHERE setting_value LIKE ?', [$needle, $repl, $like]);
    }

    private function url(SiteService $svc, string $path): string
    {
        return url('websites/' . $svc->id() . '/media/' . substr($path, 8));
    }

    private function media(SiteService $svc, int $mid): array
    {
        $db = $svc->db();
        $m = $db->fetch('SELECT * FROM ' . $db->t('media') . ' WHERE id = ?', [$mid]);
        if (!$m) {
            Response::abort(404, 'Image not found.');
        }
        return $m;
    }
}
