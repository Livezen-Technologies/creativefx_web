<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The learner's own details, and their password.
 *
 * Three of these fields are not decoration. `country` decides which price book
 * they are quoted from next time; `timezone` decides what hour a joining page
 * and a reminder tell them the class starts; `locale` decides which language
 * their account and their certificate come in. Each is stored as a code, and an
 * unrecognised one is dropped rather than saved — a junk value in any of the
 * three is wrong in a way nobody notices for months.
 *
 * The email address is shown and cannot be changed here. Changing it means
 * verifying it again, and an address that can be swapped from a signed-in
 * session is an account takeover that survives a password reset.
 *
 * Two concerns share one form on purpose: somebody correcting their timezone
 * should not have to find a second page to change a password. They are saved
 * independently, so a wrong current password rejects the password change alone
 * and everything else typed comes back with them.
 *
 * @var array|null $user
 * @var array<string,string> $countries
 * @var array<string, array<string,string>> $timezones  region => [identifier => city]
 * @var list<array{code:string,label:string,native:string}> $locales
 * @var int    $minPassword
 * @var array  $errors
 * @var string $current
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

/** A field's error message, or an empty string. */
$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');

$value = static fn (string $field, string $fallback = ''): string
    => (string) old($field, (string) ($fallback ?: ''), false);

$verified = ! empty($user['email_verified_at']);
$optIn    = old('marketing_opt_in', null, false) !== null
    ? true
    : (int) ($user['marketing_opt_in'] ?? 0) === 1;

// A form that has bounced back from validation is detectable by `first_name`
// being present in the old input, since it is required and therefore always
// posted. Without that test an unticked consent box would silently re-tick
// itself from the stored value on the way back.
$repost = old('first_name', null, false) !== null;
if ($repost) {
    $optIn = old('marketing_opt_in', null, false) !== null;
}

