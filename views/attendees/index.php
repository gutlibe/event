<?php
$start = utc_to_local($event['starts_at'], $event['timezone']);
$tabs = [['', 'All'], ['confirmed', 'Confirmed'], ['waitlisted', 'Waitlist'], ['cancelled', 'Cancelled']];
?>
<div class="container-page py-10">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="/events/<?= (int) $event['id'] ?>" class="btn-ghost btn-sm -ml-3">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
            <?= e($event['title']) ?>
        </a>
        <div class="flex items-center gap-2">
            <a href="/events/<?= (int) $event['id'] ?>/attendees/export" class="btn-outline btn-sm">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"/></svg>
                Export CSV
            </a>
            <a href="/events/<?= (int) $event['id'] ?>/check-in" class="btn-primary btn-sm">Check-in screen</a>
        </div>
    </div>

    <div class="mt-4">
        <p class="eyebrow">Guest list</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">Who's coming</h1>
        <p class="mt-1 text-sm text-ink-500"><?= e($start?->format('D, j M Y · g:i A') ?? '') ?> · <?= e($event['venue_name'] ?: 'Venue to be announced') ?></p>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <?php
        $cards = [
            ['Confirmed', $counts['confirmed'], 'text-moss-700'],
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

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-1.5">
            <?php foreach ($tabs as [$key, $label]): ?>
                <?php
                $query = array_filter(['status' => $key, 'q' => $search], fn ($value) => $value !== '');
                ?>
                <a href="/events/<?= (int) $event['id'] ?>/attendees<?= $query ? '?'.http_build_query($query) : '' ?>" class="btn btn-sm <?= $status === $key ? 'bg-ink-900 text-cream-50' : 'border border-cream-200 bg-white/60 text-ink-700 hover:bg-white' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="get" action="/events/<?= (int) $event['id'] ?>/attendees" class="flex items-center gap-2">
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input name="q" value="<?= e($search) ?>" class="input py-1.5 text-sm" placeholder="Search name, email or reference" aria-label="Search attendees">
            <button class="btn-outline btn-sm">Search</button>
        </form>
    </div>

    <?php if ($registrations === []): ?>
        <div class="mt-6 rounded-2xl border border-dashed border-cream-300 bg-white/50 px-6 py-14 text-center">
            <h2 class="text-xl"><?= $search !== '' || $status !== '' ? 'No matching guests' : 'No registrations yet' ?></h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-ink-500">
                <?= $search !== '' || $status !== '' ? 'Try a different search or filter.' : 'Share the public event link and the list will fill up here.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-wrap mt-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Booked</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <?php $regStatus = \App\Enums\RegistrationStatus::from($registration['status']); ?>
                        <tr>
                            <td>
                                <p class="font-semibold"><?= e($registration['name']) ?></p>
                                <p class="text-xs text-ink-500"><?= e($registration['email']) ?><?= $registration['phone'] ? ' · '.e($registration['phone']) : '' ?></p>
                            </td>
                            <td><span class="font-mono text-xs tracking-wider text-ink-600"><?= e($registration['reference']) ?></span></td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="<?= e($regStatus->badgeClass()) ?>"><?= e($regStatus->label()) ?></span>
                                    <?php if (! empty($registration['checked_in_at'])): ?>
                                        <span class="badge-checked">Checked in</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-xs whitespace-nowrap text-ink-500"><?= e(fmt_datetime($registration['created_at'], (string) $event['timezone'], 'j M · g:i A')) ?></td>
                            <td class="text-right">
                                <?php if ($registration['status'] !== 'cancelled'): ?>
                                    <form method="post" action="/events/<?= (int) $event['id'] ?>/attendees/<?= (int) $registration['id'] ?>/cancel" data-confirm="Cancel <?= e($registration['name']) ?>'s registration?<?= $registration['status'] === 'confirmed' && $counts['waitlisted'] > 0 ? ' The next waitlisted guest will be promoted.' : '' ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button class="btn-ghost btn-sm text-red-600 hover:bg-red-50">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-ink-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
