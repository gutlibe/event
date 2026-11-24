<?php

declare(strict_types=1);

namespace App;

final class RateLimiter
{
    /**
     * Register a hit for the bucket; true when the limit is exceeded.
     */
    public static function tooMany(string $bucket, int $max, int $decaySeconds): bool
    {
        $row = Database::first('SELECT hits, resets_at FROM rate_limits WHERE bucket = ?', [$bucket]);
        $now = time();

        if ($row === null || strtotime((string) $row['resets_at'].' UTC') <= $now) {
            Database::execute(
                'REPLACE INTO rate_limits (bucket, hits, resets_at) VALUES (?, 1, ?)',
                [$bucket, gmdate('Y-m-d H:i:s', $now + $decaySeconds)]
            );

            return false;
        }

        Database::execute('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = ?', [$bucket]);

        return ((int) $row['hits'] + 1) > $max;
    }

    public static function clear(string $bucket): void
    {
        Database::execute('DELETE FROM rate_limits WHERE bucket = ?', [$bucket]);
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
