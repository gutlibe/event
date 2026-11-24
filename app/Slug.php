<?php

declare(strict_types=1);

namespace App;

use App\Models\Event;

final class Slug
{
    public static function base(string $value, int $max = 80): string
    {
        $value = trim($value);

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }

        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        if ($value === '') {
            $value = 'event';
        }

        return mb_substr($value, 0, $max);
    }

    public static function unique(string $title, ?int $ignoreEventId = null): string
    {
        $base = self::base($title);
        $candidate = $base;
        $suffix = 2;

        while (Event::slugExists($candidate, $ignoreEventId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
