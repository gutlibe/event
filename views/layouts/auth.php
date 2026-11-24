<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(env('APP_NAME', 'Gather')) ?></title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%231c1812'/%3E%3Ccircle cx='13' cy='16' r='5' fill='%23df5327'/%3E%3Ccircle cx='20' cy='16' r='5' fill='%23f7f0e4' fill-opacity='.9'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|fraunces:500,600,700" rel="stylesheet">
    <?= vite_tags() ?>
</head>
<body class="min-h-full">
<div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
    <aside class="relative hidden overflow-hidden bg-ink-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-coral-500/25 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-moss-600/20 blur-3xl"></div>

        <a href="/" class="relative flex items-center gap-2.5 text-cream-50">
            <?php partial('logo', ['size' => 32]); ?>
            <span class="text-xl font-semibold tracking-tight"><?= e(env('APP_NAME', 'Gather')) ?></span>
        </a>

        <div class="relative max-w-md">
            <p class="eyebrow text-coral-200">Free RSVP, done properly</p>
            <p class="display mt-4 text-4xl leading-[1.15] text-cream-50">
                “The guest list, the waitlist and the door — all under control.”
            </p>
            <div class="mt-8 flex items-center gap-3 text-sm text-cream-200/70">
                <div class="flex -space-x-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-ink-950 bg-coral-500 text-[11px] font-semibold text-white">AK</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-ink-950 bg-moss-600 text-[11px] font-semibold text-white">JM</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-ink-950 bg-cream-200 text-[11px] font-semibold text-ink-900">+</span>
                </div>
                <span>Built for organizers who would rather host than administer.</span>
            </div>
        </div>

        <p class="relative text-xs text-cream-200/40">&copy; <?= date('Y') ?> <?= e(env('APP_NAME', 'Gather')) ?></p>
    </aside>

    <main class="flex flex-col px-5 py-8 sm:px-10">
        <div class="flex items-center justify-between lg:hidden">
            <a href="/" class="flex items-center gap-2.5">
                <?php partial('logo', ['size' => 30]); ?>
                <span class="text-lg font-semibold tracking-tight"><?= e(env('APP_NAME', 'Gather')) ?></span>
            </a>
            <a href="/" class="text-sm text-ink-500 hover:text-ink-900">Back home</a>
        </div>

        <?php partial('flash'); ?>

        <div class="flex flex-1 items-center justify-center py-10">
            <div class="w-full max-w-md">
                <?= $content ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
