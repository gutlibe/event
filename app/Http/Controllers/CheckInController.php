<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\EventPolicy;
use App\Models\Event;
use App\Models\Registration;

final class CheckInController
{
    public function index(int $event): void
    {
        $record = $this->owned($event);
        $search = trim((string) ($_GET['q'] ?? ''));

        $matches = $search !== ''
            ? Registration::forEvent((int) $record['id'], $search)
            : [];

        view('attendees/checkin', [
            'title' => 'Check in — '.$record['title'],
            'event' => $record,
            'matches' => $matches,
            'search' => $search,
            'counts' => Event::counts((int) $record['id']),
        ], 'layouts/app');
    }

    public function store(int $event): void
    {
        $record = $this->owned($event);
        $registration = Registration::find((int) ($_POST['registration_id'] ?? 0));

        if ($registration === null || (int) $registration['event_id'] !== (int) $record['id']) {
            abort(404);
        }

        if ($registration['status'] !== 'confirmed') {
            flash_set('error', $registration['name'].' is not on the confirmed list.');
        } else {
            Registration::checkIn((int) $registration['id']);
            flash_set('success', $registration['name'].' is checked in.');
        }

        $search = trim((string) ($_POST['q'] ?? ''));

        redirect('/events/'.$record['id'].'/check-in'.($search !== '' ? '?q='.urlencode($search) : ''));
    }

    public function undo(int $event, int $registration): void
    {
        $record = $this->owned($event);
        $target = Registration::find($registration);

        if ($target === null || (int) $target['event_id'] !== (int) $record['id']) {
            abort(404);
        }

        Registration::undoCheckIn((int) $target['id']);
        flash_set('success', 'Check-in undone for '.$target['name'].'.');

        redirect('/events/'.$record['id'].'/check-in');
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
