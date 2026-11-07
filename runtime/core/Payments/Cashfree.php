<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/** Cashfree Payments (PG API 2025-01-01): order → payment_session_id → Cashfree JS checkout → return_url → order fetched from the API. */
final class Cashfree extends Gateway
{
    private const VERSION = '2025-01-01';

    public static function id(): string
    {
        return 'cashfree';
    }

    public static function name(): string
    {
        return 'Cashfree';
    }

    public static function fields(): array
    {
        return [
            ['key' => 'app_id', 'label' => 'App ID (Client ID)', 'secret' => false, 'required' => true, 'help' => 'Payment Gateway Dashboard → Developers → API Keys.'],
            ['key' => 'secret_key', 'label' => 'Secret key (Client Secret)', 'secret' => true, 'required' => true],
        ];
    }

    public static function webhookHelp(): string
    {
        return 'Optional: orders already send this URL to Cashfree automatically. Webhooks are verified with your secret key.';
    }

    private function api(): string
    {
        return $this->base('https://sandbox.cashfree.com/pg', 'https://api.cashfree.com/pg');
    }

    private function headers(): array
    {
        return ['x-client-id' => $this->cred('app_id'), 'x-client-secret' => $this->cred('secret_key'), 'x-api-version' => self::VERSION, 'Content-Type' => 'application/json', 'Accept' => 'application/json'];
    }

    public function start(array $tx, array $ctx): array
    {
        $sep = str_contains($ctx['return_url'], '?') ? '&' : '?';
        $o = $this->expectOk($this->http->request('POST', $this->api() . '/orders', $this->headers() + ['x-idempotency-key' => 'wb-' . $tx['reference']], json_encode([
            'order_id' => $tx['reference'],
            'order_amount' => (float) self::rupees((int) $tx['amount']),
            'order_currency' => $tx['currency'],
            'order_note' => mb_substr((string) $tx['purpose'], 0, 200),
            'customer_details' => [
                'customer_id' => 'c' . substr(hash('sha256', strtolower((string) $tx['customer_email']) . '|' . $tx['customer_phone']), 0, 20),
                'customer_name' => $tx['customer_name'], 'customer_email' => $tx['customer_email'], 'customer_phone' => self::phone((string) $tx['customer_phone']),
            ],
            'order_meta' => ['return_url' => $ctx['return_url'] . $sep . 'order_id={order_id}', 'notify_url' => $ctx['webhook_url']],
        ])), 'create the order');
        if (empty($o['payment_session_id'])) {
            throw new GatewayError('Cashfree did not return a payment session.');
        }
        $script = $this->mode === 'test' && $this->emulator ? rtrim($this->emulator, '/') . '/cashfree/sdk.js' : 'https://sdk.cashfree.com/js/v3/cashfree.js';
        return ['gateway_order_id' => (string) $tx['reference'], 'action' => [
            'type' => 'script', 'gateway' => 'cashfree', 'script' => $script,
            'options' => ['mode' => $this->mode === 'live' ? 'production' : 'sandbox', 'paymentSessionId' => (string) $o['payment_session_id'], 'redirectTarget' => '_self'],
        ]];
    }

    public function complete(array $tx, array $request): array
    {
        if (isset($request['order_id']) && (string) $request['order_id'] !== (string) $tx['gateway_order_id']) {
            return self::outcome('invalid', null, null, null, 'The payment does not belong to this order.');
        }
        return $this->status($tx);
    }

    public function status(array $tx): array
    {
        $id = rawurlencode((string) $tx['gateway_order_id']);
        $o = $this->expectOk($this->http->request('GET', $this->api() . '/orders/' . $id, $this->headers()), 'check the order');
        $amount = isset($o['order_amount']) ? self::paise($o['order_amount']) : null;
        $state = (string) ($o['order_status'] ?? '');
        $payments = $this->http->request('GET', $this->api() . '/orders/' . $id . '/payments', $this->headers());
        $list = is_array($payments['json']) && array_is_list($payments['json']) ? $payments['json'] : [];
        $success = null;
        $last = $list[0] ?? null;
        foreach ($list as $p) {
            if (($p['payment_status'] ?? '') === 'SUCCESS') {
                $success = $p;
            }
        }
        if ($state === 'PAID') {
            return self::outcome('paid', $amount, isset($success['cf_payment_id']) ? (string) $success['cf_payment_id'] : null, $success['payment_group'] ?? null);
        }
        if (in_array($state, ['EXPIRED', 'TERMINATED'], true)) {
            return self::outcome('failed', $amount, null, null, 'The payment window expired.');
        }
        if ($last && in_array($last['payment_status'] ?? '', ['FAILED', 'USER_DROPPED', 'CANCELLED'], true)) {
            return self::outcome('failed', $amount, isset($last['cf_payment_id']) ? (string) $last['cf_payment_id'] : null, $last['payment_group'] ?? null, (string) ($last['payment_message'] ?? 'The payment was not completed.'));
        }
        return self::outcome('pending', $amount);
    }

    public function webhook(string $raw, array $headers): ?array
    {
        $ts = (string) ($headers['x-webhook-timestamp'] ?? '');
        $sig = (string) ($headers['x-webhook-signature'] ?? '');
        if ($ts === '' || !hash_equals(base64_encode(hash_hmac('sha256', $ts . $raw, $this->cred('secret_key'), true)), $sig)) {
            throw new GatewayError('Invalid Cashfree webhook signature.');
        }
        $j = json_decode($raw, true) ?: [];
        $orderId = $j['data']['order']['order_id'] ?? null;
        return $orderId ? ['key' => 'gateway_order_id', 'value' => (string) $orderId] : null;
    }

    public function testConnection(): string
    {
        $r = $this->http->request('GET', $this->api() . '/orders/wb-connection-check-' . bin2hex(random_bytes(4)), $this->headers());
        if ($r['code'] === 404 && (($r['json']['code'] ?? '') === 'order_not_found')) {
            return 'Connected to Cashfree (' . ($this->mode === 'live' ? 'production' : 'sandbox') . ').';
        }
        $this->expectOk($r, 'check the credentials');
        return 'Connected to Cashfree.';
    }
}
