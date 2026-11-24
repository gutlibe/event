<?php
ob_start(); ?>
<div class="container-page flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
    <p class="font-display text-[90px] leading-none text-cream-300">404</p>
    <h1 class="mt-2 text-3xl sm:text-4xl"><?= e($title ?? 'Page not found') ?></h1>
    <p class="mt-3 max-w-md text-sm text-ink-500">The page you're looking for has moved, sold out, or never existed. Try the event list instead.</p>
    <div class="mt-7 flex flex-wrap justify-center gap-2">
        <a href="/" class="btn-primary btn-sm">Browse events</a>
        <a href="/dashboard" class="btn-outline btn-sm">Organizer dashboard</a>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = $title ?? 'Page not found';
require base_path('views/layouts/public.php');
