<?php
$status = \App\Enums\EventStatus::from($event['status']);
$startLocal = utc_to_local($event['starts_at'], $event['timezone']);
$cover = ! empty($event['cover_image']) ? url($event['cover_image']) : null;
?>
<a href="/events/<?= (int) $event['id'] ?>" class="group flex items-center gap-3.5 rounded-2xl border border-cream-200 bg-white/80 p-3 transition hover:border-ink-900/20 hover:bg-white sm:gap-4 sm:p-4">
    <div class="relative h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-ink-900 sm:h-16 sm:w-16">
        <?php if ($cover): ?>
            <img src="<?= e($cover) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
        <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-moss-900 to-coral-700"></div>
            <span class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-cream-50/80"><?= e(initials((string) $event['title'])) ?></span>
        <?php endif; ?>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="truncate text-[15px] font-semibold"><?= e($event['title']) ?></h3>
            <span class="<?= e($status->badgeClass()) ?>"><?= e($status->label()) ?></span>
        </div>
        <p class="mt-1 truncate text-xs text-ink-500">
            <?= e($startLocal?->format('D, j M Y · g:i A') ?? '') ?>
            <?php if (! empty($event['venue_name'])): ?>
                <span aria-hidden="true">·</span> <?= e($event['venue_name']) ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="hidden shrink-0 items-center gap-6 pr-2 text-center md:flex">
        <div>
            <p class="text-lg leading-none font-semibold"><?= (int) $event['confirmed_count'] ?></p>
            <p class="mt-1 text-[10px] tracking-wider text-ink-400 uppercase">Going</p>
        </div>
        <div>
            <p class="text-lg leading-none font-semibold <?= (int) $event['waitlisted_count'] > 0 ? 'text-gold-700' : '' ?>"><?= (int) $event['waitlisted_count'] ?></p>
            <p class="mt-1 text-[10px] tracking-wider text-ink-400 uppercase">Waitlist</p>
        </div>
        <div>
            <p class="text-lg leading-none font-semibold"><?= (int) $event['checked_in_count'] ?></p>
            <p class="mt-1 text-[10px] tracking-wider text-ink-400 uppercase">Checked in</p>
        </div>
    </div>

    <svg class="shrink-0 text-ink-300 transition group-hover:translate-x-0.5 group-hover:text-ink-700" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg>
</a>
