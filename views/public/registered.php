<?php
$start = utc_to_local($event['starts_at'], $event['timezone']);
$end = utc_to_local($event['ends_at'], $event['timezone']);
$status = \App\Enums\RegistrationStatus::from($registration['status']);
$confirmed = $status === \App\Enums\RegistrationStatus::Confirmed;
$waitlisted = $status === \App\Enums\RegistrationStatus::Waitlisted;
$cancelled = $status === \App\Enums\RegistrationStatus::Cancelled;
$eventCancelled = $event['status'] === 'cancelled';
?>
<div class="container-page max-w-3xl py-10 sm:py-14">
    <a href="/e/<?= e($event['slug']) ?>" class="btn-ghost btn-sm -ml-3">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
        Back to event
    </a>

    <div class="ticket mt-5 overflow-hidden">
        <div class="p-7 sm:p-9">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="eyebrow"><?= $cancelled ? 'Registration cancelled' : ($confirmed ? 'You are going' : 'You are on the waitlist') ?></p>
                    <h1 class="mt-2 text-3xl sm:text-4xl">
                        <?php if ($cancelled): ?>
                            Your spot has been released
                        <?php elseif ($confirmed): ?>
                            You're in
                        <?php else: ?>
                            You're on the list
                        <?php endif; ?>
                    </h1>
                </div>

                <div class="flex h-12 w-12 items-center justify-center rounded-2xl <?= $cancelled ? 'bg-cream-200 text-ink-500' : ($confirmed ? 'bg-moss-100 text-moss-700' : 'bg-gold-100 text-gold-700') ?>">
                    <?php if ($cancelled): ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    <?php elseif ($confirmed): ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <?php else: ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <?php endif; ?>
                </div>
            </div>

            <p class="mt-4 max-w-lg text-sm leading-relaxed text-ink-500">
                <?php if ($cancelled): ?>
                    <?php if ($eventCancelled): ?>
                        This event was cancelled by the organizer, so there is nothing left to attend.
                    <?php else: ?>
                        You can register again at any time, seat availability permitting.
                    <?php endif; ?>
                <?php elseif ($confirmed): ?>
                    Your seat is confirmed. Your ticket reference is below — keep this page bookmarked or save the calendar invite.
                <?php else: ?>
                    The event is full right now. You're <?= (int) ($position ?? 1) ?><?= $position === 1 ? 'st' : (($position ?? 1) === 2 ? 'nd' : (($position ?? 1) === 3 ? 'rd' : 'th')) ?> in line. If someone cancels, we'll email you straight away.
                <?php endif; ?>
            </p>

            <div class="ticket-divider my-7"></div>

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-[10px] font-semibold tracking-wider text-ink-400 uppercase">Ticket reference</p>
                    <p class="mt-1 font-mono text-lg tracking-[0.18em]"><?= e($registration['reference']) ?></p>
                </div>
                <span class="<?= e($status->badgeClass()) ?>"><?= e($cancelled && $eventCancelled ? 'Event cancelled' : $status->label()) ?></span>
            </div>

            <div class="mt-7 grid gap-4 rounded-2xl bg-cream-50 p-5 sm:grid-cols-2">
                <div>
                    <p class="text-[10px] font-semibold tracking-wider text-ink-400 uppercase">Event</p>
                    <p class="mt-1 text-sm font-semibold"><?= e($event['title']) ?></p>
                    <p class="mt-0.5 text-xs text-ink-500"><?= e($event['venue_name'] ?: 'Venue to be announced') ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold tracking-wider text-ink-400 uppercase">When</p>
                    <p class="mt-1 text-sm font-semibold"><?= e($start?->format('D, j M Y') ?? '') ?></p>
                    <p class="mt-0.5 text-xs text-ink-500">
                        <?= e($start?->format('g:i A') ?? '') ?><?= $end ? ' – '.e($end->format('g:i A')) : '' ?>
                        · <?= e($event['timezone']) ?>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold tracking-wider text-ink-400 uppercase">Registered as</p>
                    <p class="mt-1 text-sm font-semibold"><?= e($registration['name']) ?></p>
                    <p class="mt-0.5 text-xs text-ink-500"><?= e($registration['email']) ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold tracking-wider text-ink-400 uppercase">Booked</p>
                    <p class="mt-1 text-sm font-semibold"><?= e(fmt_datetime($registration['created_at'], (string) $event['timezone'], 'j M Y · g:i A')) ?></p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-cream-200 bg-cream-50/70 px-7 py-5 sm:px-9">
            <a href="/e/<?= e($event['slug']) ?>/calendar.ics" class="btn-outline btn-sm">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                Add to calendar
            </a>

            <?php if ($cancelled && ! $eventCancelled): ?>
                <a href="/e/<?= e($event['slug']) ?>" class="btn-primary btn-sm">Register again</a>
            <?php elseif (! $cancelled): ?>
                <form method="post" action="/e/<?= e($event['slug']) ?>/registered/<?= e($registration['reference']) ?>/cancel" data-confirm="Cancel your registration?<?= $waitlisted ? '' : ' Your seat will be offered to the next person on the waitlist.' ?>">
                    <?= csrf_field() ?>
                    <button class="btn-ghost btn-sm text-red-600 hover:bg-red-50">Cancel registration</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-ink-400">
        Keep this link to check your status or cancel: <span class="text-ink-500"><?= e(url('/e/'.$event['slug'].'/registered/'.$registration['reference'])) ?></span>
    </p>
</div>
