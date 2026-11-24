<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ics;
use App\Models\Event;
use App\Models\Registration;
use App\RateLimiter;
use App\Services\RegistrationService;
use App\Slug;
use App\ValidationException;
use App\Validator;

final class PublicEventController
{
    public function home(): void
    {
        view('home', [
            'title' => 'Gather — events without the admin',
            'events' => Event::upcomingPublished(9),
        ]);
    }

    public function show(string $slug): void
    {
        $event = $this->visibleEvent($slug);
        $counts = Event::counts((int) $event['id']);
        $ended = strtotime($event['starts_at'].' UTC') <= time();

        view('public/event', [
            'title' => $event['title'],
            'event' => $event,
            'counts' => $counts,
            'seatsLeft' => Event::seatsLeft($event, $counts['confirmed']),
            'ended' => $ended,
            'canRegister' => $event['status'] === 'published' && ! $ended,
        ]);
    }

    public function register(string $slug): void
    {
        $event = $this->visibleEvent($slug);
        $ended = strtotime($event['starts_at'].' UTC') <= time();

        if ($event['status'] !== 'published' || $ended) {
            flash_set('error', 'Registration is closed for this event.');
            redirect('/e/'.$event['slug']);
        }

        if (RateLimiter::tooMany('register:'.$event['id'].':'.RateLimiter::ip(), 5, 60)) {
            throw new ValidationException(['email' => 'Too many registrations from this connection. Please wait a minute.']);
        }

        $data = Validator::validate($_POST, [
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|max:32',
        ]);

        $result = RegistrationService::register($event, $data);

        if ($result['already']) {
            flash_set('success', 'You are already registered. Here is your ticket.');
        } else {
            flash_set('success', $result['registration']['status'] === 'confirmed'
                ? "You're in. We emailed your ticket reference."
                : "You're on the waitlist. We'll email you if a seat opens up.");
        }

        redirect('/e/'.$event['slug'].'/registered/'.$result['registration']['reference']);
    }

    public function registered(string $slug, string $reference): void
    {
        $event = $this->visibleEvent($slug);
        $registration = Registration::findByReference((int) $event['id'], $reference);

        if ($registration === null) {
            abort(404);
        }

        $position = $registration['status'] === 'waitlisted'
            ? Registration::waitlistPosition((int) $event['id'], (int) $registration['id'])
            : null;

        view('public/registered', [
            'title' => 'Your registration',
            'event' => $event,
            'registration' => $registration,
            'position' => $position,
            'seatsLeft' => Event::seatsLeft($event),
        ]);
    }

    public function cancel(string $slug, string $reference): void
    {
        $event = $this->visibleEvent($slug);
        $registration = Registration::findByReference((int) $event['id'], $reference);

        if ($registration === null) {
            abort(404);
        }

        $result = RegistrationService::cancel((int) $registration['id']);

        flash_set('success', $result['promoted'] !== null
            ? 'Your spot has been released and offered to the next person on the waitlist.'
            : 'Your registration has been cancelled.');

        redirect('/e/'.$event['slug'].'/registered/'.$registration['reference']);
    }

    public function calendar(string $slug): void
    {
        $event = $this->visibleEvent($slug);
        $ics = Ics::forEvent($event);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.Slug::base($event['title']).'.ics"');
        header('Content-Length: '.strlen($ics));

        echo $ics;
        exit;
    }

    private function visibleEvent(string $slug): array
    {
        $event = Event::findBySlug($slug);

        if ($event === null) {
            abort(404);
        }

        if ($event['status'] === 'draft') {
            abort(404);
        }

        return $event;
    }
}
