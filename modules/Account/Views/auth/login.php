<?= $this->extend('Modules\Account\Views\_layout') ?>

<?php
/**
 * Sign in.
 *
 * Two fields and nothing else above the button, because every extra control on
 * a sign-in form is something between a returning customer and the class they
 * have already paid for.
 *
 * The note under the card is the one piece of copy here that earns its place.
 * The commonest confused arrival at this form is somebody whose employer booked
 * their seat: EnrolmentService created their account with a password that
 * cannot be produced by any keyboard, so this form can only ever refuse them,
 * for ever, with a message that is true and useless. Telling them where to go
 * is cheaper than the support email.
 *
 * @var array<string,string> $errors
 */
helper(['norlanka', 'url']);

$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');
?>

<?= $this->section('form') ?>

<form method="post" action="<?= esc(locale_url('account/login')) ?>" class="space-y-5">
    <?= csrf_field() ?>

    <label class="block">
        <span class="field-label"><?= esc(lang('Account.login.email')) ?></span>
        <input id="email" name="email" type="email" required maxlength="191"
               autocomplete="email" inputmode="email" class="field"
               value="<?= esc(old('email', '', false), 'attr') ?>"
               <?php // Focused only when there is nothing to read first. Moving
                     // the caret into a field on load would push a screen
                     // reader past the alert explaining why the last attempt
                     // failed. ?>
               <?= $errors === [] && ! session()->getFlashdata('error') ? 'autofocus' : '' ?>
               <?= $errorFor('email') ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
        <?php if ($e = $errorFor('email')): ?><span id="err-email" class="field-error"><?= esc($e) ?></span><?php endif; ?>
    </label>

    <label class="block">
        <span class="field-label"><?= esc(lang('Account.login.password')) ?></span>
        <input id="password" name="password" type="password" required
               autocomplete="current-password" class="field"
               <?= $errorFor('password') ? 'aria-invalid="true" aria-describedby="err-password"' : '' ?>>
        <?php if ($e = $errorFor('password')): ?><span id="err-password" class="field-error"><?= esc($e) ?></span><?php endif; ?>
    </label>

    <button type="submit" class="btn-brand w-full"><?= esc(lang('Account.login.submit')) ?></button>

    <p class="text-center text-sm">
        <a href="<?= esc(locale_url('account/forgot')) ?>" class="text-white/70 underline decoration-line underline-offset-4 transition hover:text-brand-red">
            <?= esc(lang('Account.login.forgot')) ?>
        </a>
    </p>
</form>

<?= $this->endSection() ?>

<?= $this->section('below') ?>

<p class="mt-6 text-center text-sm text-white/70">
    <?= esc(lang('Account.login.no_account')) ?>
    <a href="<?= esc(locale_url('account/register')) ?>" class="font-semibold text-brand-red underline decoration-line underline-offset-4">
        <?= esc(lang('Account.login.create')) ?>
    </a>
</p>

<p class="mt-6 rounded-2xl border border-line px-4 py-3 text-sm leading-relaxed text-white/60">
    <?= esc(lang('Account.login.invited')) ?>
    <a href="<?= esc(locale_url('account/forgot')) ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4">
        <?= esc(lang('Account.forgot.heading')) ?>
    </a>
</p>

<?= $this->endSection() ?>
