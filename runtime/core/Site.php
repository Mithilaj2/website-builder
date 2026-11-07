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

            if (is_file($this->root . '/vendor/autoload.php')) {
                require_once $this->root . '/vendor/autoload.php';
            }
            $path = $this->path();
            if ($path === 'admin' || str_starts_with($path, 'admin/')) {
                (new Admin\App($this->root, $this->db(), $app, $this->baseUrl()))->handle(substr($path, 6), $this);
                return;
            }
            if ($path === 'pay' || str_starts_with($path, 'pay/') || ($path === '' && ($_GET['page'] ?? '') === 'pay')) {
                $this->payments(substr($path, 4));
                return;
            }
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (preg_match('#^form/([a-z0-9_-]{1,40})$#', $path, $m)) {
                    (new FormHandler($this->db(), (string) $app['key'], $this->baseUrl(), !empty($app['clean_urls']), fn (array $row) => $this->notify($row)))->handle($m[1]);
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
        $renderer = $this->renderer($manifest, $flash);
        $slug = $path;
        if ($path === '' && isset($_GET['page']) && is_string($_GET['page'])) {
            $slug = trim($_GET['page'], '/'); // works without mod_rewrite
        }
        $out = $renderer->render($slug);
        http_response_code($out['status']);
        header('Content-Type: text/html; charset=utf-8');
        echo $out['html'];
    }

    private function renderer(array $manifest, array $flash = []): Renderer
    {
        return new Renderer(
            new DbSource($this->db()),
            $this->root . '/theme',
            $manifest,
            $this->urls(),
            ['fonts' => $this->root . '/assets/fonts', 'lib' => $this->root . '/assets/lib'],
            false,
            $flash
        );
    }

    /** The "Pay online" pages and gateway callbacks (/pay…). */
    private function payments(string $sub): void
    {
        $manifest = json_decode((string) file_get_contents($this->root . '/theme/manifest.json'), true) ?: [];
        $settings = (new DbSource($this->db()))->settings();
        $base = $this->baseUrl();
        $clean = !empty($this->app['clean_urls']);
        $crypto = new Crypto((string) $this->app['key']);
        // A local imitation of the gateways for automated tests; only ever used in test mode.
        $emulator = isset($this->app['payment_emulator']) && is_string($this->app['payment_emulator']) && $this->app['payment_emulator'] !== '' ? $this->app['payment_emulator'] : null;
        $svc = new Payments\PaymentService($this->db(), $crypto, $this->config('payment') ?? [], $emulator);
        $version = is_file($this->root . '/VERSION') ? substr(md5((string) file_get_contents($this->root . '/VERSION')), 0, 8) : '1';
        $theme = array_replace((array) ($manifest['theme']['defaults'] ?? []), (array) ($settings['theme'] ?? []));
        $page = new Payments\PaymentPage($svc, $this->renderer($manifest), [
            'site_name' => (string) ($settings['site']['name'] ?? $this->app['name'] ?? 'Website'),
            'color' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($theme['primary'] ?? '')) ? $theme['primary'] : '#2E3192',
            'key' => (string) $this->app['key'],
            'abs_base' => $this->origin(),
            'url' => static fn (string $s): string => $base . ($clean ? '/pay' : '/index.php/pay') . ($s !== '' ? '/' . $s : ''),
            'asset' => static fn (string $p): string => $base . '/assets/' . $p . '?v=' . rawurlencode($version),
        ], fn (array $tx) => $this->paymentEmails($tx, $svc, $settings, $crypto), fn (string $level, string $msg) => $this->log($level, $msg));
        $page->handle($sub, (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    /** Scheme and host of the current request, e.g. https://example.com (for addresses given to gateways). */
    private function origin(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (!preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/i', $host)) {
            $host = 'localhost';
        }
        return ($https ? 'https' : 'http') . '://' . $host;
    }

    /** Receipt to the payer and a notification to the owner, once per payment. Never throws. */
    private function paymentEmails(array $tx, Payments\PaymentService $svc, array $settings, Crypto $crypto): void
    {
        if ((int) ($tx['emails_sent'] ?? 0) === 1) {
            return;
        }
        try {
            $mailer = Mailer::fromSettings($settings, $crypto, (string) ($_SERVER['HTTP_HOST'] ?? ''));
            $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
            $amount = '₹' . Payments\PaymentService::inr((int) $tx['amount']);
            $site = (string) ($settings['site']['name'] ?? 'our website');
            $test = $tx['mode'] === 'test' ? '<p style="background:#fef3c7;padding:8px 12px;border-radius:6px">Test mode: no real money was charged.</p>' : '';
            $rows = '';
            foreach (['Amount' => $amount, 'For' => $tx['purpose'], 'Reference' => $tx['reference'], 'Payment ID' => $tx['gateway_payment_id'], 'Paid on' => date('d M Y, h:i A', strtotime((string) $tx['paid_at']))] as $k => $v) {
                if ((string) $v !== '') {
                    $rows .= '<tr><td style="padding:6px 16px 6px 0;color:#6b7280">' . $k . '</td><td style="padding:6px 0"><strong>' . $e($v) . '</strong></td></tr>';
                }
            }
            $sent = false;
            {
                $mailer->send([$tx['customer_email']], 'Payment receipt ' . $tx['reference'] . ' — ' . $site,
                    $mailer->wrap('Payment received', '<p>Dear ' . $e($tx['customer_name']) . ',</p><p>Thank you. We have received your payment.</p><table role="presentation">' . $rows . '</table>' . $test . '<p style="color:#6b7280;font-size:13px">Please keep this email for your records. Reply to it if you have any questions.</p>'),
                    "Payment received\n\nAmount: $amount\nFor: {$tx['purpose']}\nReference: {$tx['reference']}\nPayment ID: {$tx['gateway_payment_id']}", $mailer->notifyTo()[0] ?? null);
                $sent = true;
                if ($mailer->notifyTo()) {
                    $mailer->send($mailer->notifyTo(), 'Payment received: ' . $amount . ' from ' . $tx['customer_name'] . ($tx['mode'] === 'test' ? ' (test)' : ''),
                        $mailer->wrap('New payment: ' . $amount, '<table role="presentation">' . $rows . '<tr><td style="padding:6px 16px 6px 0;color:#6b7280">From</td><td><strong>' . $e($tx['customer_name']) . '</strong><br>' . $e($tx['customer_email']) . ' · ' . $e($tx['customer_phone']) . '</td></tr></table>'
                            . ($tx['note'] ? '<p style="background:#f4f6fb;padding:12px;border-radius:6px">' . $e($tx['note']) . '</p>' : '') . $test . '<p style="color:#6b7280;font-size:13px">All payments are listed in your website admin under Payments.</p>'),
                        "New payment: $amount\nFrom: {$tx['customer_name']} ({$tx['customer_email']}, {$tx['customer_phone']})\nFor: {$tx['purpose']}\nReference: {$tx['reference']}", $tx['customer_email']);
                }
            }
            if ($sent) {
                $svc->markEmailed((int) $tx['id']);
            }
        } catch (\Throwable $ex) {
            $this->log('WARNING', 'Payment ' . $tx['reference'] . ' received, but an email failed: ' . $ex->getMessage());
        }
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

    public function status(int $code, string $message): void
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

    /** Email the website owner about a new enquiry. Never throws: the enquiry is already stored. */
    private function notify(array $row): void
    {
        try {
            $settings = (new DbSource($this->db()))->settings();
            $mailer = Mailer::fromSettings($settings, new Crypto((string) $this->app['key']), (string) ($_SERVER['HTTP_HOST'] ?? ''));
            if (!$mailer->configured()) {
                return;
            }
            $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
            $rows = '';
            foreach (['Name' => $row['name'], 'Email' => $row['email'], 'Phone' => $row['phone'], 'Subject' => $row['subject'], 'Page' => $row['page_slug'] ? '/' . $row['page_slug'] : 'Home'] as $k => $v) {
                if ((string) $v !== '') {
                    $rows .= '<tr><td style="padding:6px 12px 6px 0;color:#6b7280">' . $k . '</td><td style="padding:6px 0"><strong>' . $e($v) . '</strong></td></tr>';
                }
            }
            $html = $mailer->wrap('New enquiry from ' . $row['name'], '<table role="presentation">' . $rows . '</table><p style="white-space:pre-line;background:#f4f6fb;padding:12px;border-radius:6px">' . $e($row['message']) . '</p>'
                . '<p style="color:#6b7280;font-size:13px">Reply to this email to answer the visitor. All enquiries are also saved in your website admin under Enquiries.</p>');
            $text = "New enquiry\n\nName: {$row['name']}\nEmail: {$row['email']}\nPhone: {$row['phone']}\nSubject: {$row['subject']}\n\n{$row['message']}";
            $mailer->send($mailer->notifyTo(), 'New enquiry: ' . ($row['subject'] ?: $row['name']), $html, $text, $row['email']);
            $this->db()->update('contact_submissions', ['email_sent' => 1], (int) $row['id']);
        } catch (\Throwable $ex) {
            $this->log('WARNING', 'Enquiry #' . $row['id'] . ' saved, but the notification email failed: ' . $ex->getMessage());
        }
    }

    public function log(string $level, string $message): void
    {
        $dir = $this->root . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . '/site-' . date('Y-m-d') . '.log', '[' . date('Y-m-d H:i:s') . "] $level: $message\n", FILE_APPEND | LOCK_EX);
    }
}
