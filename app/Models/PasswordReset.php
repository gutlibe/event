<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class PasswordReset
{
    public static function createFor(string $email): string
    {
        $token = bin2hex(random_bytes(40));
        $hash = hash('sha256', $token);

        Database::execute(
            'REPLACE INTO password_resets (email, token_hash, expires_at, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP())',
            [mb_strtolower($email), $hash, gmdate('Y-m-d H:i:s', time() + 3600)]
        );

        return $token;
    }

    public static function find(string $email, string $token): ?array
    {
        return Database::first(
            'SELECT * FROM password_resets WHERE email = ? AND token_hash = ? AND expires_at > UTC_TIMESTAMP()',
            [mb_strtolower($email), hash('sha256', $token)]
        );
    }

    public static function delete(string $email): void
    {
        Database::execute('DELETE FROM password_resets WHERE email = ?', [mb_strtolower($email)]);
    }
}
