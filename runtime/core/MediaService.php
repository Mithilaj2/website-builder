<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * A website's media library: files in its own uploads/ folder, recorded in its own media table.
 * Shared by the builder and the website's admin panel.
 */
final class MediaService
{
    public const FOLDERS = ['logo' => 'Logo & favicon', 'banners' => 'Banners', 'gallery' => 'Gallery', 'pages' => 'Page images', 'team' => 'Team & people', 'general' => 'General'];
    public const MAX_BYTES = 8 * 1024 * 1024;
    public const MAX_DIM = 6000;

    /**
     * @param string   $siteRoot  folder that contains uploads/
     * @param callable(string):string $urlFor  public URL for a stored path ("uploads/…")
     * @param callable(string):string $sectionLabel  label for a section type (for "in use" messages)
     */
    public function __construct(private Db $db, private string $siteRoot, private $urlFor, private $sectionLabel)
    {
    }

    public function uploadsDir(): string
    {
        return rtrim($this->siteRoot, '/') . '/uploads';
    }

    public function list(string $folder, string $q, int $page, int $perPage): array
    {
        $where = ['1=1'];
        $params = [];
        if (isset(self::FOLDERS[$folder])) {
            $where[] = 'folder = :folder';
            $params['folder'] = $folder;
        }
        if ($q !== '') {
            $where[] = '(original_name LIKE :q1 OR alt_text LIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . addcslashes($q, '%_\\') . '%';
        }
        $perPage = max(1, min(60, $perPage));
        $t = $this->db->t('media');
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE " . implode(' AND ', $where), $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $rows = $this->db->fetchAll("SELECT * FROM $t WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        foreach ($rows as &$r) {
            $r['url'] = ($this->urlFor)($r['path']);
        }
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @return array{0: list<array>, 1: list<string>} [saved items, skipped messages] */
    public function upload(array $files, string $folder): array
    {
        $folder = isset(self::FOLDERS[$folder]) ? $folder : 'general';
        if (!$files) {
            throw new ValidationError('Choose one or more images.', ['files' => 'Choose at least one image.']);
        }
        if (count($files) > 30) {
            throw new ValidationError('Upload at most 30 files at a time.');
        }
        $saved = [];
        $skipped = [];
        foreach ($files as $f) {
            try {
                $stored = ImageStore::store($f, $this->uploadsDir() . '/' . $folder, self::MAX_BYTES, self::MAX_DIM, false, true);
                $path = "uploads/$folder/" . $stored['name'];
                $alt = trim((string) preg_replace('/[_\-]+/', ' ', pathinfo((string) $f['name'], PATHINFO_FILENAME)));
                $id = $this->db->insert('media', [
                    'folder' => $folder, 'original_name' => mb_substr((string) $f['name'], 0, 190), 'path' => $path,
                    'mime' => $stored['mime'], 'size' => $stored['size'], 'width' => $stored['width'], 'height' => $stored['height'],
                    'alt_text' => mb_substr($alt, 0, 255), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $saved[] = ['id' => $id, 'path' => $path, 'url' => ($this->urlFor)($path)];
            } catch (ValidationError $e) {
                $skipped[] = $f['name'] . ': ' . $e->getMessage();
            }
        }
        if (!$saved) {
            throw new ValidationError(implode(' ', $skipped), ['files' => implode(' ', $skipped)]);
        }
        return [$saved, $skipped];
    }

    public function get(int $id): array
    {
        $m = $this->db->fetch('SELECT * FROM ' . $this->db->t('media') . ' WHERE id = ?', [$id]);
        if (!$m) {
            throw new NotFound('Image not found.');
        }
        return $m;
    }

    public function update(int $id, string $alt, string $folder): void
    {
        $this->get($id);
        $data = ['alt_text' => mb_substr(trim(strip_tags($alt)), 0, 255), 'updated_at' => date('Y-m-d H:i:s')];
        if (isset(self::FOLDERS[$folder])) {
            $data['folder'] = $folder; // logical grouping only; the file keeps its path so references stay valid
        }
        $this->db->update('media', $data, $id);
    }

    /** Replace the file, keeping the path (so every use updates). Returns the new URL. */
    public function replace(int $id, ?array $file): string
    {
        $m = $this->get($id);
        if (!$file) {
            throw new ValidationError('Choose a replacement image.', ['file' => 'Choose an image.']);
        }
        $tmpDir = $this->uploadsDir() . '/.replace_' . bin2hex(random_bytes(4));
        try {
            $stored = ImageStore::store($file, $tmpDir, self::MAX_BYTES, self::MAX_DIM, false, true);
            $newExt = pathinfo($stored['name'], PATHINFO_EXTENSION);
            $path = pathinfo($m['path'], PATHINFO_EXTENSION) === $newExt ? $m['path'] : (string) preg_replace('/\.[a-z0-9]+$/', '.' . $newExt, $m['path']);
            $target = $this->absolute($path);
            if ($target === null) {
                throw new ValidationError('Invalid media path.');
            }
            rename($tmpDir . '/' . $stored['name'], $target);
            if ($path !== $m['path']) {
                $old = $this->absolute($m['path']);
                if ($old && is_file($old)) {
                    unlink($old);
                }
                $this->rewriteReferences($m['path'], $path);
            }
            $this->db->update('media', ['path' => $path, 'mime' => $stored['mime'], 'size' => $stored['size'], 'width' => $stored['width'], 'height' => $stored['height'],
                'original_name' => mb_substr((string) $file['name'], 0, 190), 'updated_at' => date('Y-m-d H:i:s')], $id);
        } finally {
            if (is_dir($tmpDir)) {
                array_map('unlink', glob($tmpDir . '/*') ?: []);
                @rmdir($tmpDir);
            }
        }
        return ($this->urlFor)($path) . '?v=' . time();
    }

    /** Delete an image. If it is in use and $force is false, throws ValidationError with extra['in_use']. */
    public function delete(int $id, bool $force): array
    {
        $m = $this->get($id);
        $usage = $this->usage($m['path']);
        if ($usage && !$force) {
            throw new ValidationError(
                'This image is in use: ' . implode('; ', array_slice($usage, 0, 5)) . (count($usage) > 5 ? '…' : '') . '. Delete anyway? Those places will show no image.',
                [],
                ['in_use' => true]
            );
        }
        $file = $this->absolute($m['path']);
        if ($file && is_file($file)) {
            unlink($file);
        }
        $this->db->delete('media', $id);
        if ($usage) {
            $this->rewriteReferences($m['path'], '');
        }
        return $m;
    }

    /** Where a file is used (sections and settings), as readable labels. */
    public function usage(string $path): array
    {
        $like = '%' . addcslashes((string) json_encode($path, JSON_UNESCAPED_SLASHES), '%_\\') . '%';
        $out = [];
        foreach ($this->db->fetchAll('SELECT s.section_type, p.title FROM ' . $this->db->t('sections') . ' s JOIN ' . $this->db->t('pages') . ' p ON p.id = s.page_id WHERE s.data LIKE ? OR s.style LIKE ?', [$like, $like]) as $r) {
            $out[] = $r['title'] . ' → ' . ($this->sectionLabel)($r['section_type']);
        }
        foreach ($this->db->fetchAll('SELECT setting_key FROM ' . $this->db->t('settings') . ' WHERE setting_value LIKE ?', [$like]) as $r) {
            $out[] = 'Settings → ' . $r['setting_key'];
        }
        return $out;
    }

    /** True if $ref is a stored upload of this site, or an existing file in the template's assets. */
    public static function imageValidator(Db $db, string $themeDir): callable
    {
        return static function (string $ref) use ($db, $themeDir): bool {
            if (str_starts_with($ref, 'theme:')) {
                $rel = substr($ref, 6);
                return !str_contains($rel, '..') && is_file(rtrim($themeDir, '/') . '/assets/' . $rel);
            }
            if (str_starts_with($ref, 'uploads/') && !str_contains($ref, '..')) {
                return (int) $db->value('SELECT COUNT(*) FROM ' . $db->t('media') . ' WHERE path = ?', [$ref]) > 0;
            }
            return false;
        };
    }

    /** Absolute path of a stored file, only if it stays inside uploads/. */
    public function absolute(string $path): ?string
    {
        if (!str_starts_with($path, 'uploads/') || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }
        $real = realpath($this->uploadsDir());
        $dir = realpath(dirname($this->siteRoot . '/' . $path));
        if ($real === false || $dir === false || ($dir !== $real && !str_starts_with($dir, $real . DIRECTORY_SEPARATOR))) {
            return null;
        }
        return $dir . '/' . basename($path);
    }

    private function rewriteReferences(string $from, string $to): void
    {
        $needle = (string) json_encode($from, JSON_UNESCAPED_SLASHES);
        $repl = (string) json_encode($to, JSON_UNESCAPED_SLASHES);
        $like = '%' . addcslashes($needle, '%_\\') . '%';
        $this->db->query('UPDATE ' . $this->db->t('sections') . ' SET data = REPLACE(data, ?, ?), style = REPLACE(style, ?, ?) WHERE data LIKE ? OR style LIKE ?', [$needle, $repl, $needle, $repl, $like, $like]);
        $this->db->query('UPDATE ' . $this->db->t('settings') . ' SET setting_value = REPLACE(setting_value, ?, ?) WHERE setting_value LIKE ?', [$needle, $repl, $like]);
    }
}
