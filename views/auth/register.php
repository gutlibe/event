<h1 class="text-3xl sm:text-4xl">Create your account</h1>
<p class="mt-2 text-sm text-ink-500">Free to start. Publish your first event in a few minutes.</p>

<form method="post" action="/register" class="mt-8 space-y-5">
    <?= csrf_field() ?>

    <div>
        <label class="label" for="name">Your name</label>
        <input id="name" name="name" class="input <?= error_for('name') ? 'input-error' : '' ?>" value="<?= e(old('name')) ?>" required autofocus autocomplete="name" placeholder="Ama Mensah">
        <?php if ($msg = error_for('name')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
    </div>

    <div>
        <label class="label" for="email">Email</label>
        <input id="email" type="email" name="email" class="input <?= error_for('email') ? 'input-error' : '' ?>" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com">
        <?php if ($msg = error_for('email')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
    </div>

    <div>
        <label class="label" for="password">Password</label>
        <input id="password" type="password" name="password" class="input <?= error_for('password') ? 'input-error' : '' ?>" required autocomplete="new-password" placeholder="At least 8 characters">
        <?php if ($msg = error_for('password')): ?><p class="error"><?= e($msg) ?></p><?php endif; ?>
    </div>

    <div>
        <label class="label" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password" placeholder="Repeat the password">
    </div>

    <button type="submit" class="btn-primary w-full py-3">Create account</button>
</form>

<p class="mt-6 text-sm text-ink-500">
    Already have an account?
    <a href="/login" class="link">Sign in</a>
</p>
