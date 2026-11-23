<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

use SiteRuntime\Renderer;
use SiteRuntime\ValidationError;

/**
 * Public "Pay online" pages of a website:
 *   GET  /pay                    form (purpose, amount, name, email, mobile)
 *   POST /pay                    create the transaction + gateway order → gateway (redirect / auto-post form / gateway script)
 *   GET|POST /pay/return/{ref}   customer back from the gateway → verify + confirm with the gateway API → receipt
 *   GET  /pay/cancel/{ref}       customer closed the gateway page
 *   GET  /pay/receipt/{ref}?t=…  receipt / status page (token-protected)
 *   POST /pay/webhook/{gateway}  server-to-server notifications (signature checked, then confirmed with the API)
 */
final class PaymentPage
{
    private const MIN_SECONDS = 2;
    private const MAX_AGE = 7200;

    /**
     * @param array{site_name:string, color:string, key:string, abs_base:string, url:callable(string):string, asset:callable(string):string} $ctx
     * @param \Closure(array):void $onPaid   sends the receipt + owner notification (best effort)
     * @param \Closure(string,string):void $log
     */
    /** @param ?\Closure(array):?string $afterReturn  where to send the customer back to (shop orders); null = the receipt page */
    public function __construct(private PaymentService $svc, private Renderer $renderer, private array $ctx, private \Closure $onPaid, private \Closure $log, private ?\Closure $afterReturn = null)
    {
    }

    public function handle(string $sub, string $method): void
    {
        $sub = trim($sub, '/');
        if ($sub === '' && $method === 'GET') {
            $this->form();
        } elseif ($sub === '' && $method === 'POST') {
            $this->submit();
        } elseif (preg_match('#^return/([A-Z0-9]{6,30})$#', $sub, $m) && in_array($method, ['GET', 'POST'], true)) {
            $this->back($m[1]);
        } elseif (preg_match('#^cancel/([A-Z0-9]{6,30})$#', $sub, $m) && $method === 'GET') {
            $this->cancel($m[1]);
        } elseif (preg_match('#^receipt/([A-Z0-9]{6,30})$#', $sub, $m) && $method === 'GET') {
            $this->receipt($m[1]);
        } elseif (preg_match('#^webhook/([a-z]{3,12})$#', $sub, $m) && $method === 'POST') {
            $this->webhook($m[1]);
        } else {
            $this->page('Page not found', '<p>The page you are looking for does not exist.</p>', 404);
        }
    }

    // ------------------------------------------------------------------ form

    private function form(array $values = [], array $errors = [], string $alert = '', ?string $token = null, int $status = 200): void
    {
        $s = $this->svc->settings();
        if (!$s['enabled']) {
            $this->page('Page not found', '<p>The page you are looking for does not exist.</p>', 404);
            return;
        }
        if (!$this->svc->ready()) {
            $this->page($s['page']['title'], '<div class="wbpay-alert wbpay-alert-info">Online payments are not available at the moment. Please contact us to pay.</div>', 503);
            return;
        }
        if (!$values) {
            // Links can preselect an option (?option=2) or suggest an amount and purpose (?amount=1500&purpose=Advance).
            $values = ['purpose_option' => (string) ($_GET['option'] ?? ''), 'amount' => (string) ($_GET['amount'] ?? ''), 'purpose' => mb_substr((string) ($_GET['purpose'] ?? ''), 0, 190)];
        }
        if ($token === null) {
            $ts = (string) time();
            $token = $ts . '.' . hash_hmac('sha256', 'pay|' . $ts, $this->ctx['key']);
        }
        $html = $this->view('form', [
            'settings' => $s, 'values' => $values, 'errors' => $errors, 'alert' => $alert, 'token' => $token,
            'action' => ($this->ctx['url'])(''), 'gatewayName' => Gateways::ALL[$s['gateway']]::name(),
        ]);
        $this->page($s['page']['title'], $html, $status, true);
    }

