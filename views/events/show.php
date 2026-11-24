<?php
$status = \App\Enums\EventStatus::from($event['status']);
$startLocal = utc_to_local($event['starts_at'], $event['timezone']);
$endLocal = utc_to_local($event['ends_at'], $event['timezone']);
$cover = ! empty($event['cover_image']) ? url($event['cover_image']) : null;
$isPast = strtotime($event['starts_at'].' UTC') <= time();
?>
<div class="container-page py-10">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="/dashboard" class="btn-ghost btn-sm -ml-3">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
            Dashboard
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <?php if ($event['status'] === 'draft'): ?>
                <a href="/events/<?= (int) $event['id'] ?>/edit" class="btn-outline btn-sm">Edit</a>
                <form method="post" action="/events/<?= (int) $event['id'] ?>/publish">
                    <?= csrf_field() ?>
                    <button class="btn-primary btn-sm">Publish event</button>
                </form>
            <?php elseif ($event['status'] === 'published'): ?>
                <a href="/e/<?= e($event['slug']) ?>" target="_blank" rel="noopener" class="btn-outline btn-sm">
                    View public page
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 17 17 7M8 7h9v9"/></svg>
                </a>
                <a href="/events/<?= (int) $event['id'] ?>/edit" class="btn-primary btn-sm">Edit event</a>
            <?php else: ?>
                <a href="/e/<?= e($event['slug']) ?>" target="_blank" rel="noopener" class="btn-outline btn-sm">View public page</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-5 overflow-hidden rounded-3xl border border-cream-200 bg-white/70">
        <div class="relative aspect-[21/9] max-h-[320px] w-full overflow-hidden bg-ink-900">
            <?php if ($cover): ?>
                <img src="<?= e($cover) ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="absolute inset-0 bg-gradient-to-br from-moss-900 via-ink-900 to-coral-700"></div>
                <div class="absolute inset-0 opacity-30 paper-grain"></div>
                <div class="absolute right-8 bottom-4 text-[100px] leading-none font-semibold text-cream-50/10"><?= e(initials((string) $event['title'])) ?></div>
            <?php endif; ?>
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4 p-6 sm:p-8">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="<?= e($status->badgeClass()) ?>"><?= e($status->label()) ?></span>
                    <?php if ($isPast): ?><span class="badge bg-cream-200 text-ink-500">Past</span><?php endif; ?>
                </div>
                <h1 class="mt-3 text-3xl leading-tight sm:text-4xl"><?= e($event['title']) ?></h1>
                <?php if (! empty($event['summary'])): ?>
                    <p class="mt-2 max-w-2xl text-[15px] text-ink-500"><?= e($event['summary']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <?php
                $cards = [
                    ['Going', $counts['confirmed'], 'text-moss-700'],
                    ['Waitlist', $counts['waitlisted'], 'text-gold-700'],
                    ['Checked in', $counts['checked_in'], 'text-coral-600'],
                    ['Seats left', $seatsLeft === null ? '∞' : $seatsLeft, 'text-ink-900'],
                ];
                ?>
                <?php foreach ($cards as [$label, $value, $color]): ?>
                    <div class="card p-4">
                        <p class="text-[10px] font-semibold tracking-wider text-ink-400 uppercase"><?= e($label) ?></p>
                        <p class="mt-1.5 text-2xl font-semibold <?= e($color) ?>"><?= e((string) $value) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <section class="card-pad">
                <h2 class="text-lg">Details</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold tracking-wider text-ink-400 uppercase">Starts</dt>
                        <dd class="mt-1 font-medium"><?= e($startLocal?->format('D, j M Y · g:i A') ?? '') ?></dd>
                        <dd class="text-xs text-ink-500"><?= e($event['timezone']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wider text-ink-400 uppercase">Ends</dt>
                        <dd class="mt-1 font-medium"><?= $endLocal ? e($endLocal->format('D, j M Y · g:i A')) : '<span class="text-ink-400">Not set</span>' ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wider text-ink-400 uppercase">Venue</dt>
                        <dd class="mt-1 font-medium"><?= e($event['venue_name'] ?: 'To be announced') ?></dd>
                        <?php if (! empty($event['venue_address'])): ?><dd class="text-xs text-ink-500"><?= e($event['venue_address']) ?></dd><?php endif; ?>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wider text-ink-400 uppercase">Capacity</dt>
                        <dd class="mt-1 font-medium"><?= $event['capacity'] === null ? 'Unlimited' : (int) $event['capacity'].' seats' ?></dd>
                    </div>
                </dl>
            </section>

            <?php if (! empty($event['description'])): ?>
                <section class="card-pad">
                    <h2 class="text-lg">About this event</h2>
                    <div class="mt-3 text-[15px] leading-relaxed whitespace-pre-line text-ink-700"><?= e($event['description']) ?></div>
                </section>
            <?php endif; ?>

            <?php if ($event['status'] !== 'cancelled'): ?>
                <section class="rounded-2xl border border-red-200 bg-red-50/60 p-5">
                    <h2 class="text-base font-semibold text-red-800">Cancel this event</h2>
                    <p class="mt-1 text-sm text-red-700/80">The public page keeps the details but closes registration and shows a cancellation notice. This cannot be undone.</p>
                    <form method="post" action="/events/<?= (int) $event['id'] ?>/cancel" data-confirm="Cancel this event? Guests will see a cancellation notice on the public page." class="mt-4">
                        <?= csrf_field() ?>
                        <button class="btn-danger btn-sm">Cancel event</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <aside class="space-y-4">
            <section class="card-pad">
                <h2 class="text-base font-semibold">Manage</h2>
                <div class="mt-4 space-y-2">
                    <a href="/events/<?= (int) $event['id'] ?>/attendees" class="btn-outline w-full justify-between">
                        Attendee list
                        <span class="badge bg-cream-100 text-ink-700"><?= (int) $counts['confirmed'] + (int) $counts['waitlisted'] ?></span>
                    </a>
                    <a href="/events/<?= (int) $event['id'] ?>/check-in" class="btn-primary w-full justify-between">
                        Check-in screen
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg>
                    </a>
                    <a href="/events/<?= (int) $event['id'] ?>/attendees/export" class="btn-ghost btn-sm w-full justify-start">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"/></svg>
                        Export CSV
                    </a>
                </div>
            </section>

            <section class="card-pad">
                <h2 class="text-base font-semibold">Share</h2>
                <p class="mt-1 text-xs text-ink-500">Anyone with this link can RSVP.</p>
                <div class="mt-3 flex items-center gap-2 rounded-xl border border-cream-200 bg-cream-50 px-3 py-2">
                    <code class="min-w-0 flex-1 truncate text-xs text-ink-700"><?= e($shareUrl) ?></code>
                    <button type="button" class="btn-ghost btn-sm shrink-0" data-copy="<?= e($shareUrl) ?>"><span data-copy-label>Copy</span></button>
                </div>
                <?php if ($event['status'] === 'draft'): ?>
                    <p class="mt-3 text-xs text-ink-400">Not public yet — publish the event first.</p>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</div>
