<?php
$authUser = \App\Auth::user();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$isDashboard = $path === '/dashboard';
$isEvents = str_starts_with($path, '/events') && $path !== '/events/create';
$isCreate = $path === '/events/create';

$icons = [
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/>',
    'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.5 5.8 3.5 9S14.5 18.5 12 21c-2.5-2.5-3.5-5.8-3.5-9S9.5 5.5 12 3Z"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
];
?>
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
<aside id="admin-sidebar" class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-[276px] flex-col border-r border-cream-200 bg-[#fffdf9]/95 backdrop-blur" aria-label="Organizer menu">
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-cream-100 px-4">
        <a href="/dashboard" class="flex items-center gap-2.5">
            <?php partial('logo', ['size' => 30]); ?>
            <span class="text-[19px] font-semibold tracking-tight"><?= e(env('APP_NAME', 'Gather')) ?></span>
        </a>
        <button type="button" class="btn-ghost btn-sm -mr-1 lg:hidden" data-drawer-close aria-label="Close menu">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="flex flex-1 flex-col overflow-y-auto px-3 py-4">
        <a href="/events/create" class="btn-coral w-full <?= $isCreate ? 'ring-2 ring-coral-200 ring-offset-2 ring-offset-cream-50' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            New event
        </a>

        <p class="sidebar-section mt-7 mb-2">Workspace</p>
        <nav class="space-y-1">
            <a href="/dashboard" class="sidebar-link <?= $isDashboard ? 'is-active' : '' ?>" <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                <svg class="sidebar-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $icons['grid'] ?></svg>
                Dashboard
            </a>
            <a href="/events" class="sidebar-link <?= $isEvents ? 'is-active' : '' ?>" <?= $isEvents ? 'aria-current="page"' : '' ?>>
                <svg class="sidebar-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $icons['calendar'] ?></svg>
                Events
            </a>
        </nav>

        <p class="sidebar-section mt-7 mb-2">Public</p>
        <nav class="space-y-1">
            <a href="/" target="_blank" rel="noopener" class="sidebar-link">
                <svg class="sidebar-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $icons['globe'] ?></svg>
                Browse public site
                <svg class="ml-auto text-ink-400" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 17 17 7M8 7h9v9"/></svg>
            </a>
        </nav>

        <?php if ($authUser): ?>
            <div class="mt-auto pt-6">
                <div class="rounded-2xl border border-cream-200 bg-cream-50/80 p-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-ink-900 text-[11px] font-semibold text-cream-50"><?= e(initials((string) $authUser['name'])) ?></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold"><?= e($authUser['name']) ?></p>
                            <p class="truncate text-[11px] text-ink-500"><?= e($authUser['email']) ?></p>
                        </div>
                    </div>
                    <form method="post" action="/logout" class="mt-3 border-t border-cream-200 pt-3">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-ghost btn-sm w-full justify-start text-red-600 hover:bg-red-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-ink-950/40 backdrop-blur-[2px] lg:hidden"></div>

<div class="flex min-h-screen flex-col lg:pl-[276px]">
    <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-cream-200/70 bg-cream-50/90 px-4 backdrop-blur lg:hidden">
        <button type="button" class="btn-ghost btn-sm -ml-2" data-drawer-open aria-expanded="false" aria-controls="admin-sidebar" aria-label="Open menu">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a href="/dashboard" class="flex items-center gap-2.5">
            <?php partial('logo', ['size' => 26]); ?>
            <span class="text-[17px] font-semibold tracking-tight"><?= e(env('APP_NAME', 'Gather')) ?></span>
        </a>

        <a href="/events/create" class="btn-ghost btn-sm -mr-2" aria-label="New event">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        </a>
    </header>

    <?php partial('flash'); ?>

    <main class="flex-1">
        <?= $content ?>
    </main>

    <footer class="mt-16 border-t border-cream-200/80">
        <div class="container-page flex items-center justify-between py-6 text-xs text-ink-500">
            <span>&copy; <?= date('Y') ?> <?= e(env('APP_NAME', 'Gather')) ?></span>
            <span>Organizer area</span>
        </div>
    </footer>
</div>
</body>
</html>
