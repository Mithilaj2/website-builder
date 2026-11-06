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
 * SVG is not accepted here (sanitized SVG support arrives with the media manager in Phase 2).
 */
final class Uploader
{
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public const UPLOAD_ROOT = PUBLIC_PATH . '/uploads';

    /**
     * @return string path relative to public/, e.g. "uploads/categories/ab12.jpg"
     */
    public static function image(array $file, string $folder, int $maxBytes = 5_242_880, int $maxDim = 5000, bool $localFile = false): string
    {
        if (!preg_match('#^[a-z0-9_\-/]+$#', $folder) || str_contains($folder, '..')) {
            throw new RuntimeException('Invalid upload folder.');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new UploadException(self::uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        // $localFile: a file the server itself copied (e.g. a template's preview.jpg), not an HTTP upload.
        if (!$localFile && !is_uploaded_file($file['tmp_name'])) {
            throw new UploadException('Invalid upload.');
        }
        if ($file['size'] > $maxBytes) {
            throw new UploadException('Image is too large. Maximum size is ' . round($maxBytes / 1048576, 1) . ' MB.');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new UploadException('Only JPG, PNG and WEBP images are allowed.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime])) {
            throw new UploadException('The file content is not a valid JPG, PNG or WEBP image.');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1) {
            throw new UploadException('The image could not be read.');
        }
        if ($info[0] > $maxDim || $info[1] > $maxDim) {
            throw new UploadException("Image dimensions may not exceed {$maxDim}×{$maxDim} pixels.");
        }

        $dir = self::UPLOAD_ROOT . '/' . trim($folder, '/');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create upload folder.');
        }
        $outExt = self::TYPES[$mime];
        $name = bin2hex(random_bytes(12)) . '.' . $outExt;
        $target = $dir . '/' . $name;

        self::reencode($file['tmp_name'], $mime, $target);
        @chmod($target, 0644);
        return 'uploads/' . trim($folder, '/') . '/' . $name;
    }

    private static function reencode(string $src, string $mime, string $target): void
    {
        if (!function_exists('imagecreatefromstring')) {
            // GD missing: fall back to a plain move (still validated above).
            if (!copy($src, $target)) {
                throw new RuntimeException('Could not store the uploaded file.');
            }
            return;
        }
        $img = @imagecreatefromstring((string) file_get_contents($src));
        if ($img === false) {
            throw new UploadException('The image could not be processed.');
        }
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($img, $target, 88),
            'image/png' => (static function () use ($img, $target) {
                imagesavealpha($img, true);
                return imagepng($img, $target, 6);
            })(),
            'image/webp' => (static function () use ($img, $target) {
                imagesavealpha($img, true);
                return imagewebp($img, $target, 85);
            })(),
        };
        imagedestroy($img);
        if (!$ok) {
            throw new RuntimeException('Could not save the processed image.');
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
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file exceeds the server upload limit (' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not save the upload.',
            default => 'Upload failed.',
        };
    }
}
