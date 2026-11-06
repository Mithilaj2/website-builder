<?php
declare(strict_types=1);

namespace SiteRuntime;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Database access for one site. Every table name goes through t(), which applies the
 * site's table prefix, so a site can only ever address its own tables.
 */
final class Db
{
    public function __construct(private PDO $pdo, private string $prefix)
    {
        if (!preg_match('/^[a-z0-9_]{0,20}$/', $prefix)) {
            throw new RuntimeException('Invalid table prefix.');
        }
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Prefixed, quoted table name. */
    public function t(string $table): string
    {
        self::ident($table);
        return '`' . $this->prefix . $table . '`';
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $name = is_int($k) ? $k + 1 : ':' . ltrim((string) $k, ':');
            $stmt->bindValue($name, $v, match (true) {
                is_int($v) => PDO::PARAM_INT,
                is_bool($v) => PDO::PARAM_BOOL,
                $v === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            });
        }
        $stmt->execute();
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $r = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $r === false ? null : $r;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function value(string $sql, array $params = []): mixed
    {
        $v = $this->query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        array_map([self::class, 'ident'], $cols);
        $this->query(
            'INSERT INTO ' . $this->t($table) . ' (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_map(static fn ($c) => ':' . $c, $cols)) . ')',
            $data
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, int $id): int
    {
        $sets = [];
        foreach (array_keys($data) as $c) {
            self::ident($c);
            $sets[] = "`$c` = :$c";
        }
        $data['__id'] = $id;
        return $this->query('UPDATE ' . $this->t($table) . ' SET ' . implode(', ', $sets) . ' WHERE id = :__id', $data)->rowCount();
    }

    public function delete(string $table, int $id): int
    {
        return $this->query('DELETE FROM ' . $this->t($table) . ' WHERE id = ?', [$id])->rowCount();
    }

    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $r = $fn($this);
            $this->pdo->commit();
            return $r;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Run a schema file containing {{prefix}} placeholders, statement by statement. */
    public function runSchema(string $file): void
    {
        $sql = str_replace('{{prefix}}', $this->prefix, (string) file_get_contents($file));
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            $this->pdo->exec($stmt);
        }
    }

    /** All tables that belong to this prefix. */
    public function tables(): array
    {
        if ($this->prefix === '') {
            return [];
        }
        $rows = $this->fetchAll(
            'SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ? ORDER BY table_name',
            [addcslashes($this->prefix, '_%\\') . '%']
        );
        return array_map(static fn ($r) => (string) $r['t'], $rows);
    }

    public static function ident(string $name): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $name)) {
            throw new RuntimeException('Invalid SQL identifier.');
        }
    }
}
