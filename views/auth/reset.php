<h1 class="text-3xl sm:text-4xl">Choose a new password</h1>
<p class="mt-2 text-sm text-ink-500">Pick something you haven't used before.</p>

<?php if (empty($token) || empty($email)): ?>
    <div class="mt-8 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        This reset link is incomplete. <a href="/forgot-password" class="font-medium underline">Request a new one</a>.
    </div>
<?php else: ?>
    <form method="post" action="/reset-password" class="mt-8 space-y-5">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <input type="hidden" name="email" value="<?= e($email) ?>">

        <div>
            <label class="label" for="email-display">Email</label>
            <input id="email-display" class="input bg-cream-50" value="<?= e($email) ?>" disabled>
        </div>

        <div>
            <label class="label" for="password">New password</label>
            <input id="password" type="password" name="password" class="input <?= error_for('password') ? 'input-error' : '' ?>" required autofocus autocomplete="new-password" placeholder="At least 8 characters">
            <?php if ($msg = error_for('password') ?: error_for('token') ?: error_for('email')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password" placeholder="Repeat the password">
        </div>

        <button type="submit" class="btn-primary w-full py-3">Update password</button>
    </form>
<?php endif; ?>

<p class="mt-6 text-sm text-ink-500">
    <a href="/login" class="link">Back to sign in</a>
</p>
