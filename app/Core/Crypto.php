<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Authenticated encryption (libsodium secretbox) with the builder's app key, for secrets kept in the database. */
final class Crypto
{
    public static function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key()));
    }

    public static function decrypt(?string $payload): ?string
    {
        if (!$payload || !str_starts_with($payload, 'v1:')) {
            return null;
        }
        $raw = base64_decode(substr($payload, 3), true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key());
        return $plain === false ? null : $plain;
    }

    private static function key(): string
    {
        $key = base64_decode((string) Config::get('app.key', ''), true);
        if ($key === false || strlen($key) < 32) {
            throw new RuntimeException('The builder app key is missing from config/config.php.');
        }
        return substr(hash('sha256', $key, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
