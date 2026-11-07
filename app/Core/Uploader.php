<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Secure image uploads for builder assets (category images, template previews).
 * - extension allow-list + MIME sniffing with finfo + getimagesize
 * - size and dimension limits
 * - raster images are re-encoded with GD, which strips any embedded payload
 * - random file names; target directory must be inside public/uploads
 * SVG is accepted only where explicitly allowed (site media library) and is sanitised first.
 */
final class Uploader
{
    public const UPLOAD_ROOT = PUBLIC_PATH . '/uploads';

    /**
     * @return string path relative to public/, e.g. "uploads/categories/ab12.jpg"
     */
    public static function image(array $file, string $folder, int $maxBytes = 5_242_880, int $maxDim = 5000, bool $localFile = false): string
    {
        if (!preg_match('#^[a-z0-9_\-/]+$#', $folder) || str_contains($folder, '..')) {
            throw new RuntimeException('Invalid upload folder.');
        }
        $stored = self::storeImage($file, self::UPLOAD_ROOT . '/' . trim($folder, '/'), $maxBytes, $maxDim, $localFile);
        return 'uploads/' . trim($folder, '/') . '/' . $stored['name'];
    }

    /**
     * Validate and store an image in $absDir under a random name (shared implementation: SiteRuntime\ImageStore).
     *
     * @return array{name:string, mime:string, size:int, width:?int, height:?int}
     */
    public static function storeImage(array $file, string $absDir, int $maxBytes = 5_242_880, int $maxDim = 5000, bool $localFile = false, bool $allowSvg = false): array
    {
        try {
            return \SiteRuntime\ImageStore::store($file, $absDir, $maxBytes, $maxDim, $localFile, $allowSvg);
        } catch (\SiteRuntime\ValidationError $e) {
            throw new UploadException($e->getMessage());
        }
    }

    /** Delete a previously uploaded file given its public-relative path. Ignores paths outside uploads/. */
    public static function delete(?string $relative): void
    {
        if (!$relative || !str_starts_with($relative, 'uploads/')) {
            return;
        }
        $path = FileSystem::within(PUBLIC_PATH . '/uploads', PUBLIC_PATH . '/' . $relative);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    public static function uploadErrorMessage(int $code): string
    {
        return \SiteRuntime\ImageStore::uploadErrorMessage($code);
    }
}
