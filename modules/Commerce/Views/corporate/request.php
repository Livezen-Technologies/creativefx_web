<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The request for a quote.
 *
 * This form is the whole of the corporate funnel: there is no basket and no
 * card field anywhere on it, and there never will be. What it has to do is
 * collect enough for somebody to write a real quote without a second email —
 * how many people, which courses, roughly when, where, and what the budget
 * looks like — while staying short enough that a manager finishes it.
 *
 * Three things about the shape are deliberate.
 *
 * **Only five fields are required**: company, contact, email, country and team
 * size. Everything that makes the quote better is optional, because a half-
 * answered enquiry from a real company is worth incomparably more than a
 * perfect one that was abandoned at question eleven.
 *
 * **The courses are checkboxes, not a multi-select.** A `<select multiple>` is
 * close to unusable on a phone and invisible to somebody who has never met one;
 * a list of checkboxes is understood by everybody, operable from the keyboard
 * for free, and lets the buyer tick three things without discovering the
 * control key.
 *
 * **The budget question is asked in the visitor's own currency, or not at
 * all.** The bands arrive as integer minor units and `money()` formats them
 * here — the single division by 100 on this page. A currency with no published
 * bands produces no field, because a rupee band shown to a dollar buyer is
 * worse than silence: they answer it, and the answer is wrong by a factor of
 * three hundred.
 *
 * @var array<string, list<array{slug:string, title:string}>> $courses  by pillar
 * @var array<string,string> $countries
 * @var list<string> $teamSizes
 * @var list<string> $modes
 * @var array<string, array{min:?int, max:?int}> $budgetBands  minor units
 * @var string  $currency
 * @var string  $prefill    course slug from ?course=, already validated
 * @var string  $utmQuery   the campaign parameters, encoded, or ''
 * @var array   $errors
 * @var ?string $reference  set only by a successful submit
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$sent = ($reference ?? null) !== null && $reference !== '';

/** A field's error message, or an empty string. */
$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');

/**
 * Whether this render is a bounce back from a failed validation.
 *
 * `company` is required, so it is present in the old input whenever the form
 * has been submitted — even as an empty string. Testing for that rather than
 * for the presence of `courses` matters: somebody who unticked every course
 * and then tripped a validation error must not have `?course=` silently
 * re-ticked underneath them on the way back.
 */
$repost = old('company', null, false) !== null;

$checkedCourses = $repost
    ? array_map('strval', (array) old('courses', [], false))
    : ($prefill !== '' ? [$prefill] : []);

$selectedCountry = strtoupper((string) (old('country', '', false) ?: 'LK'));
$selectedTeam    = (string) old('team_size', '', false);
$selectedMode    = (string) old('mode', '', false);
$selectedBudget  = (string) old('budget_band', '', false);

/** The team-size bands, labelled. Keys match what the controller validates. */
$teamSizeLabels = [
    '1-4'      => lang('Commerce.corporate.form.team_1_4'),
    '5-9'      => lang('Commerce.corporate.form.team_5_9'),
    '10-19'    => lang('Commerce.corporate.form.team_10_19'),
    '20-49'    => lang('Commerce.corporate.form.team_20_49'),
    '50+'      => lang('Commerce.corporate.form.team_50_plus'),
    'not_sure' => lang('Commerce.corporate.form.team_not_sure'),
];

/** The delivery preferences, labelled and explained. */
$modeLabels = [
    'ONSITE'  => [lang('Commerce.corporate.form.mode_onsite'), lang('Commerce.corporate.form.mode_onsite_hint')],
    'VIRTUAL' => [lang('Commerce.corporate.form.mode_virtual'), lang('Commerce.corporate.form.mode_virtual_hint')],
    'HYBRID'  => [lang('Commerce.corporate.form.mode_hybrid'), lang('Commerce.corporate.form.mode_hybrid_hint')],
    'UNSURE'  => [lang('Commerce.corporate.form.mode_unsure'), lang('Commerce.corporate.form.mode_unsure_hint')],
];

/**
 * The budget options, formatted.
 *
 * An open lower bound reads "up to X", an open upper bound "over X", and a
 * closed band as a range. Built here rather than in the controller because
 * this is the last moment before a human reads it, which is the only place
 * `money()` is allowed to divide by a hundred.
 */
