<?= $this->extend('Modules\Account\Views\_layout') ?>

<?php
/**
 * Choose a new password.
 *
 * The token stays in the address of the form rather than in a hidden field, and
 * the form posts back to the same address. That is not decoration: the route is
 * `account/reset/{token}` for both the GET and the POST, and a hidden field
 * would be a second copy of the same secret with nothing keeping the two in
 * step.
 *
 * Nothing on this page says whether the token is any good. It cannot: checking
 * it here would mean redeeming it, and every corporate mail gateway fetches the
 * links in a message before a human sees one — the scanner would spend the
 * reset and the learner would meet a dead link on the page they had asked for.
 * The POST finds out, which is the only moment the answer matters, and a token
 * that has expired sends them back to ask for another.
 *
 * @var array<string,string> $errors
 * @var string $token
 */
helper(['norlanka', 'url']);

$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');
?>

<?= $this->section('form') ?>

<form method="post" action="<?= esc(locale_url('account/reset/' . rawurlencode($token))) ?>" class="space-y-5">
    <?= csrf_field() ?>

    <label class="block">
        <span class="field-label"><?= esc(lang('Account.reset.password')) ?></span>
        <input id="password" name="password" type="password" required minlength="12" maxlength="72"
               autocomplete="new-password" class="field" autofocus
               aria-describedby="hint-password<?= $errorFor('password') ? ' err-password' : '' ?>"
               <?= $errorFor('password') ? 'aria-invalid="true"' : '' ?>>
        <span id="hint-password" class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Account.register.password_hint')) ?></span>
        <?php if ($e = $errorFor('password')): ?><span id="err-password" class="field-error"><?= esc($e) ?></span><?php endif; ?>
    </label>

    <label class="block">
        <span class="field-label"><?= esc(lang('Account.reset.password_confirm')) ?></span>
        <input id="password_confirm" name="password_confirm" type="password" required minlength="12" maxlength="72"
               autocomplete="new-password" class="field"
               <?= $errorFor('password_confirm') ? 'aria-invalid="true" aria-describedby="err-password-confirm"' : '' ?>>
        <?php if ($e = $errorFor('password_confirm')): ?><span id="err-password-confirm" class="field-error"><?= esc($e) ?></span><?php endif; ?>
    </label>

    <button type="submit" class="btn-brand w-full"><?= esc(lang('Account.reset.submit')) ?></button>
</form>

<?= $this->endSection() ?>

<?= $this->section('below') ?>

<p class="mt-6 text-center text-sm text-white/70">
    <a href="<?= esc(locale_url('account/forgot')) ?>" class="underline decoration-line underline-offset-4 transition hover:text-brand-red">
        <?= esc(lang('Account.reset.ask_again')) ?>
    </a>
</p>

<?= $this->endSection() ?>
