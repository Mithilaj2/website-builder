<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/** Stripe Checkout (hosted page): Checkout Session → redirect → return with session_id → session fetched from the API. */
final class Stripe extends Gateway
{
    public static function id(): string
    {
        return 'stripe';
    }

    public static function name(): string
    {
        return 'Stripe';
    }

    public static function fields(): array
    {
        return [
            ['key' => 'secret_key', 'label' => 'Secret key', 'secret' => true, 'required' => true, 'help' => 'Developers → API keys. Starts with sk_test_ or sk_live_ (a restricted key with Checkout Sessions write access also works).'],
            ['key' => 'webhook_secret', 'label' => 'Webhook signing secret', 'secret' => true, 'required' => false, 'help' => 'Optional but recommended. Starts with whsec_.'],
        ];
    }

    public static function note(): string
    {
        return 'Stripe accounts in India are by invitation for some business types.';
    }

    public static function webhookHelp(): string
    {
        return 'Stripe Dashboard → Developers → Webhooks → Add endpoint: paste this URL and select checkout.session.completed, checkout.session.async_payment_succeeded, checkout.session.async_payment_failed and checkout.session.expired. Copy its signing secret here.';
    }

    private function api(): string
    {
        return $this->base('https://api.stripe.com', 'https://api.stripe.com') . '/v1';
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->cred('secret_key')];
    }

    public function start(array $tx, array $ctx): array
    {
        $sep = str_contains($ctx['return_url'], '?') ? '&' : '?';
        $body = http_build_query([
            'mode' => 'payment',
            'success_url' => $ctx['return_url'] . $sep . 'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $ctx['cancel_url'],
            'client_reference_id' => $tx['reference'],
            'customer_email' => $tx['customer_email'],
            'line_items' => [['quantity' => 1, 'price_data' => ['currency' => strtolower((string) $tx['currency']), 'unit_amount' => (int) $tx['amount'], 'product_data' => ['name' => mb_substr((string) $tx['purpose'], 0, 250)]]]],
            'metadata' => ['reference' => $tx['reference']],
            'payment_intent_data' => ['metadata' => ['reference' => $tx['reference']], 'description' => mb_substr((string) $tx['purpose'], 0, 250)],
        ]);
        // Stripe needs the literal {CHECKOUT_SESSION_ID} placeholder.
        $body = str_replace('%7BCHECKOUT_SESSION_ID%7D', '{CHECKOUT_SESSION_ID}', $body);
        $s = $this->expectOk($this->http->request('POST', $this->api() . '/checkout/sessions', $this->auth() + ['Content-Type' => 'application/x-www-form-urlencoded', 'Idempotency-Key' => 'wb-' . $tx['reference']], $body), 'create the checkout session');
        if (empty($s['id']) || empty($s['url'])) {
            throw new GatewayError('Stripe did not return a checkout page.');
        }
        return ['gateway_order_id' => (string) $s['id'], 'action' => ['type' => 'redirect', 'url' => (string) $s['url']]];
    }

    public function complete(array $tx, array $request): array
    {
        $sid = (string) ($request['session_id'] ?? '');
        if ($sid !== '' && $sid !== (string) $tx['gateway_order_id']) {
            return self::outcome('invalid', null, null, null, 'The payment does not belong to this order.');
        }
        return $this->status($tx);
    }

    public function status(array $tx): array
    {
        if (empty($tx['gateway_order_id'])) {
            return self::outcome('pending');
        }
        $s = $this->expectOk($this->http->request('GET', $this->api() . '/checkout/sessions/' . rawurlencode((string) $tx['gateway_order_id']), $this->auth()), 'check the payment');
        $amount = isset($s['amount_total']) ? (int) $s['amount_total'] : null;
        $pi = is_array($s['payment_intent'] ?? null) ? ($s['payment_intent']['id'] ?? null) : ($s['payment_intent'] ?? null);
        if (($s['payment_status'] ?? '') === 'paid' || ($s['payment_status'] ?? '') === 'no_payment_required') {
            return self::outcome('paid', $amount, $pi, 'card');
        }
        if (($s['status'] ?? '') === 'expired') {
            return self::outcome('failed', $amount, $pi, null, 'The checkout session expired.');
        }
        return self::outcome('pending', $amount, $pi);
    }

    public function webhook(string $raw, array $headers): ?array
    {
        $secret = trim((string) ($this->creds['webhook_secret'] ?? ''));
        if ($secret === '') {
            throw new GatewayError('No Stripe webhook signing secret is set up for this website.');
        }
        $parts = [];
        foreach (explode(',', (string) ($headers['stripe-signature'] ?? '')) as $kv) {
            [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, '');
            $parts[$k][] = $v;
        }
        $t = (int) ($parts['t'][0] ?? 0);
        if ($t <= 0 || abs(time() - $t) > 300) {
            throw new GatewayError('Stripe webhook timestamp is missing or too old.');
        }
        $expected = hash_hmac('sha256', $t . '.' . $raw, $secret);
        $ok = false;
        foreach ($parts['v1'] ?? [] as $sig) {
            $ok = $ok || hash_equals($expected, $sig);
        }
        if (!$ok) {
            throw new GatewayError('Invalid Stripe webhook signature.');
        }
        $j = json_decode($raw, true) ?: [];
        $obj = $j['data']['object'] ?? [];
        if (($obj['object'] ?? '') !== 'checkout.session' || empty($obj['id'])) {
            return null;
        }
        return ['key' => 'gateway_order_id', 'value' => (string) $obj['id']];
    }

    public function testConnection(): string
    {
        $this->expectOk($this->http->request('GET', $this->api() . '/balance', $this->auth()), 'read the account');
        return 'Connected to Stripe (' . ($this->mode === 'live' ? 'live' : 'test') . ' mode).';
    }
}
