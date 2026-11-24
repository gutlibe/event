<?php

declare(strict_types=1);

namespace App;

use App\Models\Event;

final class EventPolicy
{
    public static function assertOwner(array $event): array
    {
        $user = Auth::user();

        if ($user === null) {
            Auth::requireLogin();
        }

        if ((int) $user['id'] !== (int) $event['user_id']) {
            abort(403, 'You can only manage events you created.');
        }

        return $event;
    }

    public static function canManage(array $event): bool
    {
        $user = Auth::user();

        return $user !== null && (int) $user['id'] === (int) $event['user_id'];
    }
}
