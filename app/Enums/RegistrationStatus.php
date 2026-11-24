<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationStatus: string
{
    case Confirmed = 'confirmed';
    case Waitlisted = 'waitlisted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Waitlisted => 'Waitlisted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Confirmed => 'badge-live',
            self::Waitlisted => 'badge-waitlist',
            self::Cancelled => 'badge-cancelled',
        };
    }
}
