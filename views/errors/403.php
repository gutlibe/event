<?php
ob_start(); ?>
<div class="container-page flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
    <p class="font-display text-[90px] leading-none text-cream-300">403</p>
    <h1 class="mt-2 text-3xl sm:text-4xl"><?= e($title ?? 'Not allowed') ?></h1>
    <p class="mt-3 max-w-md text-sm text-ink-500">This event belongs to another organizer. Head back to your own dashboard.</p>
    <a href="/dashboard" class="btn-primary btn-sm mt-7">Go to dashboard</a>
</div>
<?php
$content = ob_get_clean();
$title = $title ?? 'Not allowed';
require base_path('views/layouts/public.php');
