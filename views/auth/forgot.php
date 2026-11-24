<h1 class="text-3xl sm:text-4xl">Reset your password</h1>
<p class="mt-2 text-sm text-ink-500">Enter the email you signed up with and we'll send a reset link.</p>

<form method="post" action="/forgot-password" class="mt-8 space-y-5">
    <?= csrf_field() ?>

    <div>
        <label class="label" for="email">Email</label>
        <input id="email" type="email" name="email" class="input <?= error_for('email') ? 'input-error' : '' ?>" value="<?= e(old('email')) ?>" required autofocus autocomplete="email" placeholder="you@example.com">
        <?php if ($msg = error_for('email')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
    </div>

    <button type="submit" class="btn-primary w-full py-3">Send reset link</button>
</form>

<p class="mt-6 text-sm text-ink-500">
    Remembered it?
    <a href="/login" class="link">Back to sign in</a>
</p>
