<?= $this->extend('Modules\Account\Views\_layout') ?>

<?php
/**
 * Create an account.
 *
 * Seven controls, and every one of them is either needed to issue a certificate
 * in the right name, needed to quote a price, or a consent that has to be asked
 * for explicitly. Nothing else is here: a registration form is not the place to
 * find out somebody's job title, and each optional question on it is a share of
 * the people who close the tab instead.
 *
 * Two things about it are deliberate and would be easy to undo by accident.
 *
 * **The marketing box ships un-ticked and is never assumed.** A pre-ticked box
 * is not consent under either the GDPR or the Sri Lanka PDPA, and buying a
 * course is not agreeing to a mailing list.
 *
 * **The terms links sit outside the label, not inside it.** A link inside a
 * `<label for="…">` is a trap: clicking it activates the label as well, so the
 * box the visitor never meant to touch silently toggles — and when the link
 * opens in a new tab they come back to a form that has quietly un-agreed to the
 * terms. The label carries the words; the links are the sentence underneath.
 *
 * @var array<string,string>  $errors
 * @var array<string,string>  $countries
 * @var string  $country    pre-selected from the same signals the price came from
 * @var ?string $sent       the address, set only by a submission
 * @var bool    $mailOff    the site has no mail server yet
 * @var bool    $recaptcha
 */
helper(['norlanka', 'url']);

$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');

$selectedCountry = strtoupper((string) (old('country', '', false) ?: $country));

// Linked only when the pages exist. Settings → Footer holds both addresses, and
// a link to a policy nobody has written yet is worse than the plain words.
$termsUrl   = trim((string) setting('terms_url', '', 'footer'));
$privacyUrl = trim((string) setting('privacy_url', '', 'footer'));
?>

<?= $this->section('form') ?>

<?php if ($sent): ?>

    <?php // The heading and the sentence naming the address are drawn by the
          // shell; what belongs in the card is what to do if nothing arrives.
          // The wording upstairs is true whether or not that address already
          // had an account — the server behaved identically either way, and
          // the copy has to as well. ?>
    <p class="leading-relaxed text-white/70"><?= esc(lang('Account.register.sent_spam')) ?></p>

    <?php if ($mailOff): ?>
        <?php // Said out loud rather than hidden. Sending somebody to an inbox
              // nothing was posted to is a small lie that costs a booking, and
              // this depends on the site having no SMTP account, never on the
              // address typed, so it cannot be used to probe for accounts. ?>
        <p role="alert" class="mt-5 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
            <?= esc(lang('Account.register.mail_off')) ?>
        </p>
    <?php endif; ?>

    <div class="mt-7 flex flex-wrap gap-3">
        <a href="<?= esc(locale_url('account/login')) ?>" class="btn-brand"><?= esc(lang('Account.register.sign_in')) ?></a>
        <a href="<?= esc(locale_url('account/register')) ?>" class="btn-ghost"><?= esc(lang('Account.register.sent_again')) ?></a>
    </div>

