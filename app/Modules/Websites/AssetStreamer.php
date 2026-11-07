<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Response;

/** Streams static files (template assets, fonts, libraries, site media) for builder previews. */
final class AssetStreamer
{
    private const TYPES = [
        'css' => 'text/css; charset=utf-8', 'js' => 'application/javascript; charset=utf-8',
        'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'ico' => 'image/x-icon',
        'woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf', 'json' => 'application/json',
    ];

    public static function send(string $file): never
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext])) {
            Response::abort(404, 'File not found.');
        }
        $mtime = (int) filemtime($file);
        $etag = '"' . md5($file . $mtime . filesize($file)) . '"';
        header('Content-Type: ' . self::TYPES[$ext]);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=300');
        header('ETag: ' . $etag);
        if ($ext === 'svg') {
            // An SVG opened directly must not be able to run script.
            header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
