<?php
declare(strict_types=1);

namespace App\Core;

/** Key/value platform settings stored in wb_settings. */
final class Settings
{
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::fetchAll('SELECT setting_key, setting_value FROM wb_settings') as $r) {
                self::$cache[$r['setting_key']] = $r['setting_value'];
            }
        }
        return array_key_exists($key, self::$cache) && self::$cache[$key] !== null ? self::$cache[$key] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::query(
            'INSERT INTO wb_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)',
            [$key, $value, now()]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }
}
