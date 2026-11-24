<?php
ob_start(); ?>
<div class="container-page flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
    <p class="font-display text-[90px] leading-none text-cream-300">419</p>
    <h1 class="mt-2 text-3xl sm:text-4xl">Your session expired</h1>
    <p class="mt-3 max-w-md text-sm text-ink-500">For your security, forms expire after a while. Go back, refresh the page and submit again.</p>
    <a href="/" class="btn-primary btn-sm mt-7">Back to the site</a>
</div>
<?php
$content = ob_get_clean();
$title = 'Session expired';
require base_path('views/layouts/public.php');
