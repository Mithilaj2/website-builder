<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public array $params = [];

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Path relative to the configured base URL, without query string, e.g. "categories/5". */
    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);
        $basePath = rtrim((string) parse_url((string) Config::get('app.base_url', ''), PHP_URL_PATH), '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        // Support hosts without mod_rewrite: /index.php/route or ?r=route
        if (str_starts_with($uri, '/public/')) {
            $uri = substr($uri, 7);
        }
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, 10);
        }
        if (($uri === '' || $uri === '/') && isset($_GET['r'])) {
            $uri = (string) $_GET['r'];
        }
        return trim($uri, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function all(): array
    {
        return array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $_POST);
    }

    public function file(string $key): ?array
    {
        $f = $_FILES[$key] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    /** Normalised list of uploaded files for a multi-file input (name="files[]"). */
    public function files(string $key): array
    {
        $f = $_FILES[$key] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return [];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }

    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public function ip(): string
    {
        // REMOTE_ADDR only: proxy headers can be spoofed on shared hosting.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }
}
