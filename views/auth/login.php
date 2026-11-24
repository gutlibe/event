<h1 class="text-3xl sm:text-4xl">Welcome back</h1>
<p class="mt-2 text-sm text-ink-500">Sign in to manage your events and guest lists.</p>

<form method="post" action="/login" class="mt-8 space-y-5">
    <?= csrf_field() ?>

    <div>
        <label class="label" for="email">Email</label>
        <input id="email" type="email" name="email" class="input <?= error_for('email') ? 'input-error' : '' ?>" value="<?= e(old('email')) ?>" required autofocus autocomplete="email" placeholder="you@example.com">
        <?php if ($msg = error_for('email')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label class="label" for="password">Password</label>
            <a href="/forgot-password" class="mb-1.5 text-xs font-medium text-ink-500 hover:text-ink-900">Forgot password?</a>
        </div>
        <input id="password" type="password" name="password" class="input <?= error_for('password') ? 'input-error' : '' ?>" required autocomplete="current-password" placeholder="Your password">
        <?php if ($msg = error_for('password')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
    </div>

    <button type="submit" class="btn-primary w-full py-3">Sign in</button>
</form>

<p class="mt-6 text-sm text-ink-500">
    New to <?= e(env('APP_NAME', 'Gather')) ?>?
    <a href="/register" class="link">Create an account</a>
</p>
