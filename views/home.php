<?php $authUser = \App\Auth::user(); ?>
<section class="container-page pt-12 pb-12 sm:pt-20">
    <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
            <p class="eyebrow">Free RSVP · no spreadsheets</p>
            <h1 class="display mt-4 text-[3.4rem] leading-[0.98] sm:text-7xl">
                Good events.<br>
                <span class="text-coral-500">Less admin.</span>
            </h1>
            <p class="mt-6 max-w-lg text-[17px] leading-relaxed text-ink-500">
                Publish a page, take RSVPs, manage the waitlist and check people in at the door — without the group chat archaeology.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <?php if ($authUser): ?>
                    <a href="/dashboard" class="btn-primary px-6 py-3">Go to dashboard</a>
                <?php else: ?>
                    <a href="/register" class="btn-primary px-6 py-3">Create an event</a>
                <?php endif; ?>
                <a href="#events" class="btn-outline px-6 py-3">Browse events</a>
            </div>

            <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-ink-500">
                <span class="flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                    No card required
                </span>
                <span class="flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Waitlist handled for you
                </span>
                <span class="flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Door check-in in seconds
                </span>
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-sm lg:max-w-none">
            <div class="absolute -inset-6 rounded-[2.5rem] bg-gradient-to-br from-coral-100/80 via-cream-100 to-moss-100/70 blur-2xl"></div>

            <div class="ticket relative -rotate-1 p-6 paper-grain">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-semibold tracking-[0.2em] text-coral-600 uppercase">Admit one</p>
                        <p class="mt-1 text-[11px] text-ink-400">Friday, 21 June · 7:00 PM</p>
                    </div>
                    <div class="rounded-xl bg-ink-900 px-3 py-2 text-center text-cream-50">
                        <p class="text-[9px] tracking-widest uppercase opacity-60">Jun</p>
                        <p class="text-xl leading-none font-semibold">21</p>
                    </div>
                </div>

                <div class="mt-8 aspect-[16/10] overflow-hidden rounded-xl bg-gradient-to-br from-moss-900 via-ink-900 to-coral-700">
                    <div class="flex h-full items-end justify-between p-4">
                        <span class="font-display text-2xl leading-tight text-cream-50">Friday Night<br>Jazz</span>
                        <span class="badge bg-cream-50/15 text-cream-50 backdrop-blur">32 going</span>
                    </div>
                </div>

                <div class="mt-6 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-ink-500">Venue</span>
                        <span class="font-medium">The Garden Studio</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-ink-500">Ticket</span>
                        <span class="font-medium text-moss-700">Free RSVP</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-ink-500">Seats left</span>
                        <span class="font-medium text-coral-600">8 of 40</span>
                    </div>
                </div>

                <div class="ticket-divider my-5"></div>

                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs tracking-[0.2em] text-ink-500">EVT-7K2QX9</span>
                    <span class="text-[10px] tracking-wider text-ink-400 uppercase">gather</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="events" class="container-page scroll-mt-20 border-t border-cream-200/70 py-14">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Happening soon</p>
            <h2 class="mt-2 text-3xl sm:text-4xl">Upcoming events</h2>
        </div>
        <p class="max-w-sm text-sm text-ink-500">Free to attend, easy to join. Pick one and put it in your calendar.</p>
    </div>

    <?php if ($events === []): ?>
        <div class="mt-8 rounded-3xl border border-dashed border-cream-300 bg-white/50 px-6 py-16 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-cream-100">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-ink-500"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
            </div>
            <h3 class="mt-4 text-xl">No public events yet</h3>
            <p class="mx-auto mt-2 max-w-sm text-sm text-ink-500">When an organizer publishes an event, it shows up right here.</p>
            <a href="/register" class="btn-outline btn-sm mt-5">Host the first one</a>
        </div>
    <?php else: ?>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($events as $event): ?>
                <?php partial('event-card', ['event' => $event, 'seatsLeft' => $event['capacity'] !== null ? max(0, (int) $event['capacity'] - (int) $event['confirmed_count']) : null]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="container-page py-14">
    <div class="rounded-3xl border border-cream-200 bg-white/60 p-6 sm:p-10">
        <p class="eyebrow">How it works</p>
        <h2 class="mt-2 max-w-xl text-3xl sm:text-4xl">From idea to front door in three steps.</h2>

        <div class="mt-9 grid gap-8 sm:grid-cols-3">
            <?php
            $steps = [
                ['01', 'Set it up', 'Name it, pick a time and venue, add a cover. Save it as a draft while you figure things out.'],
                ['02', 'Publish the link', 'One click makes a public page with the details and a simple RSVP form. Share the URL anywhere.'],
                ['03', 'Work the room', 'Watch the list fill, let the waitlist do its job, and check guests in from your phone at the door.'],
            ];
            ?>
            <?php foreach ($steps as [$number, $title, $copy]): ?>
                <div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-full border border-cream-300 bg-cream-50 font-display text-sm font-semibold text-coral-600"><?= e($number) ?></div>
                    <h3 class="mt-4 text-lg"><?= e($title) ?></h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500"><?= e($copy) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container-page pb-6">
    <div class="relative overflow-hidden rounded-3xl bg-ink-950 px-6 py-12 text-cream-50 sm:px-12 sm:py-14">
        <div class="absolute -top-24 -right-16 h-72 w-72 rounded-full bg-coral-500/25 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-moss-600/25 blur-3xl"></div>

        <div class="relative flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
            <div>
                <p class="text-[11px] font-semibold tracking-[0.2em] text-coral-200 uppercase">Your next event</p>
                <h2 class="mt-3 max-w-lg text-3xl leading-tight sm:text-4xl">Bring people together. We'll handle the list.</h2>
            </div>
            <a href="<?= $authUser ? '/events/create' : '/register' ?>" class="btn bg-cream-50 px-6 py-3 text-ink-900 hover:bg-coral-100">
                <?= $authUser ? 'Create an event' : 'Start free' ?>
            </a>
        </div>
    </div>
</section>
