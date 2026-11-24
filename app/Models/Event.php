<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class Event
{
    public const FIELDS = [
        'title', 'summary', 'description', 'cover_image', 'venue_name', 'venue_address',
        'timezone', 'starts_at', 'ends_at', 'capacity', 'status', 'published_at',
    ];

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM events WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first('SELECT * FROM events WHERE slug = ?', [$slug]);
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM events WHERE slug = ?';
        $params = [$slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        return Database::first($sql, $params) !== null;
    }

    public static function forUser(int $userId): array
    {
        return Database::select(
            "SELECT e.*,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'confirmed') AS confirmed_count,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'waitlisted') AS waitlisted_count,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.checked_in_at IS NOT NULL) AS checked_in_count
             FROM events e
             WHERE e.user_id = ?
             ORDER BY
                CASE e.status WHEN 'published' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END,
                e.starts_at DESC",
            [$userId]
        );
    }

    public static function upcomingPublished(int $limit = 12): array
    {
        return Database::select(
            "SELECT e.*,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'confirmed') AS confirmed_count,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'waitlisted') AS waitlisted_count
             FROM events e
             WHERE e.status = 'published' AND e.starts_at >= UTC_TIMESTAMP()
             ORDER BY e.starts_at ASC
             LIMIT ".max(1, min(50, $limit))
        );
    }

    public static function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO events
                (user_id, slug, title, summary, description, cover_image, venue_name, venue_address,
                 timezone, starts_at, ends_at, capacity, status, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [
                $data['user_id'],
                $data['slug'],
                $data['title'],
                $data['summary'] ?? null,
                $data['description'] ?? null,
                $data['cover_image'] ?? null,
                $data['venue_name'] ?? null,
                $data['venue_address'] ?? null,
                $data['timezone'] ?? 'UTC',
                $data['starts_at'],
                $data['ends_at'] ?? null,
                $data['capacity'] ?? null,
                $data['status'] ?? 'draft',
                $data['published_at'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        $columns = [];
        $values = [];

        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $columns[] = $field.' = ?';
                $values[] = $data[$field];
            }
        }

        if ($columns === []) {
            return;
        }

        $columns[] = 'updated_at = UTC_TIMESTAMP()';
        $values[] = $id;

        Database::execute('UPDATE events SET '.implode(', ', $columns).' WHERE id = ?', $values);
    }

    public static function counts(int $eventId): array
    {
        $counts = [
            'confirmed' => 0,
            'waitlisted' => 0,
            'cancelled' => 0,
            'checked_in' => 0,
        ];

        foreach (Database::select(
            'SELECT status, COUNT(*) AS total FROM registrations WHERE event_id = ? GROUP BY status',
            [$eventId]
        ) as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        $counts['checked_in'] = (int) Database::value(
            'SELECT COUNT(*) FROM registrations WHERE event_id = ? AND checked_in_at IS NOT NULL',
            [$eventId]
        );

        return $counts;
    }

    public static function seatsLeft(array $event, ?int $confirmed = null): ?int
    {
        if ($event['capacity'] === null) {
            return null;
        }

        $confirmed ??= (int) Database::value(
            "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'confirmed'",
            [$event['id']]
        );

        return max(0, (int) $event['capacity'] - $confirmed);
    }
}
