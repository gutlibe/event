<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Ics;
use App\Mailer;
use App\Models\Event;
use App\Models\Registration;
use App\Reference;
use App\ValidationException;

/**
 * The only place capacity and waitlist decisions are made.
 */
final class RegistrationService
{
    /**
     * Register an email for an event, honouring capacity under a row lock.
     *
     * @return array{registration:array, already:bool, waitlist_position:?int}
     */
    public static function register(array $event, array $data): array
    {
        $result = Database::transaction(function () use ($event, $data): array {
            // Serialise capacity checks for this event.
            $locked = Database::first('SELECT * FROM events WHERE id = ? FOR UPDATE', [$event['id']]);

            if ($locked === null) {
                throw new \RuntimeException('Event not found.');
            }

            $existing = Database::first(
                'SELECT * FROM registrations WHERE event_id = ? AND email = ?',
                [$locked['id'], mb_strtolower((string) $data['email'])]
            );

            if ($existing !== null && $existing['status'] !== 'cancelled') {
                return ['registration' => $existing, 'already' => true];
            }

            $confirmed = (int) Database::value(
                "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'confirmed'",
                [$locked['id']]
            );

            $capacity = $locked['capacity'] !== null ? (int) $locked['capacity'] : null;
            $status = ($capacity === null || $confirmed < $capacity) ? 'confirmed' : 'waitlisted';

            if ($existing !== null) {
                Database::execute(
                    'UPDATE registrations
                     SET name = ?, phone = ?, status = ?, cancelled_at = NULL, checked_in_at = NULL, reminder_sent_at = NULL, updated_at = UTC_TIMESTAMP()
                     WHERE id = ?',
                    [
                        $data['name'],
                        $data['phone'] ?? null,
                        $status,
                        $existing['id'],
                    ]
                );

                $registration = Registration::find((int) $existing['id']);
            } else {
                $id = Database::insert(
                    'INSERT INTO registrations (event_id, reference, name, email, phone, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                    [
                        $locked['id'],
                        Reference::unique(),
                        $data['name'],
                        mb_strtolower((string) $data['email']),
                        $data['phone'] ?? null,
                        $status,
                    ]
                );

                $registration = Registration::find($id);
            }

            if ($registration === null) {
                throw new \RuntimeException('Registration could not be saved.');
            }

            $position = $status === 'waitlisted'
                ? Registration::waitlistPosition((int) $locked['id'], (int) $registration['id'])
                : null;

            return ['registration' => $registration, 'already' => false, 'waitlist_position' => $position];
        });

        if (! $result['already']) {
            self::sendRegistrationMail($event, $result['registration'], $result['waitlist_position'] ?? null);
        }

        return $result;
    }

    /**
     * Cancel a registration and promote the oldest waitlisted attendee.
     *
     * @return array{registration:array, promoted:?array}
     */
    public static function cancel(int $registrationId): array
    {
        $result = Database::transaction(function () use ($registrationId): array {
            $registration = Database::first('SELECT * FROM registrations WHERE id = ?', [$registrationId]);

            if ($registration === null) {
                throw new \RuntimeException('Registration not found.');
            }

            $event = Database::first('SELECT * FROM events WHERE id = ? FOR UPDATE', [$registration['event_id']]);

            if ($registration['status'] !== 'cancelled') {
                Database::execute(
                    "UPDATE registrations SET status = 'cancelled', cancelled_at = UTC_TIMESTAMP(), checked_in_at = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?",
                    [$registrationId]
                );
            }

            $promotedId = null;

            if ($event !== null && $event['status'] !== 'cancelled') {
                $promotedId = Database::value(
                    "SELECT id FROM registrations
                     WHERE event_id = ? AND status = 'waitlisted'
                     ORDER BY created_at ASC, id ASC LIMIT 1
                     FOR UPDATE",
                    [$event['id']]
                );

                if ($promotedId !== null) {
                    Database::execute(
                        "UPDATE registrations SET status = 'confirmed', updated_at = UTC_TIMESTAMP() WHERE id = ?",
                        [(int) $promotedId]
                    );
                }
            }

            return [
                'registration' => Registration::find($registrationId),
                'promoted' => $promotedId !== null ? Registration::find((int) $promotedId) : null,
            ];
        });

        if ($result['promoted'] !== null) {
            $event = Event::find((int) $result['registration']['event_id']);

            if ($event !== null) {
                self::sendPromotionMail($event, $result['promoted']);
            }
        }

        return $result;
    }

    public static function sendRegistrationMail(array $event, array $registration, ?int $waitlistPosition = null): void
    {
        $confirmed = $registration['status'] === 'confirmed';
        $subject = $confirmed
            ? "You're in — ".$event['title']
            : "You're on the waitlist — ".$event['title'];

        $html = render('emails.registration', [
            'event' => $event,
            'registration' => $registration,
            'confirmed' => $confirmed,
            'waitlistPosition' => $waitlistPosition,
        ]);

        Mailer::send((string) $registration['email'], $subject, $html, [[
            'name' => 'event.ics',
            'mime' => 'text/calendar; method=PUBLISH',
            'content' => Ics::forEvent($event, (string) $registration['reference']),
        ]]);
    }

    public static function sendPromotionMail(array $event, array $registration): void
    {
        $html = render('emails.registration', [
            'event' => $event,
            'registration' => $registration,
            'confirmed' => true,
            'waitlistPosition' => null,
            'promoted' => true,
        ]);

        Mailer::send((string) $registration['email'], 'A seat opened up — '.$event['title'], $html, [[
            'name' => 'event.ics',
            'mime' => 'text/calendar; method=PUBLISH',
            'content' => Ics::forEvent($event, (string) $registration['reference']),
        ]]);
    }

    /**
     * Queue is the log file for v1; call this from the scheduled command.
     */
    public static function sendDueReminders(): int
    {
        $events = Database::select(
            "SELECT * FROM events
             WHERE status = 'published'
               AND starts_at BETWEEN UTC_TIMESTAMP() + INTERVAL 23 HOUR AND UTC_TIMESTAMP() + INTERVAL 25 HOUR"
        );

        $sent = 0;

        foreach ($events as $event) {
            $registrations = Database::select(
                "SELECT * FROM registrations
                 WHERE event_id = ? AND status = 'confirmed' AND reminder_sent_at IS NULL",
                [$event['id']]
            );

            foreach ($registrations as $registration) {
                $html = render('emails.reminder', [
                    'event' => $event,
                    'registration' => $registration,
                ]);

                Mailer::send((string) $registration['email'], 'Tomorrow — '.$event['title'], $html, [[
                    'name' => 'event.ics',
                    'mime' => 'text/calendar; method=PUBLISH',
                    'content' => Ics::forEvent($event, (string) $registration['reference']),
                ]]);

                Database::execute(
                    'UPDATE registrations SET reminder_sent_at = UTC_TIMESTAMP() WHERE id = ?',
                    [$registration['id']]
                );

                $sent++;
            }
        }

        return $sent;
    }

    public static function validateRegistration(array $input): array
    {
        try {
            return \App\Validator::validate($input, [
                'name' => 'required|max:120',
                'email' => 'required|email|max:190',
                'phone' => 'nullable|max:32',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }
    }
}
