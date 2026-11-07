<?php
declare(strict_types=1);

namespace SiteRuntime;

/** Authenticated encryption (libsodium secretbox) with the website's own key from config/app.php. */
final class Crypto
{
    public function __construct(private string $appKey)
    {
    }

    public function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $this->key()));
    }

    public function decrypt(?string $payload): ?string
    {
        if (!$payload || !str_starts_with($payload, 'v1:')) {
            return null;
        }
        $raw = base64_decode(substr($payload, 3), true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $this->key());
        return $plain === false ? null : $plain;
    }

    private function key(): string
    {
        $k = base64_decode($this->appKey, true);
        if ($k === false || strlen($k) < 32) {
            throw new \RuntimeException('The website key is missing from config/app.php.');
        }
        return substr(hash('sha256', 'secrets|' . $k, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
