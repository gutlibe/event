<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                (string) env('DB_HOST', '127.0.0.1'),
                (string) env('DB_PORT', '3306'),
                (string) env('DB_DATABASE', 'event')
            );

            self::$pdo = new PDO($dsn, (string) env('DB_USERNAME', 'root'), (string) env('DB_PASSWORD', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }

    public static function select(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::execute($sql, $params);

        return (int) self::pdo()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}