$budgetOptions = ['not_sure' => lang('Commerce.corporate.form.budget_not_sure')];
foreach ($budgetBands as $code => $band) {
    $budgetOptions[$code] = $band['min'] === null
        ? lang('Commerce.corporate.form.budget_upto', [money($band['max'], $currency)])
        : ($band['max'] === null
            ? lang('Commerce.corporate.form.budget_over', [money($band['min'], $currency)])
            : lang('Commerce.corporate.form.budget_between', [money($band['min'], $currency), money($band['max'], $currency)]));
}

$recaptcha = \Modules\Core\Libraries\Recaptcha::isActive();
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Commerce.corporate.eyebrow'),
    'heading' => $sent ? lang('Commerce.corporate.thanks.heading') : lang('Commerce.corporate.form.title'),
    'intro'   => $sent ? lang('Commerce.corporate.thanks.intro') : lang('Commerce.corporate.form.intro'),
], ['saveData' => false]) ?>

<div class="container-x py-14">

<?php if ($sent): ?>

    <?php // ── The thank-you state ────────────────────────────────────────── ?>
    <?php // Reached only through the flash set by a successful submit, so it
          // cannot be typed into an address bar and a reload does not send a
          // second enquiry. It says when to expect an answer and gives the
          // reference to quote, because "we have received your enquiry" with
          // nothing to hold on to is the same as silence. ?>
    <div class="max-w-2xl">
        <div class="rounded-3xl border border-line bg-surface p-7 sm:p-9" role="status">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-red">
                <?= esc(lang('Commerce.corporate.thanks.reference_label')) ?>
            </p>
            <p class="mt-2 text-2xl font-bold tabular-nums"><?= esc($reference) ?></p>

            <p class="mt-6 leading-relaxed text-white/75"><?= esc(lang('Commerce.corporate.thanks.body')) ?></p>
            <p class="mt-4 leading-relaxed text-white/60"><?= esc(lang('Commerce.corporate.thanks.next')) ?></p>

            <?php if ($mail = setting('email', '', 'contact')): ?>
                <p class="mt-4 text-sm text-white/60">
                    <?= esc(lang('Commerce.corporate.thanks.urgent')) ?>
                    <a href="mailto:<?= esc($mail, 'attr') ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4"><?= esc($mail) ?></a>
                </p>
            <?php endif; ?>

            <div class="mt-7 flex flex-wrap gap-4">
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand"><?= esc(lang('Commerce.corporate.browse_catalogue')) ?></a>
                <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Commerce.corporate.thanks.see_dates')) ?></a>
            </div>
        </div>
    </div>

