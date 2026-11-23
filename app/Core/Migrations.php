<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Builder database updates. database/schema.sql is the original schema (Phase 1, used by setup);
 * every later change is a numbered file in database/migrations/ (NNN_name.sql), applied once, in order,
 * on the first request after an upload. The applied version is kept in wb_settings ("db_version").
 */
final class Migrations
{
    public static function dir(): string
    {
        return BASE_PATH . '/database/migrations';
    }

    public static function latest(): int
    {
        $max = 0;
        foreach (glob(self::dir() . '/*.sql') ?: [] as $f) {
            $max = max($max, (int) basename($f));
        }
        return $max;
    }

    /** @return list<string> applied file names */
    public static function run(): array
    {
        $current = (int) Settings::get('db_version', '0');
        if ($current >= self::latest()) {
            return [];
        }
        $lock = fopen(STORAGE_PATH . '/migrations.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            return []; // another request is applying them
        }
        $applied = [];
        try {
            $files = glob(self::dir() . '/*.sql') ?: [];
            sort($files);
            foreach ($files as $f) {
                $n = (int) basename($f);
                if ($n <= $current) {
                    continue;
                }
                foreach (\SiteRuntime\SqlSplitter::split((string) file_get_contents($f)) as $sql) {
                    Database::pdo()->exec($sql);
                }
                Settings::set('db_version', (string) $n);
                $applied[] = basename($f);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        if ($applied) {
            Logger::info('Database updated', ['applied' => $applied]);
        }
        return $applied;
    }
}
