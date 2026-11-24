<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class Registration
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM registrations WHERE id = ?', [$id]);
    }

    public static function findByReference(int $eventId, string $reference): ?array
    {
        return Database::first(
            'SELECT * FROM registrations WHERE event_id = ? AND reference = ?',
            [$eventId, $reference]
        );
    }

    public static function forEvent(int $eventId, string $search = '', string $status = ''): array
    {
        $sql = 'SELECT * FROM registrations WHERE event_id = ?';
        $params = [$eventId];

        if ($status !== '') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ? OR reference LIKE ? OR phone LIKE ?)';
            $like = '%'.$search.'%';
            array_push($params, $like, $like, $like, $like);
        }

        $sql .= " ORDER BY
            CASE status WHEN 'confirmed' THEN 0 WHEN 'waitlisted' THEN 1 ELSE 2 END,
            created_at ASC";

        return Database::select($sql, $params);
    }

    public static function counts(int $eventId): array
    {
        return Event::counts($eventId);
    }

    public static function checkIn(int $id): bool
    {
        return Database::execute(
            'UPDATE registrations SET checked_in_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ? AND checked_in_at IS NULL',
            [$id]
        ) > 0;
    }

    public static function undoCheckIn(int $id): void
    {
        Database::execute(
            'UPDATE registrations SET checked_in_at = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$id]
        );
    }

    public static function waitlistPosition(int $eventId, int $registrationId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'waitlisted' AND created_at <= (SELECT created_at FROM registrations WHERE id = ?)",
            [$eventId, $registrationId]
        );
    }
}
