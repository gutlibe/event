<?php

declare(strict_types=1);

/**
 * Plain-PHP test runner. Uses a separate `event_test` database, so it never
 * touches the development data.
 *
 *   php tests/run.php
 */

use App\Auth;
use App\Database;
use App\EventPolicy;
use App\Ics;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Services\RegistrationService;
use App\Slug;
use App\ValidationException;
use App\Validator;

require dirname(__DIR__).'/app/bootstrap.php';

$_ENV['MAIL_MAILER'] = 'array';

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$user = (string) env('DB_USERNAME', 'root');
$password = (string) env('DB_PASSWORD', '');

$root = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$root->exec('CREATE DATABASE IF NOT EXISTS event_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$_ENV['DB_DATABASE'] = 'event_test';
putenv('DB_DATABASE=event_test');

$schema = (string) file_get_contents(base_path('database/schema.sql'));

foreach (explode(';', $schema) as $statement) {
    $statement = trim($statement);

    if ($statement !== '') {
        Database::pdo()->exec($statement);
    }
}

foreach (['registrations', 'events', 'password_resets', 'rate_limits', 'users'] as $table) {
    Database::execute('DELETE FROM '.$table);
}

$organizer = User::find(User::create('Test Organizer', 'organizer@test.local', 'password'));

function make_event(array $overrides = []): array
{
    global $organizer;

    $defaults = [
        'user_id' => (int) $organizer['id'],
        'slug' => Slug::unique('Test Event '.bin2hex(random_bytes(4))),
        'title' => 'Test Event',
        'timezone' => 'UTC',
        'starts_at' => gmdate('Y-m-d H:i:s', time() + 86400),
        'ends_at' => null,
        'capacity' => null,
        'status' => 'published',
    ];

    $id = Event::create(array_merge($defaults, $overrides));

    return Event::find($id);
}

$failures = 0;
$passed = 0;

function test(string $name, callable $test): void
{
    global $failures, $passed;

    try {
        $test();
        $passed++;
        echo "  PASS  {$name}\n";
    } catch (Throwable $e) {
        $failures++;
        echo "  FAIL  {$name}\n";
        echo '        '.$e->getMessage()."\n";
    }
}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            ($message !== '' ? $message.': ' : '').
            'expected '.var_export($expected, true).', got '.var_export($actual, true)
        );
    }
}

ob_start();

echo "Gather test suite (event_test)\n\n";

test('capacity: last seat confirmed, overflow waitlisted', function (): void {
    $event = make_event(['capacity' => 2]);

    $first = RegistrationService::register($event, ['name' => 'One', 'email' => 'one@test.local', 'phone' => null]);
    $second = RegistrationService::register($event, ['name' => 'Two', 'email' => 'two@test.local', 'phone' => null]);
    $third = RegistrationService::register($event, ['name' => 'Three', 'email' => 'three@test.local', 'phone' => null]);

    same('confirmed', $first['registration']['status']);
    same('confirmed', $second['registration']['status']);
    same('waitlisted', $third['registration']['status']);
    same(1, $third['waitlist_position']);
    same(0, Event::seatsLeft($event));
});

test('cancelling promotes the oldest waitlisted guest', function (): void {
    $event = make_event(['capacity' => 1]);

    $first = RegistrationService::register($event, ['name' => 'First', 'email' => 'first@test.local', 'phone' => null]);
    $waiting = RegistrationService::register($event, ['name' => 'Waiting', 'email' => 'waiting@test.local', 'phone' => null]);

    $result = RegistrationService::cancel((int) $first['registration']['id']);

    same('cancelled', Registration::find((int) $first['registration']['id'])['status']);
    same((int) $waiting['registration']['id'], (int) $result['promoted']['id']);
    same('confirmed', Registration::find((int) $waiting['registration']['id'])['status']);
});

test('one registration per email per event', function (): void {
    $event = make_event(['capacity' => 10]);

    RegistrationService::register($event, ['name' => 'Dupe', 'email' => 'dupe@test.local', 'phone' => null]);
    $again = RegistrationService::register($event, ['name' => 'Dupe Again', 'email' => 'dupe@test.local', 'phone' => null]);

    check($again['already'] === true, 'second attempt should be flagged as already registered');
    same(1, count(Registration::forEvent((int) $event['id'])));
});

test('cancelled guest can register again on the same row', function (): void {
    $event = make_event(['capacity' => 5]);

    $registration = RegistrationService::register($event, ['name' => 'Returning', 'email' => 'returning@test.local', 'phone' => null]);
    RegistrationService::cancel((int) $registration['registration']['id']);

    $again = RegistrationService::register($event, ['name' => 'Returning', 'email' => 'returning@test.local', 'phone' => '+233240000000']);

    same((int) $registration['registration']['id'], (int) $again['registration']['id']);
    same('confirmed', $again['registration']['status']);
    check($again['registration']['cancelled_at'] === null, 'cancelled_at should be cleared');
    same(1, count(Registration::forEvent((int) $event['id'])));
});

test('registration validation rejects bad input', function (): void {
    $caught = null;

    try {
        Validator::validate(['name' => '', 'email' => 'not-an-email'], [
            'name' => 'required|max:120',
            'email' => 'required|email',
        ]);
    } catch (ValidationException $e) {
        $caught = $e;
    }

    check($caught !== null, 'invalid input should throw');
    check(isset($caught->errors['name']), 'name should have an error');
    check(isset($caught->errors['email']), 'email should have an error');
});

test('slug collisions get a numeric suffix', function (): void {
    make_event(['slug' => 'collision-slug', 'title' => 'Collision Slug']);

    same('collision-slug-2', Slug::unique('Collision Slug'));
});

test('policy stops one organizer touching another event', function (): void {
    global $organizer;

    $other = User::find(User::create('Other Organizer', 'other@test.local', 'password'));
    $mine = make_event(['title' => 'Mine']);
    $theirs = make_event(['title' => 'Theirs', 'user_id' => (int) $other['id']]);

    Auth::login($organizer);

    check(EventPolicy::canManage($mine), 'owner should manage their event');
    check(! EventPolicy::canManage($theirs), 'owner should not manage another organizer event');
});

test('check-in is idempotent', function (): void {
    $event = make_event(['capacity' => 5]);
    $registration = RegistrationService::register($event, ['name' => 'Door', 'email' => 'door@test.local', 'phone' => null]);
    $id = (int) $registration['registration']['id'];

    check(Registration::checkIn($id) === true, 'first check-in should change the row');
    $firstTime = Registration::find($id)['checked_in_at'];

    check(Registration::checkIn($id) === false, 'second check-in should be a no-op');
    same($firstTime, Registration::find($id)['checked_in_at']);
});

test('event times render in the event timezone and .ics uses TZID', function (): void {
    $event = make_event([
        'timezone' => 'Africa/Lagos',
        'starts_at' => '2026-12-04 19:00:00',
        'ends_at' => '2026-12-04 21:00:00',
    ]);

    same('Fri, 4 Dec 2026 · 8:00 PM', fmt_datetime($event['starts_at'], 'Africa/Lagos'));
    check(str_contains(Ics::forEvent($event), 'DTSTART;TZID=Africa/Lagos:20261204T200000'), '.ics should use the local start time');
    check(str_contains(Ics::forEvent($event), 'BEGIN:VTIMEZONE'), '.ics should include a VTIMEZONE block');
});

$output = ob_get_clean();
echo $output;
echo "\n{$passed} passed, {$failures} failed\n";

exit($failures > 0 ? 1 : 0);
