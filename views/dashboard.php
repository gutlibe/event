<?php $firstName = explode(' ', (string) $user['name'])[0]; ?>
<div class="container-page py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Organizer dashboard</p>
            <h1 class="mt-2 text-3xl sm:text-4xl">Hello, <?= e($firstName) ?></h1>
            <p class="mt-2 text-sm text-ink-500">Here's how your events are doing.</p>
        </div>
        <a href="/events/create" class="btn-coral btn-sm sm:btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Create an event
        </a>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <?php
        $stats = [
            ['Events', $totals['events'], 'Total created', 'text-ink-900'],
            ['Going', $totals['confirmed'], 'Confirmed guests', 'text-moss-700'],
            ['Waitlist', $totals['waitlisted'], 'Waiting for a seat', 'text-gold-700'],
            ['Checked in', $totals['checked_in'], 'At the door', 'text-coral-600'],
        ];
        ?>
        <?php foreach ($stats as [$label, $value, $hint, $color]): ?>
            <div class="card p-5">
                <p class="text-[11px] font-semibold tracking-wider text-ink-400 uppercase"><?= e($label) ?></p>
                <p class="mt-2 text-3xl font-semibold <?= e($color) ?>"><?= (int) $value ?></p>
                <p class="mt-1 text-xs text-ink-500"><?= e($hint) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($events === []): ?>
        <div class="mt-8 rounded-3xl border border-dashed border-cream-300 bg-white/50 px-6 py-16 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-cream-100">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-ink-500"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
            </div>
            <h2 class="mt-4 text-2xl">No events yet</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-ink-500">Create a draft, add the details, and publish when you're ready.</p>
            <a href="/events/create" class="btn-primary mt-6">Create your first event</a>
        </div>
    <?php else: ?>
        <div class="mt-10 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl">Up next</h2>
                <p class="mt-1 text-sm text-ink-500">Published events that haven't happened yet.</p>
            </div>
            <a href="/events" class="link text-sm whitespace-nowrap">All events</a>
        </div>

        <?php if ($upcoming === []): ?>
            <div class="mt-4 rounded-2xl border border-cream-200 bg-white/60 p-6 text-sm text-ink-500">
                Nothing scheduled right now. <a href="/events/create" class="link">Create an event</a> or publish a draft.
            </div>
        <?php else: ?>
            <div class="mt-4 space-y-2.5">
                <?php foreach ($upcoming as $event): ?>
                    <?php partial('event-row', ['event' => $event]); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-10 flex items-end justify-between gap-4">
            <h2 class="text-2xl">Recently created</h2>
            <a href="/events" class="link text-sm whitespace-nowrap">View all</a>
        </div>
        <div class="mt-4 space-y-2.5">
            <?php foreach (array_slice($events, 0, 4) as $event): ?>
                <?php partial('event-row', ['event' => $event]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
