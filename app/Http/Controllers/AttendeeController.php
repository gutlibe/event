<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\EventPolicy;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;

final class AttendeeController
{
    public function index(int $event): void
    {
        $record = $this->owned($event);
        $search = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? '');
        $status = in_array($status, ['confirmed', 'waitlisted', 'cancelled'], true) ? $status : '';

        $registrations = Registration::forEvent((int) $record['id'], $search, $status);

        view('attendees/index', [
            'title' => 'Attendees — '.$record['title'],
            'event' => $record,
            'registrations' => $registrations,
            'counts' => Event::counts((int) $record['id']),
            'search' => $search,
            'status' => $status,
            'seatsLeft' => Event::seatsLeft($record),
        ], 'layouts/app');
    }

    public function cancel(int $event, int $registration): void
    {
        $record = $this->owned($event);
        $target = Registration::find($registration);

        if ($target === null || (int) $target['event_id'] !== (int) $record['id']) {
            abort(404);
        }

        $result = RegistrationService::cancel((int) $target['id']);

        flash_set('success', $result['promoted'] !== null
            ? $target['name'].' was cancelled and '.$result['promoted']['name'].' was promoted from the waitlist.'
            : $target['name'].' was cancelled.');

        redirect('/events/'.$record['id'].'/attendees');
    }

    public function export(int $event): void
    {
        $record = $this->owned($event);
        $registrations = Registration::forEvent((int) $record['id']);

        $filename = preg_replace('/[^a-z0-9-]+/', '-', strtolower($record['slug'])).'-attendees.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');

        // UTF-8 BOM so Excel opens accented names correctly.
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Name', 'Email', 'Phone', 'Reference', 'Status', 'Checked in', 'Booked at'], ',', '"', '');

        foreach ($registrations as $registration) {
            fputcsv($out, [
                $registration['name'],
                $registration['email'],
                $registration['phone'] ?? '',
                $registration['reference'],
                ucfirst((string) $registration['status']),
                $registration['checked_in_at'] ? fmt_datetime($registration['checked_in_at'], (string) $record['timezone']) : '',
                fmt_datetime($registration['created_at'], (string) $record['timezone']),
            ], ',', '"', '');
        }

        fclose($out);
        exit;
    }

    private function owned(int $eventId): array
    {
        $record = Event::find($eventId);

        if ($record === null) {
            abort(404);
        }

        return EventPolicy::assertOwner($record);
    }
}