    private function submit(): void
    {
        $token = (string) ($_POST['_t'] ?? '');
        $values = array_map(static fn ($v) => is_string($v) ? $v : '', array_intersect_key($_POST, array_flip(['purpose_option', 'purpose', 'amount', 'name', 'email', 'phone', 'note'])));
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            $this->page('Thank you', '<p>Thank you.</p>');
            return;
        }
        if (!$this->svc->ready()) {
            $this->form();
            return;
        }
        $age = $this->tokenAge($token);
        if ($age === null || $age > self::MAX_AGE) {
            $this->form($values, [], 'The form expired. Please check the details and try again.', null, 422);
            return;
        }
        if ($age < self::MIN_SECONDS) {
            $this->form($values, [], 'Please check the details and press the button again.', $token, 422);
            return;
        }
        try {
            $tx = $this->svc->create($_POST, $this->ip());
        } catch (ValidationError $e) {
            $this->form($values, $e->errors, $e->getMessage(), $token, 422);
            return;
        }
        $s = $this->svc->settings();
        try {
            $action = $this->svc->start($tx, [
                'return_url' => $this->abs('return/' . $tx['reference']),
                'cancel_url' => $this->abs('cancel/' . $tx['reference']),
                'webhook_url' => $this->abs('webhook/' . $tx['gateway']),
                'site_name' => $this->ctx['site_name'],
                'color' => $this->ctx['color'],
            ]);
        } catch (GatewayError $e) {
            ($this->log)('ERROR', 'Payment ' . $tx['reference'] . ' could not be started: ' . $e->getMessage());
            $this->form($values, [], 'Sorry, the payment could not be started. Please try again in a few minutes, or contact us.', null, 502);
            return;
        }
        if ($action['type'] === 'redirect') {
            header('Location: ' . $action['url'], true, 303);
            return;
        }
        $html = $this->view('redirect', ['action' => $action, 'tx' => $tx, 'settings' => $s, 'gatewayName' => Gateways::ALL[$tx['gateway']]::name(),
            'cancel' => $this->abs('cancel/' . $tx['reference'])]);
        $this->page($s['page']['title'], $html, 200, true);
    }

    // ------------------------------------------------------------------ back from the gateway

    private function back(string $ref): void
    {
        $tx = $this->svc->byReference($ref);
        if (!$tx) {
            $this->page('Payment not found', '<p>We could not find this payment.</p>', 404);
            return;
        }
        if ($tx['status'] !== 'paid') {
            $request = array_merge($_GET, $_POST);
            try {
                $o = $this->svc->gateway($tx['gateway'], $tx['mode'])->complete($tx, $request);
                [, $became] = $this->svc->apply($tx, $o, 'return', json_encode($request, JSON_UNESCAPED_SLASHES));
                if ($became) {
                    ($this->onPaid)($this->svc->find((int) $tx['id']));
                }
            } catch (GatewayError $e) {
                ($this->log)('WARNING', 'Payment ' . $ref . ': could not confirm with the gateway: ' . $e->getMessage());
                $this->svc->event((int) $tx['id'], $tx['gateway'], 'return', false, 'error', $e->getMessage());
            }
        }
        header('Location: ' . $this->backUrl($ref), true, 303);
    }

    private function cancel(string $ref): void
    {
        $tx = $this->svc->byReference($ref);
        if (!$tx) {
            $this->page('Payment not found', '<p>We could not find this payment.</p>', 404);
            return;
        }
        if (in_array($tx['status'], ['created', 'pending'], true)) {
            try {
                $o = $this->svc->gateway($tx['gateway'], $tx['mode'])->status($tx);
            } catch (GatewayError) {
                $o = ['status' => 'pending', 'amount' => null, 'payment_id' => null, 'method' => null, 'message' => null];
            }
            if ($o['status'] === 'pending') {
                $o = ['status' => 'cancelled', 'amount' => null, 'payment_id' => null, 'method' => null, 'message' => 'Cancelled by the customer.'];
            }
            [, $became] = $this->svc->apply($tx, $o, 'return');
            if ($became) {
                ($this->onPaid)($this->svc->find((int) $tx['id']));
            }
        }
        header('Location: ' . $this->backUrl($ref), true, 303);
    }

    private function receipt(string $ref): void
    {
        $tx = $this->svc->byReference($ref);
        if (!$tx || !hash_equals($this->svc->receiptToken($ref, $this->ctx['key']), (string) ($_GET['t'] ?? ''))) {
            $this->page('Payment not found', '<p>We could not find this payment.</p>', 404);
            return;
        }
        // Still waiting (e.g. UPI approval, or the customer came back before the bank answered): ask the gateway again.
        if ($tx['status'] === 'pending' && strtotime((string) $tx['updated_at']) < time() - 5) {
            try {
                [$tx, $became] = $this->svc->apply($tx, $this->svc->gateway($tx['gateway'], $tx['mode'])->status($tx), 'recheck');
                if ($became) {
                    ($this->onPaid)($tx);
                }
            } catch (GatewayError $e) {
                ($this->log)('WARNING', 'Payment ' . $ref . ': status check failed: ' . $e->getMessage());
            }
        }
        $s = $this->svc->settings();
        header('X-Robots-Tag: noindex');
        $this->page($tx['status'] === 'paid' ? 'Payment successful' : 'Payment status', $this->view('receipt', [
            'tx' => $tx, 'settings' => $s, 'gatewayName' => Gateways::ALL[$tx['gateway']]::name() ?? $tx['gateway'],
            'retry' => ($this->ctx['url'])('') . (str_contains(($this->ctx['url'])(''), '?') ? '&' : '?') . http_build_query(['purpose' => $tx['purpose'], 'amount' => PaymentService::inr((int) $tx['amount'])]),
            'self' => $this->receiptUrl($ref),
        ]), 200, true);
    }

    // ------------------------------------------------------------------ webhooks

    private function webhook(string $gatewayId): void
    {
        header('Content-Type: application/json');
        if (!isset(Gateways::ALL[$gatewayId])) {
            http_response_code(404);
            echo '{"ok":false}';
            return;
        }
        $raw = (string) file_get_contents('php://input', false, null, 0, 1_000_000);
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = (string) $v;
            }
        }
        if (!isset($headers['authorization']) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        $s = $this->svc->settings();
        // The notification may be for the current mode or the other one (e.g. late test webhooks after going live).
        $modes = array_unique([$s['mode'], $s['mode'] === 'live' ? 'test' : 'live']);
        $match = null;
        $lastError = 'not configured';
        foreach ($modes as $mode) {
            try {
                $match = $this->svc->gateway($gatewayId, $mode)->webhook($raw, $headers);
                $lastError = '';
                break;
            } catch (GatewayError $e) {
                $lastError = $e->getMessage();
            }
        }
        if ($lastError !== '') {
            $this->svc->event(null, $gatewayId, 'webhook', false, 'rejected', $lastError);
            http_response_code(401);
            echo '{"ok":false}';
            return;
        }
        $tx = null;
        if ($match) {
            $tx = $match['key'] === 'reference' ? $this->svc->byReference($match['value']) : $this->svc->byGatewayOrder($gatewayId, $match['value']);
        }
        if (!$tx || $tx['gateway'] !== $gatewayId) {
            $this->svc->event(null, $gatewayId, 'webhook', true, 'ignored', 'No matching payment on this website.', $raw);
            echo '{"ok":true}';
            return;
        }
        if ($tx['status'] !== 'paid') {
            try {
                // The notification only says "something changed": the gateway's API is the source of truth.
                [, $became] = $this->svc->apply($tx, $this->svc->gateway($tx['gateway'], $tx['mode'])->status($tx), 'webhook', $raw);
                if ($became) {
                    ($this->onPaid)($this->svc->find((int) $tx['id']));
                }
            } catch (GatewayError $e) {
                $this->svc->event((int) $tx['id'], $gatewayId, 'webhook', true, 'error', $e->getMessage(), $raw);
                http_response_code(503); // the gateway will retry later
                echo '{"ok":false}';
                return;
            }
        } else {
            $this->svc->event((int) $tx['id'], $gatewayId, 'webhook', true, 'paid', 'Already paid.', $raw);
        }
        echo '{"ok":true}';
    }

    // ------------------------------------------------------------------ helpers

    private function tokenAge(string $token): ?int
    {
        if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $token, $m) || !hash_equals(hash_hmac('sha256', 'pay|' . $m[1], $this->ctx['key']), $m[2])) {
            return null;
        }
        return time() - (int) $m[1];
    }

    private function abs(string $sub): string
    {
        return $this->ctx['abs_base'] . ($this->ctx['url'])($sub);
    }

    /** Shop orders go back to the order page; other payments to their receipt. */
    private function backUrl(string $ref): string
    {
        $tx = $this->svc->byReference($ref);
        $url = $tx && $this->afterReturn ? ($this->afterReturn)($tx) : null;
        return $url ?? $this->receiptUrl($ref);
    }

    private function receiptUrl(string $ref): string
    {
        return ($this->ctx['url'])('receipt/' . $ref) . '?t=' . $this->svc->receiptToken($ref, $this->ctx['key']);
    }

    private function ip(): string
    {
        return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    private function view(string $name, array $vars): string
    {
        $vars['e'] = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $vars['inr'] = static fn (int $p): string => '₹' . PaymentService::inr($p);
        ob_start();
        (static function (string $__f, array $__v): void {
            extract($__v, EXTR_SKIP);
            include $__f;
        })(__DIR__ . '/views/' . $name . '.php', $vars);
        return (string) ob_get_clean();
    }

    private function page(string $title, string $body, int $status = 200, bool $noindex = false): void
    {
        $css = ($this->ctx['asset'])('pay/pay.css');
        $content = '<link rel="stylesheet" href="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">'
            . '<section class="wb-section wbpay"><div class="wbpay-wrap">' . ($body !== '' && !str_contains($body, 'wbpay-card') ? '<div class="wbpay-card"><h1 class="wbpay-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>' . $body . '</div>' : $body) . '</div></section>'
            . '<script src="' . htmlspecialchars(($this->ctx['asset'])('pay/pay.js'), ENT_QUOTES, 'UTF-8') . '"></script>';
        $out = $this->renderer->renderContent(['id' => 0, 'title' => $title, 'slug' => 'pay', 'page_type' => 'pay', 'is_home' => 0, 'status' => 'active',
            'seo_title' => $title, 'seo_description' => '', 'seo_keywords' => '', 'robots' => $noindex ? 'noindex' : null], $content, $status);
        if ($noindex) {
            header('X-Robots-Tag: noindex');
        }
        header('Cache-Control: no-store');
        http_response_code($out['status']);
        header('Content-Type: text/html; charset=utf-8');
        echo $out['html'];
    }
}
