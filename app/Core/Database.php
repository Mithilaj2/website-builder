<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO wrapper. All values go through prepared statements.
 * Table/column names passed to insert()/update() must come from code, never from user input;
 * they are additionally checked against a strict identifier pattern.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(array $cfg): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'] ?? 'localhost',
            (int) ($cfg['port'] ?? 3306),
            $cfg['name'] ?? '',
            $cfg['charset'] ?? 'utf8mb4'
        );
        return new PDO($dsn, (string) ($cfg['user'] ?? ''), (string) ($cfg['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect((array) Config::get('db', []));
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : (str_starts_with((string) $key, ':') ? $key : ':' . $key);
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        self::assertIdentifier($table);
        $cols = array_keys($data);
        array_map([self::class, 'assertIdentifier'], $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`,`', $cols),
            implode(',', array_map(static fn ($c) => ':' . $c, $cols))
        );
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    /** Update rows by primary key id. */
    public static function update(string $table, array $data, int $id): int
    {
        self::assertIdentifier($table);
        $sets = [];
        foreach (array_keys($data) as $col) {
            self::assertIdentifier($col);
            $sets[] = "`$col` = :$col";
        }
        $data['__id'] = $id;
        return self::query(sprintf('UPDATE `%s` SET %s WHERE id = :__id', $table, implode(', ', $sets)), $data)->rowCount();
    }

    public static function delete(string $table, int $id): int
    {
        self::assertIdentifier($table);
        return self::query(sprintf('DELETE FROM `%s` WHERE id = :id', $table), ['id' => $id])->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Paginated select. $baseSql must start after SELECT columns: "FROM ... WHERE ...".
     * $orderBy must be a code-controlled, whitelisted ORDER BY clause.
     */
    public static function paginate(string $columns, string $baseSql, array $params, string $orderBy, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $total = (int) self::value('SELECT COUNT(*) ' . $baseSql, $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $rows = self::fetchAll("SELECT $columns $baseSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset", $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => $pages];
    }

    public static function assertIdentifier(string $name): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $name)) {
            throw new RuntimeException('Invalid SQL identifier.');
        }
    }

    /** Escape LIKE wildcards in user search input. */
    public static function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }
}
