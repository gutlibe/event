<?php if (flash('success')): ?>
    <div class="container-page pt-6">
        <div class="flash-success" role="status">
            <svg class="mt-0.5 shrink-0" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span><?= e(flash('success')) ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if (flash('error')): ?>
    <div class="container-page pt-6">
        <div class="flash-error" role="alert">
            <svg class="mt-0.5 shrink-0" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
            <span><?= e(flash('error')) ?></span>
        </div>
    </div>
<?php endif; ?>
