<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok(string $message = 'Saved.', array $extra = []): never
    {
        self::json(['ok' => true, 'message' => $message] + $extra);
    }

    public static function fail(string $message, array $errors = [], int $status = 422): never
    {
        self::json(['ok' => false, 'message' => $message, 'errors' => (object) $errors], $status);
    }

    public static function redirect(string $to): never
    {
        header('Location: ' . (preg_match('#^https?://#', $to) ? $to : url($to)));
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        $req = new Request();
        if ($req->isAjax()) {
            self::json(['ok' => false, 'message' => $message ?: 'Request failed.'], $status);
        }
        $titles = [403 => 'Access denied', 404 => 'Page not found', 405 => 'Method not allowed', 429 => 'Too many requests'];
        View::render('errors/error', ['code' => $status, 'title' => $titles[$status] ?? 'Error', 'message' => $message], Auth::check() ? 'main' : 'auth');
        exit;
    }

    public static function securityHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; frame-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
    }
}
