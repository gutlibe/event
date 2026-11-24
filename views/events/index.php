<div class="container-page py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Your events</p>
            <h1 class="mt-2 text-3xl sm:text-4xl">Everything you're hosting</h1>
            <p class="mt-2 text-sm text-ink-500">Drafts, live events and cancellations in one list.</p>
        </div>
        <a href="/events/create" class="btn-primary btn-sm">New event</a>
    </div>

    <?php
    $tabs = [
        ['', 'All'],
        ['published', 'Live'],
        ['draft', 'Drafts'],
        ['cancelled', 'Cancelled'],
    ];
    ?>
    <div class="mt-7 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-1.5">
            <?php foreach ($tabs as [$key, $label]): ?>
                <a href="/events<?= $key !== '' ? '?status='.$key : '' ?>" class="btn btn-sm <?= $status === $key ? 'bg-ink-900 text-cream-50' : 'border border-cream-200 bg-white/60 text-ink-700 hover:bg-white' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="get" action="/events" class="flex items-center gap-2">
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input name="q" value="<?= e($search) ?>" class="input py-1.5 text-sm" placeholder="Search events" aria-label="Search events">
            <button class="btn-outline btn-sm">Search</button>
        </form>
    </div>

    <?php if ($events === []): ?>
        <div class="mt-6 rounded-3xl border border-dashed border-cream-300 bg-white/50 px-6 py-14 text-center">
            <h2 class="text-xl">Nothing here yet</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-ink-500">Try another filter, or create a new event.</p>
            <a href="/events/create" class="btn-primary btn-sm mt-5">Create an event</a>
        </div>
    <?php else: ?>
        <div class="mt-6 space-y-2.5">
            <?php foreach ($events as $event): ?>
                <?php partial('event-row', ['event' => $event]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
