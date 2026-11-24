<?php
$start = utc_to_local($event['starts_at'], $event['timezone']);
$end = utc_to_local($event['ends_at'], $event['timezone']);
$cover = ! empty($event['cover_image']) ? url($event['cover_image']) : null;
$cancelled = $event['status'] === 'cancelled';
$full = $seatsLeft === 0;
$errors = errors();
?>
<div class="container-page py-8 sm:py-12">
    <?php if ($cancelled): ?>
        <div class="mb-8 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
            <svg class="mt-0.5 shrink-0 text-red-600" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
            <div>
                <p class="font-semibold text-red-800">This event has been cancelled</p>
                <p class="mt-0.5 text-sm text-red-700/80">The organizer called it off. Registration is closed.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid gap-8 lg:grid-cols-[1.45fr_1fr] lg:gap-10">
        <div>
            <div class="relative aspect-[16/9] w-full overflow-hidden rounded-3xl bg-ink-900">
                <?php if ($cover): ?>
                    <img src="<?= e($cover) ?>" alt="" class="h-full w-full object-cover">
                <?php else: ?>
                    <div class="absolute inset-0 bg-gradient-to-br from-moss-900 via-ink-900 to-coral-700"></div>
                    <div class="absolute inset-0 opacity-30 paper-grain"></div>
                    <div class="absolute right-6 bottom-4 text-[90px] leading-none font-semibold text-cream-50/10"><?= e(initials((string) $event['title'])) ?></div>
                <?php endif; ?>
                <span class="absolute top-4 left-4 rounded-full bg-cream-50/95 px-3 py-1 text-[10px] font-semibold tracking-wider uppercase backdrop-blur">
                    <?= e(seats_label($seatsLeft, $event['capacity'])) ?>
                </span>
            </div>

            <div class="mt-7">
                <p class="eyebrow"><?= $cancelled ? 'Cancelled event' : ($ended ? 'This event has ended' : 'Upcoming event') ?></p>
                <h1 class="mt-2 text-4xl leading-[1.05] sm:text-5xl"><?= e($event['title']) ?></h1>

                <?php if (! empty($event['summary'])): ?>
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink-500"><?= e($event['summary']) ?></p>
                <?php endif; ?>

                <div class="mt-7 grid gap-3 sm:grid-cols-2">
                    <div class="flex items-start gap-3 rounded-2xl border border-cream-200 bg-white/70 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-coral-100 text-coral-700">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold"><?= e($start?->format('D, j M Y') ?? '') ?></p>
                            <p class="mt-0.5 text-xs text-ink-500">
                                <?= e($start?->format('g:i A') ?? '') ?>
                                <?php if ($end): ?> – <?= e($end->format($end->format('Y-m-d') === $start?->format('Y-m-d') ? 'g:i A' : 'D, j M · g:i A')) ?><?php endif; ?>
                                · <?= e($event['timezone']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl border border-cream-200 bg-white/70 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-moss-100 text-moss-700">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold"><?= e($event['venue_name'] ?: 'Venue to be announced') ?></p>
                            <?php if (! empty($event['venue_address'])): ?>
                                <p class="mt-0.5 text-xs text-ink-500"><?= e($event['venue_address']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="lg:row-span-2">
            <div class="ticket p-6 sm:p-7 lg:sticky lg:top-24">
                <div class="flex items-center justify-between">
                    <span class="badge bg-moss-100 text-moss-700">Free RSVP</span>
                    <span class="text-xs font-medium <?= $full ? 'text-coral-600' : 'text-ink-500' ?>"><?= e(seats_label($seatsLeft, $event['capacity'])) ?></span>
                </div>

                <div class="ticket-divider my-5"></div>

                <div class="flex items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-ink-900 text-cream-50">
                        <span class="text-[9px] tracking-widest uppercase opacity-60"><?= e($start?->format('M') ?? '') ?></span>
                        <span class="text-xl leading-none font-semibold"><?= e($start?->format('j') ?? '') ?></span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold"><?= e($start?->format('l, j F') ?? '') ?></p>
                        <p class="text-xs text-ink-500"><?= e($start?->format('g:i A') ?? '') ?><?= $end ? ' – '.e($end->format('g:i A')) : '' ?></p>
                        <p class="mt-0.5 truncate text-xs text-ink-500"><?= e($event['venue_name'] ?: 'Venue to be announced') ?></p>
                    </div>
                </div>

                <div class="ticket-divider my-5"></div>

                <?php if ($cancelled): ?>
                    <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">
                        Registration is closed because this event was cancelled.
                    </div>
                <?php elseif ($ended): ?>
                    <div class="rounded-xl bg-cream-100 px-4 py-3 text-sm text-ink-600">
                        This event has already taken place. Thanks for stopping by.
                    </div>
                <?php else: ?>
                    <?php if ($full): ?>
                        <div class="mb-4 rounded-xl border border-gold-100 bg-gold-100/50 px-4 py-3 text-sm text-gold-700">
                            Every seat is taken. Join the waitlist and we'll email you if someone cancels.
                        </div>
                    <?php endif; ?>

                    <h2 class="text-xl"><?= $full ? 'Join the waitlist' : 'Reserve your spot' ?></h2>
                    <p class="mt-1 text-xs text-ink-500">Free to attend. We'll email your ticket reference.</p>

                    <form method="post" action="/e/<?= e($event['slug']) ?>/register" class="mt-5 space-y-4">
                        <?= csrf_field() ?>

                        <div>
                            <label class="label" for="name">Full name</label>
                            <input id="name" name="name" value="<?= e(old('name')) ?>" class="input <?= isset($errors['name']) ? 'input-error' : '' ?>" required autocomplete="name" placeholder="Your name">
                            <?php if (isset($errors['name'])): ?><p class="error"><?= e($errors['name']) ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="label" for="email">Email</label>
                            <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" class="input <?= isset($errors['email']) ? 'input-error' : '' ?>" required autocomplete="email" placeholder="you@example.com">
                            <?php if (isset($errors['email'])): ?><p class="error"><?= e($errors['email']) ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="label" for="phone">Phone <span class="font-normal text-ink-400">(optional)</span></label>
                            <input id="phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" class="input <?= isset($errors['phone']) ? 'input-error' : '' ?>" autocomplete="tel" placeholder="+233 ...">
                            <?php if (isset($errors['phone'])): ?><p class="error"><?= e($errors['phone']) ?></p><?php endif; ?>
                        </div>

                        <button type="submit" class="btn-coral w-full py-3"><?= $full ? 'Join waitlist' : 'Confirm RSVP' ?></button>
                        <p class="text-center text-[11px] leading-relaxed text-ink-400">One registration per email. You can cancel any time from your ticket.</p>
                    </form>
                <?php endif; ?>

                <div class="ticket-divider my-5"></div>

                <a href="/e/<?= e($event['slug']) ?>/calendar.ics" class="btn-ghost btn-sm w-full justify-center">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18M12 14v3M10.5 15.5h3"/></svg>
                    Add to calendar
                </a>
            </div>
        </aside>

        <?php if (! empty($event['description'])): ?>
            <div class="lg:col-start-1">
                <div class="border-t border-cream-200 pt-7">
                    <h2 class="text-2xl">About this event</h2>
                    <div class="mt-4 text-[15px] leading-relaxed whitespace-pre-line text-ink-700"><?= e($event['description']) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