<?php else: ?>

    <?php if ($errors !== []): ?>
        <p role="alert" class="mb-6 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
            <?= esc(lang('Account.register.errors_summary')) ?>
        </p>
    <?php endif; ?>

    <form method="post" action="<?= esc(locale_url('account/register')) ?>" class="space-y-5"
        <?php if ($recaptcha): ?>
        x-data="{ scored: false }"
        @submit="if (!scored &amp;&amp; window.recaptchaToken) { $event.preventDefault(); window.recaptchaToken('register').then(t =&gt; { $refs.rcToken.value = t; }).catch(() =&gt; {}).finally(() =&gt; { scored = true; $el.requestSubmit(); }); }"
        <?php endif; ?>>
        <?= csrf_field() ?>

        <?php // A field no human ever sees and nearly every script fills.
              // Hidden from assistive technology as well as from the eye, and
              // out of the tab order, so a screen reader user is never asked
              // to fill in something that would refuse their registration. ?>
        <div class="hidden" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
        </div>

        <?php if ($recaptcha): ?>
            <?php // Filled by the submit handler above, and empty when
                  // JavaScript is blocked — which the server reads as "no
                  // evidence of a bot" rather than as a rejection. ?>
            <input type="hidden" name="recaptcha_token" x-ref="rcToken" value="">
        <?php endif; ?>

        <label class="block">
            <span class="field-label"><?= esc(lang('Account.register.name')) ?></span>
            <input id="name" name="name" type="text" required maxlength="128" autocomplete="name"
                   class="field" value="<?= esc(old('name', '', false), 'attr') ?>"
                   aria-describedby="hint-name<?= $errorFor('name') ? ' err-name' : '' ?>"
                   <?= $errorFor('name') ? 'aria-invalid="true"' : '' ?>>
            <span id="hint-name" class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Account.register.name_hint')) ?></span>
            <?php if ($e = $errorFor('name')): ?><span id="err-name" class="field-error"><?= esc($e) ?></span><?php endif; ?>
        </label>

        <label class="block">
            <span class="field-label"><?= esc(lang('Account.register.email')) ?></span>
            <input id="email" name="email" type="email" required maxlength="191" autocomplete="email" inputmode="email"
                   class="field" value="<?= esc(old('email', '', false), 'attr') ?>"
                   aria-describedby="hint-email<?= $errorFor('email') ? ' err-email' : '' ?>"
                   <?= $errorFor('email') ? 'aria-invalid="true"' : '' ?>>
            <span id="hint-email" class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Account.register.email_hint')) ?></span>
            <?php if ($e = $errorFor('email')): ?><span id="err-email" class="field-error"><?= esc($e) ?></span><?php endif; ?>
        </label>

        <label class="block">
            <span class="field-label"><?= esc(lang('Account.register.password')) ?></span>
            <?php // minlength as well as the server rule, so the browser and
                  // the password manager both know the shape before anything
                  // is submitted. The server is still the one that decides. ?>
            <input id="password" name="password" type="password" required minlength="12" maxlength="72"
                   autocomplete="new-password" class="field"
                   aria-describedby="hint-password<?= $errorFor('password') ? ' err-password' : '' ?>"
                   <?= $errorFor('password') ? 'aria-invalid="true"' : '' ?>>
            <span id="hint-password" class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Account.register.password_hint')) ?></span>
            <?php if ($e = $errorFor('password')): ?><span id="err-password" class="field-error"><?= esc($e) ?></span><?php endif; ?>
        </label>

        <label class="block">
            <span class="field-label"><?= esc(lang('Account.register.password_confirm')) ?></span>
            <input id="password_confirm" name="password_confirm" type="password" required minlength="12" maxlength="72"
                   autocomplete="new-password" class="field"
                   <?= $errorFor('password_confirm') ? 'aria-invalid="true" aria-describedby="err-password-confirm"' : '' ?>>
            <?php if ($e = $errorFor('password_confirm')): ?><span id="err-password-confirm" class="field-error"><?= esc($e) ?></span><?php endif; ?>
        </label>

        <label class="block">
            <span class="field-label"><?= esc(lang('Account.register.country')) ?></span>
            <select id="country" name="country" required autocomplete="country" class="field"
                    aria-describedby="hint-country<?= $errorFor('country') ? ' err-country' : '' ?>"
                    <?= $errorFor('country') ? 'aria-invalid="true"' : '' ?>>
                <?php foreach ($countries as $code => $countryName): ?>
                    <option value="<?= esc($code, 'attr') ?>" <?= $selectedCountry === $code ? 'selected' : '' ?>>
                        <?= esc($countryName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span id="hint-country" class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Account.register.country_hint')) ?></span>
            <?php if ($e = $errorFor('country')): ?><span id="err-country" class="field-error"><?= esc($e) ?></span><?php endif; ?>
        </label>

        <div class="space-y-4 border-t border-line pt-5">
            <div class="flex items-start gap-3">
                <input type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1"
                       class="mt-0.5 h-4 w-4 shrink-0 accent-brand-red"
                       aria-describedby="hint-marketing"
                       <?= old('marketing_opt_in', '', false) ? 'checked' : '' ?>>
                <div>
                    <label for="marketing_opt_in" class="text-sm text-white/80"><?= esc(lang('Account.register.marketing')) ?></label>
                    <p id="hint-marketing" class="mt-1 text-xs leading-relaxed text-white/50"><?= esc(lang('Account.register.marketing_hint')) ?></p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <input type="checkbox" id="terms" name="terms" value="1" required
                       class="mt-0.5 h-4 w-4 shrink-0 accent-brand-red"
                       <?= $errorFor('terms') ? 'aria-invalid="true" aria-describedby="err-terms"' : '' ?>
                       <?= old('terms', '', false) ? 'checked' : '' ?>>
                <div>
                    <label for="terms" class="text-sm text-white/80"><?= esc(lang('Account.register.terms')) ?></label>
                    <?php if ($termsUrl !== '' || $privacyUrl !== ''): ?>
                        <p class="mt-1 text-xs text-white/50">
                            <?php if ($termsUrl !== ''): ?>
                                <a href="<?= esc(menu_link($termsUrl), 'attr') ?>" target="_blank" rel="noopener"
                                   class="underline decoration-line underline-offset-4 hover:text-brand-red"><?= esc(lang('Account.register.terms_link')) ?></a>
                            <?php endif; ?>
                            <?php if ($termsUrl !== '' && $privacyUrl !== ''): ?><span aria-hidden="true"> · </span><?php endif; ?>
                            <?php if ($privacyUrl !== ''): ?>
                                <a href="<?= esc(menu_link($privacyUrl), 'attr') ?>" target="_blank" rel="noopener"
                                   class="underline decoration-line underline-offset-4 hover:text-brand-red"><?= esc(lang('Account.register.privacy_link')) ?></a>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($e = $errorFor('terms')): ?><span id="err-terms" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-brand w-full"><?= esc(lang('Account.register.submit')) ?></button>
    </form>

<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('below') ?>

<?php if (! $sent): ?>
    <p class="mt-6 text-center text-sm text-white/70">
        <?= esc(lang('Account.register.have_account')) ?>
        <a href="<?= esc(locale_url('account/login')) ?>" class="font-semibold text-brand-red underline decoration-line underline-offset-4">
            <?= esc(lang('Account.register.sign_in')) ?>
        </a>
    </p>
<?php endif; ?>

<?= $this->endSection() ?>
