<?php
declare(strict_types=1);

namespace SiteRuntime;

use PDO;

/**
 * Front controller of a generated website. Works on its own: it needs only this site's
 * folder (core/, theme/, assets/, uploads/, config/) and its own database.
 */
final class Site
{
    private string $root;
    private array $app;
    private array $dbConfig;
    private ?Db $db = null;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
    }

    public static function run(string $root): void
    {
        (new self($root))->handle();
    }

    public function handle(): void
    {
        $this->securityHeaders();
        try {
            $app = $this->config('app');
            $db = $this->config('database');
            if (!$app || !$db || empty($db['name'])) {
                $this->notConfigured();
                return;
            }
            $this->app = $app;
            $this->dbConfig = $db;
            date_default_timezone_set((string) ($app['timezone'] ?? 'Asia/Kolkata'));
            ini_set('display_errors', !empty($app['debug']) ? '1' : '0');

            $path = $this->path();
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (preg_match('#^form/([a-z0-9_-]{1,40})$#', $path, $m)) {
                    (new FormHandler($this->db(), (string) $app['key'], $this->baseUrl(), !empty($app['clean_urls'])))->handle($m[1]);
                    return;
                }
                $this->status(405, 'Method not allowed');
                return;
            }
            $this->render($path);
        } catch (\Throwable $e) {
            $this->log('ERROR', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->status(500, 'This website is temporarily unavailable. Please try again shortly.');
        }
    }

    private function render(string $path): void
    {
        $manifest = json_decode((string) file_get_contents($this->root . '/theme/manifest.json'), true) ?: [];
        $flash = [];
        $form = (string) ($_GET['form'] ?? '');
        if (preg_match('/^[a-z0-9_-]{1,40}$/', $form) && in_array($_GET['status'] ?? '', ['sent', 'error'], true)) {
            $sent = $_GET['status'] === 'sent';
            $reasons = [
                'invalid' => 'Please fill in your name, a valid email address and your message.',
                'expired' => 'The form expired. Please send your message again.',
                'limit' => 'Too many messages were sent from your connection. Please try again later.',
            ];
            $flash[$form] = [
                'status' => $sent ? 'sent' : 'error',
                'message' => $sent ? 'Thank you! Your message has been sent. We will get back to you soon.' : ($reasons[$_GET['reason'] ?? ''] ?? 'Your message could not be sent. Please try again.'),
            ];
        }
        $renderer = new Renderer(
            new DbSource($this->db()),
            $this->root . '/theme',
            $manifest,
            $this->urls(),
            ['fonts' => $this->root . '/assets/fonts', 'lib' => $this->root . '/assets/lib'],
            false,
            $flash
        );
        $slug = $path;
        if ($path === '' && isset($_GET['page']) && is_string($_GET['page'])) {
            $slug = trim($_GET['page'], '/'); // works without mod_rewrite
        }
        $out = $renderer->render($slug);
        http_response_code($out['status']);
        header('Content-Type: text/html; charset=utf-8');
        echo $out['html'];
    }

    /** URL patterns for this site, based on where it is installed. */
    private function urls(): array
    {
        $base = $this->baseUrl();
        $clean = !empty($this->app['clean_urls']);
        return [
            'home' => $base . '/',
            'page' => $clean ? $base . '/{slug}' : $base . '/index.php?page={slug}',
            'theme_asset' => $base . '/theme/assets/{path}',
            'upload' => $base . '/uploads/{path}',
            'font' => $base . '/assets/fonts/{path}',
            'lib' => $base . '/assets/lib/{path}',
            'form' => $clean ? $base . '/form/{form}' : $base . '/index.php/form/{form}',
            'form_key' => (string) $this->app['key'],
        ];
    }

    /** Path of the site relative to the web root, e.g. "" or "/clientsite" (subfolder installs). */
    private function basePath(): string
    {
        $configured = (string) ($this->app['base_path'] ?? '');
        if ($configured !== '') {
            return rtrim($configured, '/');
        }
        return rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
    }

    private function baseUrl(): string
    {
        return $this->basePath();
    }

    /** Request path relative to the site, without leading/trailing slashes. */
    private function path(): string
    {
        $uri = rawurldecode((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'));
        $base = $this->basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, 10);
        }
        return trim($uri, '/');
    }

    private function db(): Db
    {
        if ($this->db === null) {
            $c = $this->dbConfig;
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'] ?? 'localhost', (int) ($c['port'] ?? 3306), $c['name']),
                (string) ($c['user'] ?? ''),
                (string) ($c['pass'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
            );
            $this->db = new Db($pdo, (string) ($c['prefix'] ?? ''));
        }
        return $this->db;
    }

    private function config(string $name): ?array
    {
        $file = $this->root . '/config/' . $name . '.php';
        if (!is_file($file)) {
            return null;
        }
        $v = require $file;
        return is_array($v) ? $v : null;
    }

    private function notConfigured(): void
    {
        if (is_file($this->root . '/install.php')) {
            header('Location: ' . $this->basePath() . '/install.php');
            return;
        }
        $this->status(503, 'This website has not been configured yet.');
    }

    private function status(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</title></head><body style="font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:90vh;color:#334155">'
            . '<div style="text-align:center;max-width:520px;padding:1rem"><h1 style="font-size:1.4rem">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</h1></div></body></html>';
    }

    private function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    private function log(string $level, string $message): void
    {
        $dir = $this->root . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . '/site-' . date('Y-m-d') . '.log', '[' . date('Y-m-d H:i:s') . "] $level: $message\n", FILE_APPEND | LOCK_EX);
    }
}
