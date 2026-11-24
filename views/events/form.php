<?php
/** @var array|null $event */
$editing = $event !== null;
$action = $editing ? '/events/'.(int) $event['id'] : '/events';
$value = fn (string $key, mixed $fallback = '') => e(old($key, $event[$key] ?? $fallback));
$localStart = $editing ? utc_to_local($event['starts_at'], $event['timezone'])?->format('Y-m-d\TH:i') : '';
$localEnd = $editing && $event['ends_at'] ? utc_to_local($event['ends_at'], $event['timezone'])?->format('Y-m-d\TH:i') : '';
$selectedTz = old('timezone', $editing ? $event['timezone'] : $defaultTimezone);
?>

<div class="container-page py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow"><?= $editing ? 'Edit event' : 'New event' ?></p>
            <h1 class="mt-2 text-3xl sm:text-4xl"><?= $editing ? 'Fine-tune the details' : 'Put an event together' ?></h1>
            <p class="mt-2 max-w-xl text-sm text-ink-500">Save as a draft as many times as you like. Nothing is public until you publish.</p>
        </div>
        <a href="<?= $editing ? '/events/'.(int) $event['id'] : '/dashboard' ?>" class="btn-ghost btn-sm">Cancel</a>
    </div>

    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="mt-8 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <?= csrf_field() ?>
        <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>

        <div class="space-y-6">
            <section class="card-pad">
                <h2 class="text-lg">The basics</h2>
                <p class="mt-1 text-sm text-ink-500">What is happening, and why should people come?</p>

                <div class="mt-5 space-y-5">
                    <div>
                        <label class="label" for="title">Event name</label>
                        <input id="title" name="title" class="input <?= error_for('title') ? 'input-error' : '' ?>" value="<?= $value('title') ?>" required maxlength="160" placeholder="e.g. Friday Night Jazz at the Garden">
                        <?php if ($msg = error_for('title')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label class="label" for="summary">Short summary <span class="font-normal text-ink-400">(optional)</span></label>
                        <input id="summary" name="summary" class="input <?= error_for('summary') ? 'input-error' : '' ?>" value="<?= $value('summary') ?>" maxlength="255" placeholder="One line that shows on the event card">
                        <?php if ($msg = error_for('summary')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label class="label" for="description">Description <span class="font-normal text-ink-400">(optional)</span></label>
                        <textarea id="description" name="description" rows="7" class="input <?= error_for('description') ? 'input-error' : '' ?>" placeholder="Line-up, what to bring, parking, anything your guests should know."><?= $value('description') ?></textarea>
                        <?php if ($msg = error_for('description')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="card-pad">
                <h2 class="text-lg">When and where</h2>
                <p class="mt-1 text-sm text-ink-500">Times are entered and shown in the event's own timezone.</p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="timezone">Timezone</label>
                        <input id="timezone" name="timezone" list="timezone-list" class="input <?= error_for('timezone') ? 'input-error' : '' ?>" value="<?= e($selectedTz) ?>" required placeholder="Africa/Accra" autocomplete="off">
                        <datalist id="timezone-list">
                            <?php foreach ($timezones as $zone): ?>
                                <option value="<?= e($zone) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <?php if ($msg = error_for('timezone')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label class="label" for="starts_at">Starts</label>
                        <input id="starts_at" type="datetime-local" name="starts_at" class="input <?= error_for('starts_at') ? 'input-error' : '' ?>" value="<?= e(old('starts_at', $localStart)) ?>" required>
                        <?php if ($msg = error_for('starts_at')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label class="label" for="ends_at">Ends <span class="font-normal text-ink-400">(optional)</span></label>
                        <input id="ends_at" type="datetime-local" name="ends_at" class="input <?= error_for('ends_at') ? 'input-error' : '' ?>" value="<?= e(old('ends_at', $localEnd)) ?>">
                        <?php if ($msg = error_for('ends_at')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label class="label" for="venue_name">Venue name</label>
                        <input id="venue_name" name="venue_name" class="input" value="<?= $value('venue_name') ?>" maxlength="160" placeholder="e.g. The Garden Studio">
                    </div>

                    <div>
                        <label class="label" for="venue_address">Address</label>
                        <input id="venue_address" name="venue_address" class="input" value="<?= $value('venue_address') ?>" maxlength="255" placeholder="Street, city">
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card-pad">
                <h2 class="text-lg">Capacity</h2>
                <p class="mt-1 text-sm text-ink-500">Leave empty for unlimited RSVPs. When full, new guests join the waitlist automatically.</p>

                <div class="mt-5">
                    <label class="label" for="capacity">Seats</label>
                    <input id="capacity" type="number" min="1" name="capacity" class="input <?= error_for('capacity') ? 'input-error' : '' ?>" value="<?= $value('capacity') ?>" placeholder="Unlimited">
                    <?php if ($msg = error_for('capacity')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
                </div>
            </section>

            <section class="card-pad">
                <h2 class="text-lg">Cover image</h2>
                <p class="mt-1 text-sm text-ink-500">JPG, PNG or WebP up to 4MB. Resized to 1600px wide.</p>

                <?php if ($editing && ! empty($event['cover_image'])): ?>
                    <img src="<?= e(url($event['cover_image'])) ?>" alt="" class="mt-4 aspect-[16/9] w-full rounded-xl object-cover">
                <?php endif; ?>

                <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full cursor-pointer rounded-xl border border-dashed border-cream-300 bg-cream-50 px-4 py-3 text-sm text-ink-500 file:mr-3 file:rounded-full file:border-0 file:bg-ink-900 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-cream-50 hover:border-coral-300">
                <?php if ($msg = error_for('cover')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
            </section>

            <?php if ($editing): ?>
                <section class="card-pad">
                    <h2 class="text-lg">Public link</h2>
                    <p class="mt-1 text-sm text-ink-500">The URL stays the same even if you rename the event.</p>
                    <div class="mt-3 flex items-center gap-2 rounded-xl border border-cream-200 bg-cream-50 px-3 py-2">
                        <code class="min-w-0 flex-1 truncate text-xs text-ink-700"><?= e(url('/e/'.$event['slug'])) ?></code>
                        <button type="button" class="btn-ghost btn-sm shrink-0" data-copy="<?= e(url('/e/'.$event['slug'])) ?>">
                            <span data-copy-label>Copy</span>
                        </button>
                    </div>
                </section>
            <?php endif; ?>

            <div class="flex flex-col gap-2 rounded-2xl border border-cream-200 bg-cream-100/60 p-4">
                <button type="submit" class="btn-primary w-full"><?= $editing ? 'Save changes' : 'Save draft' ?></button>
                <p class="text-center text-[11px] text-ink-400"><?= $editing ? 'Changes are live immediately if the event is published.' : 'You can publish from the next screen.' ?></p>
            </div>
        </div>
    </form>
</div>