<?php else: ?>

    <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-14">
        <div class="min-w-0">

            <?php if ($msg = session()->getFlashdata('error')): ?>
                <p role="alert" class="mb-8 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
                    <?= esc($msg) ?>
                </p>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <?php // A summary at the top as well as a message beside each
                      // field: on a form this long the invalid box is often two
                      // screens away from the button that was just pressed. ?>
                <p role="alert" class="mb-8 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
                    <?= esc(lang('Commerce.corporate.form.errors_summary', [count($errors)])) ?>
                </p>
            <?php endif; ?>

            <?php // The action carries the campaign parameters, because
                  // LeadModel::utm() reads them from the query string of the
                  // request that creates the lead — and that request is this
                  // POST. Posting to a bare address attributes every corporate
                  // enquiry to nothing. ?>
            <form method="post" action="<?= esc(locale_url('corporate/request-quote') . $utmQuery) ?>" class="space-y-10"
                <?php if ($recaptcha): ?>
                x-data="{ scored: false }"
                @submit="if (!scored &amp;&amp; window.recaptchaToken) { $event.preventDefault(); window.recaptchaToken('corporate').then(t =&gt; { $refs.rcToken.value = t; }).catch(() =&gt; {}).finally(() =&gt; { scored = true; $el.requestSubmit(); }); }"
                <?php endif; ?>>
                <?= csrf_field() ?>

                <?php // A field no human ever sees and nearly every script
                      // fills. Hidden from assistive technology as well as from
                      // the eye, and taken out of the tab order, so a screen
                      // reader user is never asked to fill it in. ?>
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
                </div>

                <?php if ($recaptcha): ?>
                    <?php // Filled by the submit handler above. Empty when
                          // JavaScript is blocked, which the server treats as
                          // "no evidence of a bot" rather than as a rejection —
                          // a real buyer behind a corporate proxy still gets
                          // through. ?>
                    <input type="hidden" name="recaptcha_token" x-ref="rcToken" value="">
                <?php endif; ?>

                <p class="text-sm text-white/50"><?= esc(lang('Commerce.corporate.form.required_note')) ?></p>

                <?php // ── Who is asking ──────────────────────────────────── ?>
                <section aria-labelledby="about-you">
                    <h2 id="about-you" class="section-title"><?= esc(lang('Commerce.corporate.form.about_you')) ?></h2>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <label class="sm:col-span-2">
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.company')) ?> *</span>
                            <input id="company" name="company" type="text" required maxlength="191" autocomplete="organization"
                                   class="field" value="<?= esc(old('company', '', false), 'attr') ?>"
                                   <?= $errorFor('company') ? 'aria-invalid="true" aria-describedby="err-company"' : '' ?>>
                            <?php if ($e = $errorFor('company')): ?><span id="err-company" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                        </label>

                        <label>
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.name')) ?> *</span>
                            <input id="name" name="name" type="text" required maxlength="128" autocomplete="name"
                                   class="field" value="<?= esc(old('name', '', false), 'attr') ?>"
                                   <?= $errorFor('name') ? 'aria-invalid="true" aria-describedby="err-name"' : '' ?>>
                            <?php if ($e = $errorFor('name')): ?><span id="err-name" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                        </label>

                        <label>
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.email')) ?> *</span>
                            <input id="email" name="email" type="email" required maxlength="191" autocomplete="email"
                                   class="field" value="<?= esc(old('email', '', false), 'attr') ?>"
                                   <?= $errorFor('email') ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
                            <?php if ($e = $errorFor('email')): ?><span id="err-email" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                        </label>

                        <label>
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.phone')) ?></span>
                            <input id="phone" name="phone" type="tel" maxlength="48" autocomplete="tel"
                                   class="field" value="<?= esc(old('phone', '', false), 'attr') ?>"
                                   <?= $errorFor('phone') ? 'aria-invalid="true" aria-describedby="err-phone"' : '' ?>>
                            <?php if ($e = $errorFor('phone')): ?><span id="err-phone" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                        </label>

                        <label>
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.country')) ?> *</span>
                            <select id="country" name="country" required autocomplete="country" class="field"
                                    <?= $errorFor('country') ? 'aria-invalid="true" aria-describedby="err-country"' : '' ?>>
                                <?php foreach ($countries as $code => $countryName): ?>
                                    <option value="<?= esc($code, 'attr') ?>" <?= $selectedCountry === $code ? 'selected' : '' ?>>
                                        <?= esc($countryName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($e = $errorFor('country')): ?><span id="err-country" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                        </label>
                    </div>
                </section>

                <?php // ── What they need ─────────────────────────────────── ?>
                <section aria-labelledby="what-you-need">
                    <h2 id="what-you-need" class="section-title"><?= esc(lang('Commerce.corporate.form.what_you_need')) ?></h2>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <label>
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.team_size')) ?> *</span>
                            <select id="team_size" name="team_size" required class="field"
                                    <?= $errorFor('team_size') ? 'aria-invalid="true" aria-describedby="err-team_size"' : '' ?>>
                                <option value=""><?= esc(lang('Commerce.corporate.form.choose')) ?></option>
                                <?php foreach ($teamSizes as $size): ?>
                                    <option value="<?= esc($size, 'attr') ?>" <?= $selectedTeam === $size ? 'selected' : '' ?>>
                                        <?= esc($teamSizeLabels[$size] ?? $size) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($e = $errorFor('team_size')): ?><span id="err-team_size" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                            <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.corporate.form.team_size_hint')) ?></span>
                        </label>

                        <label>
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.dates')) ?></span>
                            <input id="preferred_dates" name="preferred_dates" type="text" maxlength="191"
                                   class="field" value="<?= esc(old('preferred_dates', '', false), 'attr') ?>"
                                   placeholder="<?= esc(lang('Commerce.corporate.form.dates_placeholder'), 'attr') ?>"
                                   <?= $errorFor('preferred_dates') ? 'aria-invalid="true"' : '' ?>>
                            <?php if ($e = $errorFor('preferred_dates')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>
                            <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.corporate.form.dates_hint')) ?></span>
                        </label>
                    </div>

                    <?php // ── Courses of interest ────────────────────────── ?>
                    <fieldset class="mt-8">
                        <legend class="field-label"><?= esc(lang('Commerce.corporate.form.courses')) ?></legend>
                        <p class="mb-3 text-xs text-white/50"><?= esc(lang('Commerce.corporate.form.courses_hint')) ?></p>

                        <?php if ($courses === []): ?>
                            <?php // No published courses yet. Said plainly
                                  // rather than shown as an empty box: the
                                  // enquiry is still worth sending, and the
                                  // message field is where it goes. ?>
                            <p class="rounded-xl border border-line bg-surface px-4 py-3 text-sm text-white/60">
                                <?= esc(lang('Commerce.corporate.form.courses_none')) ?>
                            </p>
                        <?php else: ?>
                            <?php // Scrollable rather than paged: every checkbox
                                  // inside is focusable, so a keyboard user
                                  // reaches the bottom of the list by tabbing
                                  // and the browser scrolls to follow. ?>
                            <div class="max-h-80 space-y-6 overflow-y-auto rounded-2xl border border-line bg-surface p-5">
                                <?php foreach ($courses as $pillar => $list): ?>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/45">
                                            <?= esc(lang('Catalog.pillar.' . $pillar)) ?>
                                        </p>
                                        <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                            <?php foreach ($list as $course): ?>
                                                <label class="flex items-start gap-3 text-sm text-white/80">
                                                    <input type="checkbox" name="courses[]" class="mt-0.5 h-4 w-4 shrink-0 accent-brand-red"
                                                           value="<?= esc($course['slug'], 'attr') ?>"
                                                           <?= in_array($course['slug'], $checkedCourses, true) ? 'checked' : '' ?>>
                                                    <span><?= esc($course['title']) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <label class="mt-5 block">
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.courses_other')) ?></span>
                            <input id="courses_other" name="courses_other" type="text" maxlength="191"
                                   class="field" value="<?= esc(old('courses_other', '', false), 'attr') ?>"
                                   <?= $errorFor('courses_other') ? 'aria-invalid="true"' : '' ?>>
                            <?php if ($e = $errorFor('courses_other')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>
                            <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.corporate.form.courses_other_hint')) ?></span>
                        </label>
                    </fieldset>
                </section>

                <?php // ── How and where ──────────────────────────────────── ?>
                <section aria-labelledby="how-and-where">
                    <h2 id="how-and-where" class="section-title"><?= esc(lang('Commerce.corporate.form.how_and_where')) ?></h2>

                    <fieldset class="mt-6">
                        <legend class="field-label"><?= esc(lang('Commerce.corporate.form.mode')) ?> *</legend>
                        <?php if ($e = $errorFor('mode')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <?php foreach ($modes as $mode): ?>
                                <?php [$label, $hint] = $modeLabels[$mode] ?? [$mode, '']; ?>
                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-surface p-4">
                                    <input type="radio" name="mode" required class="mt-1 h-4 w-4 shrink-0 accent-brand-red"
                                           value="<?= esc($mode, 'attr') ?>"
                                           <?= $selectedMode === $mode ? 'checked' : '' ?>>
                                    <span>
                                        <span class="block text-sm font-semibold"><?= esc($label) ?></span>
                                        <?php if ($hint !== ''): ?>
                                            <span class="mt-1 block text-xs leading-relaxed text-white/55"><?= esc($hint) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <label>
                            <span class="field-label"><?= esc(lang('Commerce.corporate.form.location')) ?></span>
                            <input id="location" name="location" type="text" maxlength="128"
                                   class="field" value="<?= esc(old('location', '', false), 'attr') ?>"
                                   <?= $errorFor('location') ? 'aria-invalid="true"' : '' ?>>
                            <?php if ($e = $errorFor('location')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>
                            <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.corporate.form.location_hint')) ?></span>
                        </label>

                        <?php // Asked only when there are bands published in
                              // this visitor's currency. See the note at the
                              // top of this file for why the alternative is
                              // worse than not asking. ?>
                        <?php if ($budgetBands !== []): ?>
                            <label>
                                <span class="field-label"><?= esc(lang('Commerce.corporate.form.budget')) ?></span>
                                <select id="budget_band" name="budget_band" class="field"
                                        <?= $errorFor('budget_band') ? 'aria-invalid="true"' : '' ?>>
                                    <?php foreach ($budgetOptions as $code => $label): ?>
                                        <option value="<?= esc($code, 'attr') ?>" <?= $selectedBudget === $code ? 'selected' : '' ?>>
                                            <?= esc($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($e = $errorFor('budget_band')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>
                                <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.corporate.form.budget_hint')) ?></span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <label class="mt-6 block">
                        <span class="field-label"><?= esc(lang('Commerce.corporate.form.message')) ?></span>
                        <textarea id="message" name="message" rows="5" maxlength="4000" class="field"
                                  <?= $errorFor('message') ? 'aria-invalid="true"' : '' ?>><?= esc(old('message', '', false)) ?></textarea>
                        <?php if ($e = $errorFor('message')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>
                        <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.corporate.form.message_hint')) ?></span>
                    </label>
                </section>

                <?php // ── Consent and send ───────────────────────────────── ?>
                <div class="space-y-5">
                    <?php // Un-ticked in the markup and never assumed. A
                          // pre-ticked box is not consent under either the
                          // GDPR or the Sri Lanka PDPA, and asking for a quote
                          // is not agreeing to a mailing list. ?>
                    <label class="flex items-start gap-3 text-sm text-white/70">
                        <input type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1"
                               class="mt-0.5 h-4 w-4 shrink-0 accent-brand-red"
                               <?= old('marketing_opt_in', '', false) ? 'checked' : '' ?>>
                        <span><?= esc(lang('Commerce.corporate.form.marketing_opt_in')) ?></span>
                    </label>

                    <p class="text-xs leading-relaxed text-white/50"><?= esc(lang('Commerce.corporate.form.privacy_note')) ?></p>

                    <button type="submit" class="btn-brand"><?= esc(lang('Commerce.corporate.form.submit')) ?></button>
                </div>
            </form>
        </div>

        <?php // ── What happens next ──────────────────────────────────────── ?>
        <aside class="lg:pt-2" aria-labelledby="what-next">
            <div class="rounded-2xl border border-line bg-surface p-6">
                <h2 id="what-next" class="text-base font-semibold"><?= esc(lang('Commerce.corporate.form.next_title')) ?></h2>
                <p class="mt-3 text-sm leading-relaxed text-white/65"><?= esc(lang('Commerce.corporate.form.next_body')) ?></p>

                <?php // No price, no obligation and no card — stated here
                      // because it is the objection that stops a manager
                      // pressing send, and it costs one sentence to answer. ?>
                <p class="mt-4 text-sm leading-relaxed text-white/55"><?= esc(lang('Commerce.corporate.form.next_note')) ?></p>

                <?php if ($mail = setting('email', '', 'contact')): ?>
                    <p class="mt-4 border-t border-line pt-4 text-sm text-white/60">
                        <?= esc(lang('Commerce.corporate.form.next_email')) ?>
                        <a href="mailto:<?= esc($mail, 'attr') ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4"><?= esc($mail) ?></a>
                    </p>
                <?php endif; ?>

                <p class="mt-4 text-sm">
                    <a href="<?= esc(locale_url('corporate')) ?>" class="text-brand-red underline decoration-line underline-offset-4">
                        <?= esc(lang('Commerce.corporate.form.back_to_overview')) ?>
                    </a>
                </p>
            </div>
        </aside>
    </div>

<?php endif; ?>

</div>

<?= $this->endSection() ?>
