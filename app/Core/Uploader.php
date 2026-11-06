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
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
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
        $stored = self::storeImage($file, self::UPLOAD_ROOT . '/' . trim($folder, '/'), $maxBytes, $maxDim, $localFile);
        return 'uploads/' . trim($folder, '/') . '/' . $stored['name'];
    }

    /**
     * Validate and store an image in $absDir under a random name.
     * Raster images (JPG/PNG/WEBP/GIF) are re-encoded with GD; SVG (only if $allowSvg) is sanitised.
     *
     * @return array{name:string, mime:string, size:int, width:?int, height:?int}
     */
    public static function storeImage(array $file, string $absDir, int $maxBytes = 5_242_880, int $maxDim = 5000, bool $localFile = false, bool $allowSvg = false): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new UploadException(self::uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        // $localFile: a file the server itself copied (e.g. a template's preview.jpg), not an HTTP upload.
        if (!$localFile && !is_uploaded_file($file['tmp_name'])) {
            throw new UploadException('Invalid upload.');
        }
        if ($file['size'] > $maxBytes) {
            throw new UploadException('File is too large. Maximum size is ' . round($maxBytes / 1048576, 1) . ' MB.');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowedExt = $allowSvg ? ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'] : ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExt, true)) {
            throw new UploadException('Only ' . strtoupper(implode(', ', $allowedExt)) . ' images are allowed.');
        }
        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            throw new RuntimeException('Could not create upload folder.');
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if ($ext === 'svg') {
            if (!in_array($mime, ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain', 'text/html'], true)) {
                throw new UploadException('The file content is not a valid SVG image.');
            }
            $clean = \SiteRuntime\SvgSanitizer::sanitize((string) file_get_contents($file['tmp_name']));
            if ($clean === null) {
                throw new UploadException('The SVG could not be made safe and was rejected.');
            }
            $name = bin2hex(random_bytes(12)) . '.svg';
            file_put_contents($absDir . '/' . $name, $clean);
            @chmod($absDir . '/' . $name, 0644);
            return ['name' => $name, 'mime' => 'image/svg+xml', 'size' => strlen($clean), 'width' => null, 'height' => null];
        }

        if (!isset(self::TYPES[$mime])) {
            throw new UploadException('The file content is not a valid ' . strtoupper(implode(', ', array_diff($allowedExt, ['svg']))) . ' image.');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1) {
            throw new UploadException('The image could not be read.');
        }
        if ($info[0] > $maxDim || $info[1] > $maxDim) {
            throw new UploadException("Image dimensions may not exceed {$maxDim}×{$maxDim} pixels.");
        }
        $name = bin2hex(random_bytes(12)) . '.' . self::TYPES[$mime];
        $target = $absDir . '/' . $name;
        self::reencode($file['tmp_name'], $mime, $target);
        @chmod($target, 0644);
        return ['name' => $name, 'mime' => $mime, 'size' => (int) filesize($target), 'width' => (int) $info[0], 'height' => (int) $info[1]];
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
            'image/gif' => imagegif($img, $target),
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
