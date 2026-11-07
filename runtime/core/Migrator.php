<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Database schema of a website: core.sql (CREATE IF NOT EXISTS) plus numbered migrations
 * in schema/migrations/NNN_name.sql, applied once each and recorded in settings._schema_version.
 * Run by the builder on every generation and by the website itself after an update package.
 */
final class Migrator
{
    public function __construct(private Db $db, private string $schemaDir)
    {
    }

    public function current(): int
    {
        try {
            return (int) json_decode((string) $this->db->value('SELECT setting_value FROM ' . $this->db->t('settings') . " WHERE setting_key = '_schema_version'"), true);
        } catch (\PDOException) {
            return 0;
        }
    }

    public function latest(): int
    {
        $max = 0;
        foreach (glob($this->schemaDir . '/migrations/*.sql') ?: [] as $f) {
            $max = max($max, (int) basename($f));
        }
        return $max;
    }

    public function pending(): bool
    {
        return $this->current() < $this->latest();
    }

    /** @return list<string> names of the migrations applied */
    public function migrate(): array
    {
        $this->db->runSchema($this->schemaDir . '/core.sql');
        $current = $this->current();
        $applied = [];
        $files = glob($this->schemaDir . '/migrations/*.sql') ?: [];
        sort($files);
        foreach ($files as $f) {
            $n = (int) basename($f);
            if ($n <= $current) {
                continue;
            }
            $this->db->runSchema($f);
            $this->db->query(
                'REPLACE INTO ' . $this->db->t('settings') . " (setting_key, setting_value, updated_at) VALUES ('_schema_version', ?, ?)",
                [(string) $n, date('Y-m-d H:i:s')]
            );
            $applied[] = basename($f, '.sql');
        }
        return $applied;
    }
}
