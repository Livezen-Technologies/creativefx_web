<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Admin\Views\layout');

$inputCls = 'w-full rounded-lg border border-white/15 bg-black/30 px-3 py-2 text-sm text-white placeholder:text-white/25 focus:border-brand-red focus:outline-none';
$me       = session()->get('admin_user') ?? [];
?>
<?= $this->section('content') ?>

<?php if (session('message')): ?>
    <div class="mb-5 rounded-lg border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm" role="status">
        <?= esc(session('message')) ?>
    </div>
<?php endif; ?>

<?php if (session('errors')): ?>
    <div class="mb-5 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm" role="alert">
        <p class="mb-1 font-semibold text-brand-red">Your password was not changed:</p>
        <ul class="list-inside list-disc space-y-0.5 text-white/80">
            <?php foreach ((array) session('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="max-w-xl">
    <h2 class="text-lg font-bold">Your account</h2>
    <p class="mt-1 text-sm text-white/50">
        Signed in as <span class="text-white/80"><?= esc($me['email'] ?? '') ?></span>.
    </p>

    <form method="post" action="<?= site_url('admin/account/password') ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="current_password" class="text-xs uppercase tracking-widest text-white/50">Current password</label>
            <?php // autocomplete matters here: without these the browser offers
                  // to fill the new-password boxes with the old password, and
                  // saves nothing when the change succeeds. ?>
            <input id="current_password" type="password" name="current_password" required
                   autocomplete="current-password" class="<?= $inputCls ?> mt-1.5">
        </div>

        <div>
            <label for="new_password" class="text-xs uppercase tracking-widest text-white/50">New password</label>
            <input id="new_password" type="password" name="new_password" required
                   minlength="<?= (int) $minLength ?>" autocomplete="new-password"
                   aria-describedby="new_password_help" class="<?= $inputCls ?> mt-1.5">
            <p id="new_password_help" class="mt-1.5 text-xs text-white/45">
                At least <?= (int) $minLength ?> characters. A phrase you will remember beats a short
                password with a symbol pushed into it.
            </p>
        </div>

        <div>
            <label for="confirm_password" class="text-xs uppercase tracking-widest text-white/50">New password again</label>
            <input id="confirm_password" type="password" name="confirm_password" required
                   minlength="<?= (int) $minLength ?>" autocomplete="new-password" class="<?= $inputCls ?> mt-1.5">
        </div>

        <button class="btn-brand">Change password</button>
    </form>

    <?php // Said plainly because the opposite is what people assume. Changing a
          // password here replaces this browser's session, not the others: CI4
          // keeps one file per session and nothing links them to an account, so
          // a browser already signed in elsewhere stays signed in until it is
          // signed out or the session expires. Promising otherwise would be the
          // more comfortable sentence and the wrong one to act on. ?>
    <p class="mt-6 border-t border-white/10 pt-4 text-xs leading-relaxed text-white/40">
        This changes the password for the next sign-in. Any browser already signed in as this
        account — including one you have forgotten about — stays signed in until it signs out or its
        session expires. If you are changing the password because somebody else may have had it,
        sign out of the machines you can reach as well.
    </p>
</div>

<?= $this->endSection() ?>