$currentCountry  = strtoupper($value('country', (string) ($user['country'] ?? '')));
$currentTimezone = $value('timezone', (string) ($user['timezone'] ?? ''));
$currentLocale   = $value('locale', (string) ($user['locale'] ?? '')) ?: current_locale();
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Account.nav.title'),
    'heading' => lang('Account.profile.title'),
    'intro'   => lang('Account.profile.intro'),
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-14 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-14">

    <?= view('Modules\Account\Views\partials\account_nav', ['current' => $current], ['saveData' => false]) ?>

    <div class="min-w-0 max-w-2xl space-y-8">

        <?php if ($msg = session()->getFlashdata('notice')): ?>
            <p role="status" class="rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <?php // A summary as well as a message beside each field: on a form
                  // this long the invalid box can be a screen away from the
                  // button that was just pressed. ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
                <?= esc(lang('Account.profile.errors_summary', [count($errors)])) ?>
            </p>
        <?php endif; ?>

        <form method="post" action="<?= esc(locale_url('account/profile')) ?>" class="space-y-10">
            <?= csrf_field() ?>

            <?php // ── Who you are ────────────────────────────────────────── ?>
            <section aria-labelledby="details">
                <h2 id="details" class="section-title"><?= esc(lang('Account.profile.details')) ?></h2>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <span class="field-label" id="email-label"><?= esc(lang('Account.profile.email')) ?></span>
                        <?php // Read-only rather than disabled: a disabled field
                              // is skipped by the keyboard and cannot be read by
                              // somebody tabbing through the form to check it. ?>
                        <input type="email" value="<?= esc((string) ($user['email'] ?? ''), 'attr') ?>" readonly
                               aria-labelledby="email-label" aria-describedby="email-help"
                               class="field cursor-not-allowed text-white/60">
                        <span id="email-help" class="mt-1.5 block text-xs text-white/55">
                            <?= esc($verified ? lang('Account.profile.email_verified') : lang('Account.profile.email_unverified')) ?>
                            <?= esc(lang('Account.profile.email_change')) ?>
                        </span>
                    </div>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.first_name')) ?> *</span>
                        <input id="first_name" name="first_name" type="text" required maxlength="64" autocomplete="given-name"
                               class="field" value="<?= esc($value('first_name', (string) ($user['first_name'] ?? '')), 'attr') ?>"
                               <?= $errorFor('first_name') ? 'aria-invalid="true" aria-describedby="err-first_name"' : '' ?>>
                        <?php if ($e = $errorFor('first_name')): ?><span id="err-first_name" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.last_name')) ?></span>
                        <input id="last_name" name="last_name" type="text" maxlength="64" autocomplete="family-name"
                               class="field" value="<?= esc($value('last_name', (string) ($user['last_name'] ?? '')), 'attr') ?>"
                               <?= $errorFor('last_name') ? 'aria-invalid="true" aria-describedby="err-last_name"' : '' ?>>
                        <?php if ($e = $errorFor('last_name')): ?><span id="err-last_name" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.phone')) ?></span>
                        <input id="phone" name="phone" type="tel" maxlength="48" autocomplete="tel"
                               class="field" value="<?= esc($value('phone', (string) ($user['phone'] ?? '')), 'attr') ?>"
                               <?= $errorFor('phone') ? 'aria-invalid="true" aria-describedby="err-phone"' : '' ?>>
                        <?php if ($e = $errorFor('phone')): ?><span id="err-phone" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.country')) ?></span>
                        <select id="country" name="country" autocomplete="country" class="field"
                                aria-describedby="country-help">
                            <option value=""><?= esc(lang('Account.profile.not_set')) ?></option>
                            <?php foreach ($countries as $code => $name): ?>
                                <option value="<?= esc($code, 'attr') ?>" <?= $currentCountry === $code ? 'selected' : '' ?>>
                                    <?= esc($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span id="country-help" class="mt-1.5 block text-xs text-white/55"><?= esc(lang('Account.profile.country_help')) ?></span>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.company')) ?></span>
                        <input id="company" name="company" type="text" maxlength="191" autocomplete="organization"
                               class="field" value="<?= esc($value('company', (string) ($user['company'] ?? '')), 'attr') ?>">
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.job_title')) ?></span>
                        <input id="job_title" name="job_title" type="text" maxlength="128" autocomplete="organization-title"
                               class="field" value="<?= esc($value('job_title', (string) ($user['job_title'] ?? '')), 'attr') ?>">
                    </label>
                </div>
            </section>

            <?php // ── Time and language ──────────────────────────────────── ?>
            <section aria-labelledby="preferences">
                <h2 id="preferences" class="section-title"><?= esc(lang('Account.profile.preferences')) ?></h2>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.timezone')) ?></span>
                        <?php // Grouped by region rather than four hundred flat
                              // options: optgroups are announced by a screen
                              // reader and give a keyboard user something to
                              // skip through. ?>
                        <select id="timezone" name="timezone" class="field" aria-describedby="timezone-help">
                            <option value=""><?= esc(lang('Account.profile.not_set')) ?></option>
                            <?php foreach ($timezones as $region => $zones): ?>
                                <optgroup label="<?= esc($region, 'attr') ?>">
                                    <?php foreach ($zones as $identifier => $city): ?>
                                        <option value="<?= esc($identifier, 'attr') ?>" <?= $currentTimezone === $identifier ? 'selected' : '' ?>>
                                            <?= esc($city) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <span id="timezone-help" class="mt-1.5 block text-xs text-white/55"><?= esc(lang('Account.profile.timezone_help')) ?></span>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.locale')) ?></span>
                        <select id="locale" name="locale" class="field">
                            <?php foreach ($locales as $option): ?>
                                <option value="<?= esc($option['code'], 'attr') ?>" <?= $currentLocale === $option['code'] ? 'selected' : '' ?>>
                                    <?= esc($option['native']) ?><?php if ($option['native'] !== $option['label']): ?> (<?= esc($option['label']) ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label class="mt-6 flex items-start gap-3">
                    <input type="checkbox" name="marketing_opt_in" value="1" <?= $optIn ? 'checked' : '' ?>
                           class="mt-1 h-4 w-4 shrink-0 rounded border-line bg-surface text-brand-red focus-visible:ring-2 focus-visible:ring-brand-red">
                    <span class="text-sm leading-relaxed text-white/75"><?= esc(lang('Account.profile.marketing')) ?></span>
                </label>
                <?php // The consent question is separate from everything above
                      // it and worded as a choice, not as a pre-ticked default.
                      // Both the PDPA and the GDPR expect consent to be given
                      // rather than left in place. ?>
                <p class="mt-2 text-xs text-white/50"><?= esc(lang('Account.profile.marketing_note')) ?></p>
            </section>

            <?php // ── Password ───────────────────────────────────────────── ?>
            <section aria-labelledby="password">
                <h2 id="password" class="section-title"><?= esc(lang('Account.profile.password')) ?></h2>
                <p class="mt-2 text-sm text-white/60"><?= esc(lang('Account.profile.password_note')) ?></p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="field-label"><?= esc(lang('Account.profile.current_password')) ?></span>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                               class="field"
                               <?= $errorFor('current_password') ? 'aria-invalid="true" aria-describedby="err-current_password"' : '' ?>>
                        <?php if ($e = $errorFor('current_password')): ?><span id="err-current_password" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.new_password')) ?></span>
                        <input id="new_password" name="new_password" type="password" autocomplete="new-password"
                               minlength="<?= (int) $minPassword ?>" class="field" aria-describedby="new_password-help<?= $errorFor('new_password') ? ' err-new_password' : '' ?>"
                               <?= $errorFor('new_password') ? 'aria-invalid="true"' : '' ?>>
                        <span id="new_password-help" class="mt-1.5 block text-xs text-white/55">
                            <?= esc(lang('Account.profile.password_min', [(int) $minPassword])) ?>
                        </span>
                        <?php if ($e = $errorFor('new_password')): ?><span id="err-new_password" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Account.profile.confirm_password')) ?></span>
                        <input id="new_password_confirm" name="new_password_confirm" type="password" autocomplete="new-password"
                               minlength="<?= (int) $minPassword ?>" class="field"
                               <?= $errorFor('new_password_confirm') ? 'aria-invalid="true" aria-describedby="err-new_password_confirm"' : '' ?>>
                        <?php if ($e = $errorFor('new_password_confirm')): ?><span id="err-new_password_confirm" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>
                </div>
            </section>

            <button type="submit" class="btn-brand"><?= esc(lang('Account.profile.save')) ?></button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
