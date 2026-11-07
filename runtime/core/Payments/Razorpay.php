<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/**
 * Razorpay Standard Checkout: server creates an Order, the browser opens Razorpay's checkout script
 * with redirect mode, Razorpay posts back razorpay_payment_id/order_id/signature.
 * The signature (HMAC-SHA256 of "order_id|payment_id" with the key secret) is checked, then the payment
 * is fetched from the API (and captured if the account is set to manual capture).
 */
final class Razorpay extends Gateway
{
    public static function id(): string
    {
        return 'razorpay';
    }

    public static function name(): string
    {
        return 'Razorpay';
    }

    public static function fields(): array
    {
        return [
            ['key' => 'key_id', 'label' => 'Key ID', 'secret' => false, 'required' => true, 'help' => 'Dashboard → Account & Settings → API Keys. Starts with rzp_test_ or rzp_live_.'],
            ['key' => 'key_secret', 'label' => 'Key Secret', 'secret' => true, 'required' => true],
            ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'secret' => true, 'required' => false, 'help' => 'Optional but recommended: the secret you type when adding the webhook in Razorpay.'],
        ];
    }

    public static function note(): string
    {
        return 'Test keys are available immediately after signing up, before KYC.';
    }

    public static function webhookHelp(): string
    {
        return 'Razorpay Dashboard → Webhooks → Add: paste this URL, set a secret (enter the same secret here), and tick payment.captured, payment.failed and order.paid.';
    }

    private function api(): string
    {
        return $this->base('https://api.razorpay.com', 'https://api.razorpay.com') . '/v1';
    }

    private function auth(): array
    {
        return ['Authorization' => 'Basic ' . base64_encode($this->cred('key_id') . ':' . $this->cred('key_secret'))];
    }

    public function start(array $tx, array $ctx): array
    {
        $r = $this->http->request('POST', $this->api() . '/orders', $this->auth() + ['Content-Type' => 'application/json'], json_encode([
            'amount' => (int) $tx['amount'], 'currency' => $tx['currency'], 'receipt' => $tx['reference'],
            'notes' => ['reference' => $tx['reference'], 'purpose' => mb_substr((string) $tx['purpose'], 0, 250)],
        ]));
        $order = $this->expectOk($r, 'create the order');
        if (empty($order['id'])) {
            throw new GatewayError('Razorpay did not return an order ID.');
        }
        $script = $this->mode === 'test' && $this->emulator ? rtrim($this->emulator, '/') . '/razorpay/checkout.js' : 'https://checkout.razorpay.com/v1/checkout.js';
        return ['gateway_order_id' => (string) $order['id'], 'action' => [
            'type' => 'script', 'gateway' => 'razorpay', 'script' => $script,
            'options' => [
                'key' => $this->cred('key_id'), 'amount' => (int) $tx['amount'], 'currency' => $tx['currency'],
                'name' => mb_substr($ctx['site_name'], 0, 60), 'description' => mb_substr((string) $tx['purpose'], 0, 250), 'order_id' => (string) $order['id'],
                'prefill' => ['name' => $tx['customer_name'], 'email' => $tx['customer_email'], 'contact' => $tx['customer_phone']],
                'notes' => ['reference' => $tx['reference']], 'callback_url' => $ctx['return_url'], 'redirect' => true,
                'theme' => ['color' => $ctx['color']],
            ],
            'cancel_url' => $ctx['cancel_url'],
        ]];
    }

    public function complete(array $tx, array $request): array
    {
        $paymentId = (string) ($request['razorpay_payment_id'] ?? '');
        $orderId = (string) ($request['razorpay_order_id'] ?? '');
        $sig = (string) ($request['razorpay_signature'] ?? '');
        if ($paymentId !== '' || $sig !== '') {
            if ($orderId !== (string) $tx['gateway_order_id']) {
                return self::outcome('invalid', null, null, null, 'The payment does not belong to this order.');
            }
            $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->cred('key_secret'));
            if (!hash_equals($expected, $sig)) {
                return self::outcome('invalid', null, null, null, 'The payment signature could not be verified.');
            }
            return $this->payment($paymentId, $tx);
        }
        // Redirect mode posts error[...] when the payment fails; confirm with the API in case a retry succeeded.
        $err = is_array($request['error'] ?? null) ? $request['error'] : [];
        $s = $this->status($tx);
        if ($s['status'] === 'pending' && $err) {
            return self::outcome('failed', null, null, null, (string) ($err['description'] ?? 'The payment was not completed.'));
        }
        return $s;
    }

    public function status(array $tx): array
    {
        if (empty($tx['gateway_order_id'])) {
            return self::outcome('pending');
        }
        $r = $this->http->request('GET', $this->api() . '/orders/' . rawurlencode((string) $tx['gateway_order_id']) . '/payments', $this->auth());
        $items = (array) ($this->expectOk($r, 'check the order')['items'] ?? []);
        $failed = null;
        foreach ($items as $p) {
            if (in_array($p['status'] ?? '', ['captured', 'authorized'], true)) {
                return $this->payment((string) $p['id'], $tx, $p);
            }
            if (($p['status'] ?? '') === 'failed') {
                $failed = $p;
            }
        }
        return $failed ? self::outcome('failed', (int) $failed['amount'], (string) $failed['id'], $failed['method'] ?? null, $failed['error_description'] ?? 'The payment failed.') : self::outcome('pending');
    }

    private function payment(string $paymentId, array $tx, ?array $p = null): array
    {
        if ($p === null) {
            $p = $this->expectOk($this->http->request('GET', $this->api() . '/payments/' . rawurlencode($paymentId), $this->auth()), 'check the payment');
        }
        if (($p['order_id'] ?? '') !== (string) $tx['gateway_order_id']) {
            return self::outcome('invalid', null, $paymentId, null, 'The payment does not belong to this order.');
        }
        if (($p['status'] ?? '') === 'authorized') {
            // Accounts with manual capture: capture now, otherwise the money is refunded after a few days.
            $p = $this->expectOk($this->http->request('POST', $this->api() . '/payments/' . rawurlencode($paymentId) . '/capture', $this->auth() + ['Content-Type' => 'application/json'],
                json_encode(['amount' => (int) $p['amount'], 'currency' => $p['currency'] ?? 'INR'])), 'capture the payment');
        }
        return match ($p['status'] ?? '') {
            'captured' => self::outcome('paid', (int) $p['amount'], $paymentId, $p['method'] ?? null),
            'failed' => self::outcome('failed', (int) $p['amount'], $paymentId, $p['method'] ?? null, $p['error_description'] ?? 'The payment failed.'),
            default => self::outcome('pending', (int) ($p['amount'] ?? 0), $paymentId, $p['method'] ?? null),
        };
    }

    public function webhook(string $raw, array $headers): ?array
    {
        $secret = trim((string) ($this->creds['webhook_secret'] ?? ''));
        if ($secret === '') {
            throw new GatewayError('No Razorpay webhook secret is set up for this website.');
        }
        if (!hash_equals(hash_hmac('sha256', $raw, $secret), (string) ($headers['x-razorpay-signature'] ?? ''))) {
            throw new GatewayError('Invalid Razorpay webhook signature.');
        }
        $j = json_decode($raw, true) ?: [];
        $orderId = $j['payload']['payment']['entity']['order_id'] ?? $j['payload']['order']['entity']['id'] ?? null;
        return $orderId ? ['key' => 'gateway_order_id', 'value' => (string) $orderId] : null;
    }

    public function testConnection(): string
    {
        $this->expectOk($this->http->request('GET', $this->api() . '/orders?count=1', $this->auth()), 'list orders');
        return 'Connected to Razorpay (' . ($this->mode === 'live' ? 'live' : 'test') . ' mode).';
    }
}
