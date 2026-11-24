<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth;
use App\EventPolicy;
use App\Models\Event;
use App\Slug;
use App\ValidationException;
use App\Validator;
use DateTimeImmutable;
use DateTimeZone;

final class EventController
{
    public function index(): void
    {
        $events = Event::forUser((int) Auth::user()['id']);

        $status = (string) ($_GET['status'] ?? '');
        $search = trim((string) ($_GET['q'] ?? ''));

        if (in_array($status, ['published', 'draft', 'cancelled'], true)) {
            $events = array_values(array_filter($events, fn (array $event): bool => $event['status'] === $status));
        } else {
            $status = '';
        }

        if ($search !== '') {
            $events = array_values(array_filter(
                $events,
                fn (array $event): bool => mb_stripos((string) $event['title'], $search) !== false
            ));
        }

        view('events/index', [
            'title' => 'Your events',
            'events' => $events,
            'status' => $status,
            'search' => $search,
        ], 'layouts/app');
    }

    public function create(): void
    {
        view('events/form', [
            'title' => 'Create an event',
            'event' => null,
            'timezones' => $this->timezones(),
            'defaultTimezone' => 'Africa/Accra',
        ], 'layouts/app');
    }

    public function store(): void
    {
        $data = $this->validated();

        $data['user_id'] = (int) Auth::user()['id'];
        $data['slug'] = Slug::unique((string) $data['title']);
        $data['status'] = 'draft';

        $cover = $this->storeCover();

        if ($cover !== null) {
            $data['cover_image'] = $cover;
        }

        $id = Event::create($data);

        flash_set('success', 'Event saved as a draft. Publish it when the details are ready.');
        redirect('/events/'.$id);
    }

    public function show(int $event): void
    {
        $record = $this->owned($event);
        $counts = Event::counts((int) $record['id']);

        view('events/show', [
            'title' => $record['title'],
            'event' => $record,
            'counts' => $counts,
            'seatsLeft' => Event::seatsLeft($record, $counts['confirmed']),
            'shareUrl' => url('/e/'.$record['slug']),
        ], 'layouts/app');
    }

    public function edit(int $event): void
    {
        $record = $this->owned($event);

        view('events/form', [
            'title' => 'Edit '.$record['title'],
            'event' => $record,
            'timezones' => $this->timezones(),
            'defaultTimezone' => $record['timezone'],
        ], 'layouts/app');
    }

    public function update(int $event): void
    {
        $record = $this->owned($event);
        $data = $this->validated();

        $cover = $this->storeCover();

        if ($cover !== null) {
            $data['cover_image'] = $cover;
            $this->deleteCover($record['cover_image'] ?? null);
        }

        Event::update((int) $record['id'], $data);

        flash_set('success', 'Event updated.');
        redirect('/events/'.$record['id']);
    }

    public function publish(int $event): void
    {
        $record = $this->owned($event);

        if (strtotime($record['starts_at'].' UTC') <= time()) {
            flash_set('error', 'Set a start date in the future before publishing.');
            redirect('/events/'.$record['id'].'/edit');
        }

        Event::update((int) $record['id'], [
            'status' => 'published',
            'published_at' => $record['published_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);

        flash_set('success', 'Event is live. Share the public link with your guests.');
        redirect('/events/'.$record['id']);
    }

    public function cancel(int $event): void
    {
        $record = $this->owned($event);
        Event::update((int) $record['id'], ['status' => 'cancelled']);

        flash_set('success', 'Event cancelled. The public page now shows a cancellation notice.');
        redirect('/events/'.$record['id']);
    }

    private function owned(int $eventId): array
    {
        $event = Event::find($eventId);

        if ($event === null) {
            abort(404);
        }

        return EventPolicy::assertOwner($event);
    }

    private function validated(): array
    {
        $clean = Validator::validate($_POST, [
            'title' => 'required|min:3|max:160',
            'summary' => 'nullable|max:255',
            'description' => 'nullable|max:20000',
            'venue_name' => 'nullable|max:160',
            'venue_address' => 'nullable|max:255',
            'timezone' => 'required|timezone',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'capacity' => 'nullable|integer|min:1|max:100000',
        ]);

        $timezone = (string) $clean['timezone'];

        $clean['starts_at'] = $this->toUtc((string) $clean['starts_at'], $timezone);
        $clean['ends_at'] = $clean['ends_at'] !== null ? $this->toUtc((string) $clean['ends_at'], $timezone) : null;
        $clean['capacity'] = $clean['capacity'] !== null ? (int) $clean['capacity'] : null;
        $clean['summary'] = $clean['summary'] ?? null;

        return $clean;
    }

    private function toUtc(string $local, string $timezone): string
    {
        $date = new DateTimeImmutable($local, tz($timezone));

        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function timezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    private function storeCover(): ?string
    {
        $file = $_FILES['cover'] ?? null;

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new ValidationException(['cover' => 'The cover image could not be uploaded.']);
        }

        if ($file['size'] > 4 * 1024 * 1024) {
            throw new ValidationException(['cover' => 'Cover images must be 4MB or smaller.']);
        }

        $info = @getimagesize($file['tmp_name']);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new ValidationException(['cover' => 'Upload a JPG, PNG or WebP image.']);
        }

        $contents = file_get_contents($file['tmp_name']);
        $image = $contents !== false ? @imagecreatefromstring($contents) : false;

        if ($image === false) {
            throw new ValidationException(['cover' => 'That image could not be read.']);
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > 1600) {
            $resized = imagescale($image, 1600, (int) round($height * 1600 / $width), IMG_BICUBIC);

            if ($resized !== false) {
                imagedestroy($image);
                $image = $resized;
            }
        }

        $name = bin2hex(random_bytes(10)).'.jpg';
        $directory = public_path('uploads/covers');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        imagejpeg($image, $directory.'/'.$name, 86);
        imagedestroy($image);

        return 'uploads/covers/'.$name;
    }

    private function deleteCover(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, 'uploads/covers/')) {
            return;
        }

        $full = public_path($path);

        if (is_file($full)) {
            @unlink($full);
        }
    }
}
