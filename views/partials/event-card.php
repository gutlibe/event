<?php
$cover = ! empty($event['cover_image']) ? url($event['cover_image']) : null;
$left = $seatsLeft ?? null;
$startLocal = utc_to_local($event['starts_at'], $event['timezone']);
?>
<a href="/e/<?= e($event['slug']) ?>" class="card group block overflow-hidden transition hover:-translate-y-0.5 hover:shadow-xl">
    <div class="relative aspect-[16/9] overflow-hidden bg-ink-900">
        <?php if ($cover): ?>
            <img src="<?= e($cover) ?>" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
        <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-moss-900 via-ink-900 to-coral-700"></div>
            <div class="absolute inset-0 opacity-30 paper-grain"></div>
            <div class="absolute right-6 bottom-5 text-[72px] leading-none font-semibold text-cream-50/10"><?= e(initials((string) $event['title'])) ?></div>
        <?php endif; ?>

        <div class="absolute top-4 left-4 flex flex-col items-center rounded-xl bg-cream-50/95 px-3 py-2 text-center shadow-sm backdrop-blur">
            <span class="text-[10px] font-semibold tracking-widest text-coral-600 uppercase"><?= e($startLocal?->format('M') ?? '') ?></span>
            <span class="text-xl leading-none font-semibold"><?= e($startLocal?->format('j') ?? '') ?></span>
        </div>
    </div>

    <div class="p-5">
        <p class="text-xs font-medium text-ink-500"><?= e($startLocal?->format('D, j M Y · g:i A') ?? '') ?></p>
        <h3 class="mt-1.5 text-xl leading-snug"><?= e($event['title']) ?></h3>

        <?php if (! empty($event['summary'])): ?>
            <p class="mt-2 line-clamp-2 text-sm text-ink-500"><?= e($event['summary']) ?></p>
        <?php endif; ?>

        <div class="mt-4 flex items-center justify-between gap-3 border-t border-cream-100 pt-3 text-xs">
            <span class="flex min-w-0 items-center gap-1.5 text-ink-500">
                <svg class="shrink-0" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <span class="truncate"><?= e($event['venue_name'] ?: 'Venue to be announced') ?></span>
            </span>
            <span class="shrink-0 font-semibold <?= $left === 0 ? 'text-coral-600' : 'text-ink-700' ?>">
                <?= e(seats_label($left, $event['capacity'] ?? null)) ?>
            </span>
        </div>
    </div>
</a>
