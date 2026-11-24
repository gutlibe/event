<?php

declare(strict_types=1);

namespace App;

final class Ics
{
    /**
     * Build a single-event calendar file in the event's own timezone.
     */
    public static function forEvent(array $event, ?string $reference = null): string
    {
        $timezone = (string) $event['timezone'];
        $zone = tz($timezone);
        $start = (new \DateTimeImmutable((string) $event['starts_at'], new \DateTimeZone('UTC')))->setTimezone($zone);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Gather//Events//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape((string) $event['title']),
            'X-WR-TIMEZONE:'.$timezone,
            'BEGIN:VTIMEZONE',
            'TZID:'.$timezone,
            'X-LIC-LOCATION:'.$timezone,
            'BEGIN:STANDARD',
            'DTSTART:19700101T000000',
            'TZOFFSETFROM:'.self::offset($start->format('P')),
            'TZOFFSETTO:'.self::offset($start->format('P')),
            'TZNAME:'.self::escape($start->format('T')),
            'END:STANDARD',
            'END:VTIMEZONE',
            'BEGIN:VEVENT',
            'UID:'.($reference ? $reference.'-' : '').($event['id'] ?? '').'@'.parse_url((string) env('APP_URL', 'http://event.test'), PHP_URL_HOST),
            'DTSTAMP:'.gmdate('Ymd\\THis\\Z'),
            'DTSTART;TZID='.$timezone.':'.$start->format('Ymd\\THis'),
        ];

        if (! empty($event['ends_at'])) {
            $end = (new \DateTimeImmutable((string) $event['ends_at'], new \DateTimeZone('UTC')))->setTimezone($zone);
            $lines[] = 'DTEND;TZID='.$timezone.':'.$end->format('Ymd\\THis');
        }

        $lines[] = 'SUMMARY:'.self::escape((string) $event['title']);

        $location = trim(implode(', ', array_filter([
            $event['venue_name'] ?? null,
            $event['venue_address'] ?? null,
        ])));

        if ($location !== '') {
            $lines[] = 'LOCATION:'.self::escape($location);
        }

        if (! empty($event['description'])) {
            $lines[] = 'DESCRIPTION:'.self::escape(strip_tags((string) $event['description']));
        }

        $lines[] = 'URL:'.url('/e/'.($event['slug'] ?? ''));
        $lines[] = 'STATUS:CONFIRMED';
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'fold'], $lines))."\r\n";
    }

    private static function offset(string $isoOffset): string
    {
        // +01:00 -> +0100
        return str_replace(':', '', $isoOffset);
    }

    private static function escape(string $value): string
    {
        $value = str_replace(["\\", ';', ','], ['\\\\', '\\;', '\\,'], $value);
        $value = str_replace(["\r\n", "\n", "\r"], '\\n', $value);

        return $value;
    }

    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = substr($line, 0, 75);
        $rest = substr($line, 75);

        while ($rest !== '') {
            $out .= "\r\n ".substr($rest, 0, 74);
            $rest = substr($rest, 74);
        }

        return $out;
    }
}
