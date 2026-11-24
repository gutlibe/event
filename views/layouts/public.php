<?php $authUser = \App\Auth::user(); ?>
<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(env('APP_NAME', 'Gather')) ?></title>
    <meta name="description" content="Gather — publish an event, take free RSVPs, manage the waitlist and check guests in.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%231c1812'/%3E%3Ccircle cx='13' cy='16' r='5' fill='%23df5327'/%3E%3Ccircle cx='20' cy='16' r='5' fill='%23f7f0e4' fill-opacity='.9'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|fraunces:500,600,700" rel="stylesheet">
    <?= vite_tags() ?>
</head>
<body class="flex min-h-full flex-col">
<header class="sticky top-0 z-30 border-b border-cream-200/70 bg-cream-50/85 backdrop-blur">
    <div class="container-page flex h-16 items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-2.5" aria-label="<?= e(env('APP_NAME', 'Gather')) ?> home">
            <?php partial('logo', ['size' => 30]); ?>
            <span class="text-[19px] font-semibold tracking-tight"><?= e(env('APP_NAME', 'Gather')) ?></span>
        </a>

        <nav class="hidden items-center gap-1 sm:flex">
            <a href="/" class="btn-ghost btn-sm">Browse events</a>
            <?php if ($authUser): ?>
                <a href="/dashboard" class="btn-ghost btn-sm">Dashboard</a>
                <a href="/events/create" class="btn-primary btn-sm ml-1">New event</a>
            <?php else: ?>
                <a href="/login" class="btn-ghost btn-sm">Sign in</a>
                <a href="/register" class="btn-primary btn-sm ml-1">Create an event</a>
            <?php endif; ?>
        </nav>

        <button type="button" class="btn-ghost btn-sm sm:hidden" data-nav-toggle="site-nav" aria-expanded="false" aria-label="Menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>

    <div id="site-nav" class="hidden border-t border-cream-200 bg-cream-50 sm:hidden">
        <div class="container-page flex flex-col gap-1 py-3">
            <a href="/" class="btn-ghost justify-start">Browse events</a>
            <?php if ($authUser): ?>
                <a href="/dashboard" class="btn-ghost justify-start">Dashboard</a>
                <a href="/events/create" class="btn-ghost justify-start">New event</a>
            <?php else: ?>
                <a href="/login" class="btn-ghost justify-start">Sign in</a>
                <a href="/register" class="btn-ghost justify-start">Create an event</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php partial('flash'); ?>

<main class="flex-1">
    <?= $content ?>
</main>

<footer class="mt-16 border-t border-cream-200/80">
    <div class="container-page flex flex-col gap-3 py-8 text-sm text-ink-500 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2.5">
            <?php partial('logo', ['size' => 22]); ?>
            <span class="text-ink-700"><?= e(env('APP_NAME', 'Gather')) ?></span>
            <span aria-hidden="true">·</span>
            <span>Free RSVP for good events.</span>
        </div>
        <div class="flex items-center gap-5">
            <a href="/" class="hover:text-ink-900">Browse</a>
            <a href="/login" class="hover:text-ink-900">Organizer sign in</a>
            <span>&copy; <?= date('Y') ?></span>
        </div>
    </div>
</footer>
</body>
</html>
