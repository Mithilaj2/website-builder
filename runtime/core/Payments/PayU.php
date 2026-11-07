<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/**
 * PayU India hosted checkout: the browser posts a hashed form to PayU's /_payment page; PayU posts the result
 * back to surl/furl with a reverse hash, which is checked, then confirmed with the verify_payment API.
 */
final class PayU extends Gateway
{
    public static function id(): string
    {
        return 'payu';
    }

    public static function name(): string
    {
        return 'PayU';
    }

    public static function fields(): array
    {
        return [
            ['key' => 'merchant_key', 'label' => 'Merchant key', 'secret' => false, 'required' => true, 'help' => 'PayU Dashboard → Developers → API keys (test keys are shown under Test mode).'],
            ['key' => 'merchant_salt', 'label' => 'Merchant salt (v1)', 'secret' => true, 'required' => true],
        ];
    }

    public static function webhookHelp(): string
    {
        return 'Optional: PayU Dashboard → Developers → Webhooks → Payments: paste this URL for successful and failed payments. Webhooks are verified with your salt.';
    }

    private function host(): string
    {
        return $this->base('https://test.payu.in', 'https://secure.payu.in');
    }

    private function verifyUrl(): string
    {
        return $this->base('https://test.payu.in', 'https://info.payu.in') . '/merchant/postservice.php?form=2';
    }

    private static function clean(string $s, int $max): string
    {
        // PayU rejects some special characters in these fields; they are also part of the hash.
        return mb_substr(trim(preg_replace('/[^\p{L}\p{N} .,\-\/&()@]/u', ' ', $s) ?? ''), 0, $max);
    }

    public function start(array $tx, array $ctx): array
    {
        $f = [
            'key' => $this->cred('merchant_key'), 'txnid' => $tx['reference'], 'amount' => self::rupees((int) $tx['amount']),
            'productinfo' => self::clean((string) $tx['purpose'], 100) ?: 'Payment', 'firstname' => self::clean((string) $tx['customer_name'], 60) ?: 'Customer',
            'email' => $tx['customer_email'], 'phone' => self::phone((string) $tx['customer_phone']),
            'surl' => $ctx['return_url'], 'furl' => $ctx['return_url'],
            'udf1' => '', 'udf2' => '', 'udf3' => '', 'udf4' => '', 'udf5' => '',
        ];
        $f['hash'] = hash('sha512', implode('|', [$f['key'], $f['txnid'], $f['amount'], $f['productinfo'], $f['firstname'], $f['email'], '', '', '', '', '', '', '', '', '', '', $this->cred('merchant_salt')]));
        return ['gateway_order_id' => (string) $tx['reference'], 'action' => ['type' => 'form', 'url' => $this->host() . '/_payment', 'fields' => $f]];
    }

    /** Reverse hash PayU sends back: sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key), prefixed with additionalCharges| when present. */
    private function reverseHashOk(array $p): bool
    {
        $parts = [$this->cred('merchant_salt'), (string) ($p['status'] ?? ''), '', '', '', '', '',
            (string) ($p['udf5'] ?? ''), (string) ($p['udf4'] ?? ''), (string) ($p['udf3'] ?? ''), (string) ($p['udf2'] ?? ''), (string) ($p['udf1'] ?? ''),
            (string) ($p['email'] ?? ''), (string) ($p['firstname'] ?? ''), (string) ($p['productinfo'] ?? ''), (string) ($p['amount'] ?? ''), (string) ($p['txnid'] ?? ''), (string) ($p['key'] ?? '')];
        $s = implode('|', $parts);
        if (isset($p['additionalCharges']) && $p['additionalCharges'] !== '') {
            $s = $p['additionalCharges'] . '|' . $s;
        }
        return hash_equals(hash('sha512', $s), strtolower((string) ($p['hash'] ?? '')));
    }

    public function complete(array $tx, array $request): array
    {
        if (isset($request['hash'])) {
            if (($request['txnid'] ?? '') !== (string) $tx['reference'] || ($request['key'] ?? '') !== $this->cred('merchant_key') || !$this->reverseHashOk($request)) {
                return self::outcome('invalid', null, null, null, 'The payment response could not be verified.');
            }
        }
        $s = $this->status($tx);
        if ($s['status'] === 'pending' && ($request['status'] ?? '') === 'failure') {
            return self::outcome('failed', $s['amount'], $s['payment_id'], $s['method'], (string) ($request['error_Message'] ?? 'The payment failed.'));
        }
        return $s;
    }

    public function status(array $tx): array
    {
        $key = $this->cred('merchant_key');
        $txnid = (string) $tx['reference'];
        $r = $this->http->request('POST', $this->verifyUrl(), ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'], http_build_query([
            'key' => $key, 'command' => 'verify_payment', 'var1' => $txnid,
            'hash' => hash('sha512', $key . '|verify_payment|' . $txnid . '|' . $this->cred('merchant_salt')),
        ]));
        $j = $this->expectOk($r, 'check the payment');
        $d = $j['transaction_details'][$txnid] ?? null;
        if (!is_array($d)) {
            return self::outcome('pending');
        }
        $amount = isset($d['amt']) ? self::paise($d['amt']) : (isset($d['transaction_amount']) ? self::paise($d['transaction_amount']) : null);
        $pid = isset($d['mihpayid']) ? (string) $d['mihpayid'] : null;
        return match (strtolower((string) ($d['status'] ?? ''))) {
            'success' => self::outcome('paid', $amount, $pid, $d['mode'] ?? null),
            'failure', 'failed', 'dropped', 'bounced', 'usercancelled' => self::outcome('failed', $amount, $pid, $d['mode'] ?? null, (string) ($d['error_Message'] ?? $d['field9'] ?? 'The payment failed.')),
            default => self::outcome('pending', $amount, $pid, $d['mode'] ?? null),
        };
    }

    public function webhook(string $raw, array $headers): ?array
    {
        parse_str($raw, $p);
        if (!isset($p['hash'], $p['txnid']) || ($p['key'] ?? '') !== $this->cred('merchant_key') || !$this->reverseHashOk($p)) {
            throw new GatewayError('Invalid PayU webhook hash.');
        }
        return ['key' => 'reference', 'value' => (string) $p['txnid']];
    }

    public function testConnection(): string
    {
        $r = $this->http->request('POST', $this->verifyUrl(), ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'], http_build_query([
            'key' => $this->cred('merchant_key'), 'command' => 'verify_payment', 'var1' => 'WBCHECK',
            'hash' => hash('sha512', $this->cred('merchant_key') . '|verify_payment|WBCHECK|' . $this->cred('merchant_salt')),
        ]));
        $j = is_array($r['json']) ? $r['json'] : null;
        if ($r['code'] !== 200 || $j === null || stripos((string) ($j['msg'] ?? ''), 'hash') !== false) {
            $msg = (string) ($j['msg'] ?? '');
            throw new GatewayError('PayU did not accept the merchant key and salt' . ($msg !== '' ? ': ' . $msg : '.'));
        }
        return 'Connected to PayU (' . ($this->mode === 'live' ? 'live' : 'test') . ' mode).';
    }
}
