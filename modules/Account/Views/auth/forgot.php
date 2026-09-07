<?= $this->extend('Modules\Account\Views\_layout') ?>

<?php
/**
 * Ask for a password reset link.
 *
 * One field, and a confirmation that is the same sentence whether or not the
 * address is registered — the shell prints "if that address has an account, a
 * link is on its way", and it says "if" because the server genuinely did the
 * same thing either way. A page that said "we have emailed you" for a real
 * address and "no account found" for an unknown one would be a way of asking
 * this site who its customers are, one address at a time.
 *
 * The note under the field is for the other population this form serves: people
 * whose employer booked their seat. Their account was created for them with a
 * password nobody can type, so this is not "reset" for them at all — it is how
 * they open the account for the first time.
 *
 * @var array<string,string> $errors
 * @var ?string $sent     the address, set only by a submission
 * @var bool    $mailOff  the site has no mail server yet
 */
helper(['norlanka', 'url']);

$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');
?>

<?= $this->section('form') ?>

<?php if ($sent): ?>

    <p class="leading-relaxed text-white/70"><?= esc(lang('Account.forgot.sent_spam')) ?></p>

    <?php if ($mailOff): ?>
        <p role="alert" class="mt-5 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
            <?= esc(lang('Account.forgot.mail_off')) ?>
        </p>
    <?php endif; ?>

    <div class="mt-7">
        <a href="<?= esc(locale_url('account/login')) ?>" class="btn-brand"><?= esc(lang('Account.forgot.back')) ?></a>
    </div>

<?php else: ?>

    <form method="post" action="<?= esc(locale_url('account/forgot')) ?>" class="space-y-5">
        <?= csrf_field() ?>

        <div class="hidden" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
        </div>

        <label class="block">
            <span class="field-label"><?= esc(lang('Account.forgot.email')) ?></span>
            <input id="email" name="email" type="email" required maxlength="191"
                   autocomplete="email" inputmode="email" class="field"
                   value="<?= esc(old('email', '', false), 'attr') ?>"
                   <?= $errors === [] && ! session()->getFlashdata('error') ? 'autofocus' : '' ?>
                   <?= $errorFor('email') ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
            <?php if ($e = $errorFor('email')): ?><span id="err-email" class="field-error"><?= esc($e) ?></span><?php endif; ?>
        </label>

        <button type="submit" class="btn-brand w-full"><?= esc(lang('Account.forgot.submit')) ?></button>
    </form>

<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('below') ?>

<?php if (! $sent): ?>
    <p class="mt-6 rounded-2xl border border-line px-4 py-3 text-sm leading-relaxed text-white/60">
        <?= esc(lang('Account.forgot.invited')) ?>
    </p>

    <p class="mt-6 text-center text-sm">
        <a href="<?= esc(locale_url('account/login')) ?>" class="text-white/70 underline decoration-line underline-offset-4 transition hover:text-brand-red">
            <?= esc(lang('Account.forgot.back')) ?>
        </a>
    </p>
<?php endif; ?>

<?= $this->endSection() ?>
