<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth;
use App\Models\Event;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        $events = Event::forUser((int) $user['id']);

        $totals = ['events' => count($events), 'confirmed' => 0, 'waitlisted' => 0, 'checked_in' => 0];

        foreach ($events as $event) {
            $totals['confirmed'] += (int) $event['confirmed_count'];
            $totals['waitlisted'] += (int) $event['waitlisted_count'];
            $totals['checked_in'] += (int) $event['checked_in_count'];
        }

        $upcoming = array_values(array_filter($events, fn (array $event): bool => $event['status'] === 'published' && strtotime($event['starts_at'].' UTC') >= time()));

        view('dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'events' => $events,
            'upcoming' => array_slice($upcoming, 0, 4),
            'totals' => $totals,
        ], 'layouts/app');
    }
}
