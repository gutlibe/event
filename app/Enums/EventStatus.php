<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Live',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-draft',
            self::Published => 'badge-live',
            self::Cancelled => 'badge-cancelled',
        };
    }
}
