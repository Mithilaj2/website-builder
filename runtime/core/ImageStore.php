<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Secure image storage, shared by the builder and every website's admin.
 * - extension allow-list + MIME sniffing (finfo) + getimagesize
 * - size and dimension limits
 * - raster images (JPG/PNG/WEBP/GIF) are re-encoded with GD, which strips any embedded payload
 * - SVG only where allowed, and only after SvgSanitizer
 * - random file names
 */
final class ImageStore
{
    public const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    /**
     * @return array{name:string, mime:string, size:int, width:?int, height:?int}
     * @throws ValidationError with a user-facing message
     */
    public static function store(array $file, string $absDir, int $maxBytes = 5_242_880, int $maxDim = 5000, bool $localFile = false, bool $allowSvg = false): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationError(self::uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        // $localFile: a file the server itself copied (e.g. a template's preview.jpg), not an HTTP upload.
        if (!$localFile && !is_uploaded_file((string) $file['tmp_name'])) {
            throw new ValidationError('Invalid upload.');
        }
        if ((int) $file['size'] > $maxBytes) {
            throw new ValidationError('File is too large. Maximum size is ' . round($maxBytes / 1048576, 1) . ' MB.');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowedExt = $allowSvg ? ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'] : ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExt, true)) {
            throw new ValidationError('Only ' . strtoupper(implode(', ', $allowedExt)) . ' images are allowed.');
        }
        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            throw new \RuntimeException('Could not create upload folder.');
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if ($ext === 'svg') {
            if (!in_array($mime, ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain', 'text/html'], true)) {
                throw new ValidationError('The file content is not a valid SVG image.');
            }
            $clean = SvgSanitizer::sanitize((string) file_get_contents($file['tmp_name']));
            if ($clean === null) {
                throw new ValidationError('The SVG could not be made safe and was rejected.');
            }
            $name = bin2hex(random_bytes(12)) . '.svg';
            file_put_contents($absDir . '/' . $name, $clean);
            @chmod($absDir . '/' . $name, 0644);
            return ['name' => $name, 'mime' => 'image/svg+xml', 'size' => strlen($clean), 'width' => null, 'height' => null];
        }

        if (!isset(self::TYPES[$mime])) {
            throw new ValidationError('The file content is not a valid ' . strtoupper(implode(', ', array_diff($allowedExt, ['svg']))) . ' image.');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1) {
            throw new ValidationError('The image could not be read.');
        }
        if ($info[0] > $maxDim || $info[1] > $maxDim) {
            throw new ValidationError("Image dimensions may not exceed {$maxDim}×{$maxDim} pixels.");
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
            if (!copy($src, $target)) {
                throw new \RuntimeException('Could not store the uploaded file.');
            }
            return;
        }
        $img = @imagecreatefromstring((string) file_get_contents($src));
        if ($img === false) {
            throw new ValidationError('The image could not be processed.');
        }
        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagesavealpha($img, true);
        }
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($img, $target, 88),
            'image/png' => imagepng($img, $target, 6),
            'image/webp' => imagewebp($img, $target, 85),
            'image/gif' => imagegif($img, $target),
        };
        imagedestroy($img);
        if (!$ok) {
            throw new \RuntimeException('Could not save the processed image.');
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
