<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class FileSystem
{
    /**
     * Resolve $path and return it only if it lies inside $base (prevents directory traversal).
     * Works for paths that do not exist yet by resolving the nearest existing parent.
     */
    public static function within(string $base, string $path): ?string
    {
        $realBase = realpath($base);
        if ($realBase === false) {
            return null;
        }
        $normalized = self::normalize($path);
        $probe = $normalized;
        $suffix = '';
        while (!file_exists($probe)) {
            $parent = dirname($probe);
            if ($parent === $probe) {
                return null;
            }
            $suffix = '/' . basename($probe) . $suffix;
            $probe = $parent;
        }
        $real = realpath($probe);
        if ($real === false) {
            return null;
        }
        $full = $real . $suffix;
        return ($full === $realBase || str_starts_with($full, $realBase . DIRECTORY_SEPARATOR)) ? $full : null;
    }

    /** Collapse "." and ".." segments without touching the filesystem. */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $absolute = str_starts_with($path, '/');
        $out = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($out);
                continue;
            }
            $out[] = $seg;
        }
        return ($absolute ? '/' : '') . implode('/', $out);
    }

    public static function copyDir(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            throw new RuntimeException("Source folder not found: $src");
        }
        if (!is_dir($dst) && !mkdir($dst, 0755, true) && !is_dir($dst)) {
            throw new RuntimeException("Could not create folder: $dst");
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            if ($item->isLink()) {
                continue; // never follow symlinks
            }
            $target = $dst . '/' . substr($item->getPathname(), strlen($src) + 1);
            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                    throw new RuntimeException("Could not create folder: $target");
                }
            } elseif (!copy($item->getPathname(), $target)) {
                throw new RuntimeException("Could not copy file: {$item->getPathname()}");
            }
        }
    }

    public static function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    /** Zip a folder into $zipPath (used for template version snapshots and exports). */
    public static function zipDir(string $dir, string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create ZIP archive.');
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && !$file->isLink()) {
                $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($dir) + 1));
            }
        }
        $zip->close();
    }

    /**
     * Safely extract a ZIP into $dest. Rejects absolute paths, ".." segments, symlinks,
     * too many files and oversized content (zip-bomb guard).
     */
    public static function safeExtract(string $zipPath, string $dest, int $maxFiles = 3000, int $maxBytes = 209_715_200): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new UploadException('The file is not a valid ZIP archive.');
        }
        if ($zip->numFiles > $maxFiles) {
            $zip->close();
            throw new UploadException("ZIP contains too many files (max $maxFiles).");
        }
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace('\\', '/', (string) $stat['name']);
            if ($name === '' || str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name) || preg_match('#^[a-zA-Z]:#', $name)) {
                $zip->close();
                throw new UploadException('ZIP contains an unsafe path: ' . $name);
            }
            // Reject symlinks (unix mode S_IFLNK in external attributes).
            if ($zip->getExternalAttributesIndex($i, $opsys, $attr) && $opsys === \ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0170000) === 0120000) {
                $zip->close();
                throw new UploadException('ZIP contains a symbolic link, which is not allowed.');
            }
            $total += (int) $stat['size'];
            if ($total > $maxBytes) {
                $zip->close();
                throw new UploadException('ZIP content is too large when extracted.');
            }
        }
        if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
            throw new RuntimeException('Could not create extraction folder.');
        }
        if (!$zip->extractTo($dest)) {
            $zip->close();
            throw new RuntimeException('Could not extract ZIP archive.');
        }
        $zip->close();
    }
}
