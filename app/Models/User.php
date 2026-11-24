<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class User
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::first('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    }

    public static function create(string $name, string $email, string $password): int
    {
        return Database::insert(
            'INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [$name, mb_strtolower($email), password_hash($password, PASSWORD_DEFAULT)]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::execute(
            'UPDATE users SET password = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }
}
