<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/**
 * One payment gateway. Every gateway follows the same flow:
 *   start()    create the order at the gateway → an action for the browser (redirect, auto-post form, or the gateway's script)
 *   complete() the customer comes back → verify what the gateway sent, then confirm with the gateway's API
 *   status()   ask the gateway's API for the order's current state (also used by "Re-check" and after webhooks)
 *   webhook()  verify a server-to-server notification
 *
 * An outcome is: ['status' => paid|failed|pending|invalid (a message that failed verification: logged, changes nothing), 'amount' => ?int paise, 'payment_id' => ?string, 'method' => ?string, 'message' => ?string]
 *
 * In test mode only, $emulator (a local imitation of the gateways used in automated tests) replaces the sandbox hosts.
 */
abstract class Gateway
{
    /** @param array<string,string> $creds decrypted credentials for the current mode */
    public function __construct(protected array $creds, protected string $mode, protected Http $http, protected ?string $emulator = null)
    {
    }

    abstract public static function id(): string;

    abstract public static function name(): string;

    /** @return list<array{key:string,label:string,secret:bool,required:bool,help?:string}> */
    abstract public static function fields(): array;

    public static function note(): string
    {
        return '';
    }

    /** Whether this gateway needs its own webhook secret/credentials to accept webhooks. */
    public static function webhookHelp(): string
    {
        return '';
    }

    /** @param array{return_url:string,cancel_url:string,webhook_url:string,site_name:string,color:string} $ctx */
    abstract public function start(array $tx, array $ctx): array;

    abstract public function complete(array $tx, array $request): array;

    abstract public function status(array $tx): array;

    /**
     * @param array<string,string> $headers lower-case header names
     * @return array{key:string, value:string}|null which transaction the webhook is about (column + value); null = not relevant
     * @throws GatewayError when the signature is missing or wrong
     */
    abstract public function webhook(string $raw, array $headers): ?array;

    /** Check the credentials against the gateway. Returns a human message; throws GatewayError on failure. */
    abstract public function testConnection(): string;

    protected function base(string $test, string $live): string
    {
        if ($this->mode === 'test' && $this->emulator) {
            return rtrim($this->emulator, '/') . '/' . static::id() . rtrim((string) parse_url($test, PHP_URL_PATH), '/');
        }
        return $this->mode === 'live' ? $live : $test;
    }

    protected function cred(string $key): string
    {
        $v = trim((string) ($this->creds[$key] ?? ''));
        if ($v === '') {
            throw new GatewayError(static::name() . ' is not set up: “' . $key . '” is missing. Enter it in Payments settings.');
        }
        return $v;
    }

    protected static function outcome(string $status, ?int $amount = null, ?string $paymentId = null, ?string $method = null, ?string $message = null): array
    {
        return ['status' => $status, 'amount' => $amount, 'payment_id' => $paymentId, 'method' => $method, 'message' => $message !== null ? mb_substr($message, 0, 250) : null];
    }

    /** Throws a GatewayError carrying the gateway's own error text when the HTTP status is not 2xx. */
    protected function expectOk(array $r, string $what): array
    {
        if ($r['code'] >= 200 && $r['code'] < 300 && is_array($r['json'])) {
            return $r['json'];
        }
        $j = is_array($r['json']) ? $r['json'] : [];
        $msg = $j['error']['description'] ?? $j['error']['message'] ?? $j['message'] ?? $j['msg'] ?? null;
        if ($r['code'] === 401 || $r['code'] === 403) {
            throw new GatewayError(static::name() . " refused the credentials while trying to $what" . ($msg ? ": $msg" : '. Check the keys in Payments settings.'));
        }
        throw new GatewayError(static::name() . " could not $what (HTTP {$r['code']})" . ($msg ? ": $msg" : '.'));
    }

    /** Indian mobile numbers as the gateways expect them: 10 digits without +91 / 0. */
    protected static function phone(string $p): string
    {
        $d = preg_replace('/\D/', '', $p) ?? '';
        if (strlen($d) === 12 && str_starts_with($d, '91')) {
            $d = substr($d, 2);
        } elseif (strlen($d) === 11 && str_starts_with($d, '0')) {
            $d = substr($d, 1);
        }
        return $d;
    }

    protected static function rupees(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }

    protected static function paise(mixed $rupees): int
    {
        return (int) round(((float) $rupees) * 100);
    }
}
