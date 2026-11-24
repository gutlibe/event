<?php $start = utc_to_local($event['starts_at'], $event['timezone']); ?>
<div class="container-page max-w-5xl py-10">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="/events/<?= (int) $event['id'] ?>" class="btn-ghost btn-sm -ml-3">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
            <?= e($event['title']) ?>
        </a>
        <div class="flex items-center gap-2">
            <a href="/events/<?= (int) $event['id'] ?>/attendees" class="btn-ghost btn-sm">Guest list</a>
            <span class="badge bg-cream-200 text-ink-700"><?= e($start?->format('j M · g:i A') ?? '') ?></span>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_280px]">
        <div>
            <p class="eyebrow">Door check-in</p>
            <h1 class="mt-2 text-3xl sm:text-4xl">Find a guest</h1>
            <p class="mt-1 text-sm text-ink-500">Search by name, email, phone or ticket reference. Check-ins save instantly.</p>

            <form method="get" action="/events/<?= (int) $event['id'] ?>/check-in" class="mt-5 flex gap-2">
                <div class="relative flex-1">
                    <svg class="absolute top-1/2 left-4 -translate-y-1/2 text-ink-400" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input name="q" value="<?= e($search) ?>" class="input py-3.5 pl-11 text-base" placeholder="Start typing a name…" autofocus autocomplete="off" data-autofocus aria-label="Search guests">
                </div>
                <button class="btn-primary px-6">Search</button>
            </form>

            <?php if ($search === ''): ?>
                <div class="mt-8 rounded-2xl border border-dashed border-cream-300 bg-white/50 px-6 py-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-cream-100 text-ink-500">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </div>
                    <p class="mt-4 text-sm font-medium">Ready for the door</p>
                    <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed text-ink-500">Type any part of a guest's details above. Partial names work — “ama” finds Ama Mensah.</p>
                </div>
            <?php elseif ($matches === []): ?>
                <div class="mt-8 rounded-2xl border border-dashed border-cream-300 bg-white/50 px-6 py-12 text-center">
                    <p class="text-sm font-medium">No one matches “<?= e($search) ?>”</p>
                    <p class="mt-1 text-xs text-ink-500">Check the spelling, or search by ticket reference instead.</p>
                </div>
            <?php else: ?>
                <div class="mt-6 space-y-3">
                    <?php foreach ($matches as $registration): ?>
                        <?php
                        $regStatus = \App\Enums\RegistrationStatus::from($registration['status']);
                        $checked = ! empty($registration['checked_in_at']);
                        ?>
                        <div class="card flex flex-wrap items-center gap-4 p-4 sm:flex-nowrap">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full <?= $checked ? 'bg-moss-100 text-moss-700' : 'bg-cream-100 text-ink-600' ?> text-sm font-semibold">
                                <?php if ($checked): ?>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <?php else: ?>
                                    <?= e(initials((string) $registration['name'])) ?>
                                <?php endif; ?>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold"><?= e($registration['name']) ?></p>
                                    <span class="<?= e($regStatus->badgeClass()) ?>"><?= e($regStatus->label()) ?></span>
                                </div>
                                <p class="mt-0.5 text-xs text-ink-500">
                                    <?= e($registration['email']) ?>
                                    <span aria-hidden="true">·</span>
                                    <span class="font-mono tracking-wider"><?= e($registration['reference']) ?></span>
                                </p>
                                <?php if ($checked): ?>
                                    <p class="mt-1 text-xs font-medium text-moss-700">Checked in <?= e(fmt_datetime($registration['checked_in_at'], (string) $event['timezone'], 'g:i A')) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <?php if ($checked): ?>
                                    <form method="post" action="/events/<?= (int) $event['id'] ?>/check-in/<?= (int) $registration['id'] ?>/undo">
                                        <?= csrf_field() ?>
                                        <button class="btn-ghost btn-sm">Undo</button>
                                    </form>
                                <?php elseif ($registration['status'] === 'confirmed'): ?>
                                    <form method="post" action="/events/<?= (int) $event['id'] ?>/check-in">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="registration_id" value="<?= (int) $registration['id'] ?>">
                                        <input type="hidden" name="q" value="<?= e($search) ?>">
                                        <button class="btn-primary btn-sm px-5">Check in</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-ink-400">Not on the confirmed list</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside>
            <div class="card-pad lg:sticky lg:top-24">
                <p class="text-[10px] font-semibold tracking-wider text-ink-400 uppercase">At the door</p>
                <p class="mt-2 text-4xl font-semibold"><?= (int) $counts['checked_in'] ?><span class="text-lg text-ink-400"> / <?= (int) $counts['confirmed'] ?></span></p>
                <p class="mt-1 text-xs text-ink-500">checked in</p>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-cream-200">
                    <?php $pct = $counts['confirmed'] > 0 ? min(100, round($counts['checked_in'] / $counts['confirmed'] * 100)) : 0; ?>
                    <div class="h-full rounded-full bg-moss-600 transition-all" style="width: <?= (int) $pct ?>%"></div>
                </div>

                <div class="mt-5 space-y-2 border-t border-cream-100 pt-4 text-xs">
                    <div class="flex items-center justify-between"><span class="text-ink-500">Confirmed</span><span class="font-semibold"><?= (int) $counts['confirmed'] ?></span></div>
                    <div class="flex items-center justify-between"><span class="text-ink-500">Waitlist</span><span class="font-semibold"><?= (int) $counts['waitlisted'] ?></span></div>
                    <div class="flex items-center justify-between"><span class="text-ink-500">Remaining</span><span class="font-semibold"><?= max(0, (int) $counts['confirmed'] - (int) $counts['checked_in']) ?></span></div>
                </div>
            </div>
        </aside>
    </div>
</div>
