<?php
declare(strict_types=1);

namespace App\Modules\Generator;

use App\Core\Crypto;
use App\Core\Settings;

/**
 * QA access to generated websites on the builder server.
 * Generated sites are real, standalone sites; on the builder server they are protected with
 * HTTP Basic Auth (generated_websites/.htaccess + storage/qa/.htpasswd) so only the team can open them.
 */
final class QaAccess
{
    public const USER = 'qa';

    public static function baseUrl(): string
    {
        $v = rtrim((string) Settings::get('qa_sites_url', ''), '/');
        return $v !== '' ? $v : rtrim(url('generated_websites'), '/');
    }

    public static function siteUrl(array $website): string
    {
        return self::baseUrl() . '/' . basename((string) $website['folder_path']) . '/';
    }

    public static function password(): string
    {
        $plain = Crypto::decrypt((string) Settings::get('qa_password', ''));
        if ($plain === null || $plain === '') {
            $plain = self::regenerate();
        }
        return $plain;
    }

    public static function regenerate(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $plain = '';
        for ($i = 0; $i < 18; $i++) {
            $plain .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        Settings::set('qa_password', Crypto::encrypt($plain));
        self::writeFiles($plain);
        return $plain;
    }

    /** (Re)write the .htpasswd and the Basic Auth .htaccess for generated_websites/. */
    public static function writeFiles(?string $plain = null): void
    {
        $plain ??= self::password();
        $dir = STORAGE_PATH . '/qa';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/.htpasswd', self::USER . ':' . password_hash($plain, PASSWORD_BCRYPT) . "\n", LOCK_EX);
        @chmod($dir . '/.htpasswd', 0644);
        $htpasswd = realpath($dir . '/.htpasswd') ?: $dir . '/.htpasswd';
        $ht = "# Written by the builder (Settings). QA copies of generated websites: team access only.\n"
            . "Options -Indexes\n"
            . "AuthType Basic\n"
            . "AuthName \"Channel Softech QA\"\n"
            . 'AuthUserFile "' . str_replace('"', '', $htpasswd) . "\"\n"
            . "Require valid-user\n";
        file_put_contents(SITES_PATH . '/.htaccess', $ht, LOCK_EX);
    }
}
