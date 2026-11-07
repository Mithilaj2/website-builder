<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/**
 * PhonePe Payment Gateway, Standard Checkout v2: OAuth token (client credentials) → /checkout/v2/pay →
 * redirect to PhonePe → back to redirectUrl → /checkout/v2/order/{merchantOrderId}/status.
 * Webhooks carry Authorization: SHA256(username:password) with the credentials set in the PhonePe dashboard.
 */
final class PhonePe extends Gateway
{
    public static function id(): string
    {
        return 'phonepe';
    }

    public static function name(): string
    {
        return 'PhonePe';
    }

    public static function fields(): array
    {
        return [
            ['key' => 'client_id', 'label' => 'Client ID', 'secret' => false, 'required' => true, 'help' => 'PhonePe Business Dashboard → Developer Settings.'],
            ['key' => 'client_version', 'label' => 'Client version', 'secret' => false, 'required' => true, 'help' => 'Usually 1.'],
            ['key' => 'client_secret', 'label' => 'Client secret', 'secret' => true, 'required' => true],
            ['key' => 'webhook_username', 'label' => 'Webhook username', 'secret' => false, 'required' => false, 'help' => 'Optional: the username and password you set for the webhook in the PhonePe dashboard.'],
            ['key' => 'webhook_password', 'label' => 'Webhook password', 'secret' => true, 'required' => false],
        ];
    }

    public static function note(): string
    {
        return 'Requires a PhonePe business account; API credentials are issued after onboarding. Uses PhonePe’s current (v2) Standard Checkout.';
    }

    public static function webhookHelp(): string
    {
        return 'PhonePe Business Dashboard → Developer Settings → Webhook: paste this URL, choose a username and password (enter the same here) and select checkout.order.completed and checkout.order.failed. For the sandbox, PhonePe’s integration team sets it up.';
    }

    private function api(): string
    {
        return $this->base('https://api-preprod.phonepe.com/apis/pg-sandbox', 'https://api.phonepe.com/apis/pg');
    }

    private function tokenUrl(): string
    {
        return $this->base('https://api-preprod.phonepe.com/apis/pg-sandbox', 'https://api.phonepe.com/apis/identity-manager') . '/v1/oauth/token';
    }

    private function auth(): array
    {
        $r = $this->http->request('POST', $this->tokenUrl(), ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query([
            'client_id' => $this->cred('client_id'), 'client_version' => $this->cred('client_version'),
            'client_secret' => $this->cred('client_secret'), 'grant_type' => 'client_credentials',
        ]));
        $j = $this->expectOk($r, 'sign in');
        if (empty($j['access_token'])) {
            throw new GatewayError('PhonePe did not return an access token.');
        }
        return ['Authorization' => 'O-Bearer ' . $j['access_token']];
    }

    public function start(array $tx, array $ctx): array
    {
        $r = $this->http->request('POST', $this->api() . '/checkout/v2/pay', $this->auth() + ['Content-Type' => 'application/json'], json_encode([
            'merchantOrderId' => $tx['reference'],
            'amount' => (int) $tx['amount'],
            'expireAfter' => 1200,
            'metaInfo' => ['udf1' => mb_substr((string) $tx['purpose'], 0, 250), 'udf2' => $tx['customer_email']],
            'paymentFlow' => ['type' => 'PG_CHECKOUT', 'message' => mb_substr((string) $tx['purpose'], 0, 250), 'merchantUrls' => ['redirectUrl' => $ctx['return_url']]],
        ]));
        $j = $this->expectOk($r, 'create the payment');
        if (empty($j['redirectUrl'])) {
            throw new GatewayError('PhonePe did not return a payment page.');
        }
        return ['gateway_order_id' => (string) ($j['orderId'] ?? $tx['reference']), 'action' => ['type' => 'redirect', 'url' => (string) $j['redirectUrl']]];
    }

    public function complete(array $tx, array $request): array
    {
        return $this->status($tx);
    }

    public function status(array $tx): array
    {
        $j = $this->expectOk($this->http->request('GET', $this->api() . '/checkout/v2/order/' . rawurlencode((string) $tx['reference']) . '/status', $this->auth()), 'check the payment');
        $details = (array) ($j['paymentDetails'] ?? []);
        $last = $details ? $details[count($details) - 1] : [];
        foreach ($details as $d) {
            if (($d['state'] ?? '') === 'COMPLETED') {
                $last = $d;
            }
        }
        $amount = isset($j['amount']) ? (int) $j['amount'] : null;
        $pid = isset($last['transactionId']) ? (string) $last['transactionId'] : null;
        $method = $last['paymentMode'] ?? null;
        return match ($j['state'] ?? '') {
            'COMPLETED' => self::outcome('paid', $amount, $pid, $method),
            'FAILED' => self::outcome('failed', $amount, $pid, $method, (string) ($last['errorCode'] ?? $j['errorCode'] ?? 'The payment failed.')),
            default => self::outcome('pending', $amount, $pid, $method),
        };
    }

    public function webhook(string $raw, array $headers): ?array
    {
        $user = trim((string) ($this->creds['webhook_username'] ?? ''));
        $pass = (string) ($this->creds['webhook_password'] ?? '');
        if ($user === '' || $pass === '') {
            throw new GatewayError('No PhonePe webhook username/password is set up for this website.');
        }
        $got = strtolower(trim(preg_replace('/^(SHA256|Basic)\s+/i', '', (string) ($headers['authorization'] ?? ''))));
        if (!hash_equals(hash('sha256', $user . ':' . $pass), $got)) {
            throw new GatewayError('Invalid PhonePe webhook authorization.');
        }
        $j = json_decode($raw, true) ?: [];
        $ref = $j['payload']['merchantOrderId'] ?? null;
        return $ref ? ['key' => 'reference', 'value' => (string) $ref] : null;
    }

    public function testConnection(): string
    {
        $this->auth();
        return 'Connected to PhonePe (' . ($this->mode === 'live' ? 'production' : 'sandbox') . ').';
    }
}
