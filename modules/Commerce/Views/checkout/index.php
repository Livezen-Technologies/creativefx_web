<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The checkout form.
 *
 * The one thing this page does that a generic shop checkout does not is ask who
 * is actually coming. A training seat is not a parcel: the buyer is routinely
 * not the learner, four seats are four different people, and the joining link,
 * the recording and the certificate belong to them rather than to whoever held
 * the card. So there is a name and an email box per seat, per line, and the
 * form is laid out around that rather than treating quantity as a number.
 *
 * The order of the page follows the order of the buyer's doubt: who am I
 * paying, who is coming, what does it cost, how do I pay, what have I agreed
 * to. The summary sits alongside on a wide screen and above the payment methods
 * on a phone, so the total is never something you have to scroll back for.
 *
 * @var array  $cart
 * @var array  $totals   subtotal, discount, coupon, tax, total, items, currency
 * @var list<\Modules\Commerce\Services\Gateways\PaymentGateway> $gateways
 * @var array  $warnings by cart-item id, lines whose seats have run short
 * @var ?array $user     the signed-in learner, or null
 * @var string $idempotencyKey
 * @var array  $errors
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$currency = (string) $totals['currency'];

/**
 * ISO 3166-1 alpha-2, for the billing address.
 *
 * Held here rather than in a database table because it is reference data that
 * does not change with the business: a list of countries is not something an
 * administrator should be able to get wrong. Sri Lanka is first because it is
 * where most of these bookings come from, and the rest follow alphabetically.
 */
$countries = [
    'LK' => 'Sri Lanka',
    'AE' => 'United Arab Emirates', 'AU' => 'Australia', 'BD' => 'Bangladesh',
    'BH' => 'Bahrain', 'CA' => 'Canada', 'CH' => 'Switzerland', 'CN' => 'China',
    'DE' => 'Germany', 'DK' => 'Denmark', 'EG' => 'Egypt', 'ES' => 'Spain',
    'FR' => 'France', 'HK' => 'Hong Kong SAR China', 'ID' => 'Indonesia',
    'IE' => 'Ireland', 'IN' => 'India', 'IT' => 'Italy', 'JP' => 'Japan',
    'KE' => 'Kenya', 'KW' => 'Kuwait', 'MV' => 'Maldives', 'MY' => 'Malaysia',
    'NG' => 'Nigeria', 'NL' => 'Netherlands', 'NO' => 'Norway',
    'NZ' => 'New Zealand', 'OM' => 'Oman', 'PH' => 'Philippines',
    'PK' => 'Pakistan', 'QA' => 'Qatar', 'SA' => 'Saudi Arabia',
    'SE' => 'Sweden', 'SG' => 'Singapore', 'TH' => 'Thailand', 'TR' => 'Türkiye',
    'US' => 'United States', 'GB' => 'United Kingdom', 'VN' => 'Viet Nam',
    'ZA' => 'South Africa',
];

$selectedCountry = strtoupper((string) (old('billing_country', '', false) ?: ($cart['country'] ?? 'LK')));
// Not `$gateways[0]->key() ?? ''`: the null-coalescing operator does not guard
// a method call, so an empty registry would be a fatal error rather than the
// honest "no way to pay in this currency" panel further down.
$selectedGateway = (string) (old('gateway', '', false) ?: ($gateways === [] ? '' : $gateways[0]->key()));

/** A field's error message, or an empty string. */
$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Commerce.checkout.eyebrow'),
    'heading' => lang('Commerce.checkout.title'),
    'intro'   => lang('Commerce.checkout.intro'),
], ['saveData' => false]) ?>

<div class="container-x grid gap-12 py-14 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">

    <?php // ── The form ───────────────────────────────────────────────────── ?>
    <div class="min-w-0">

        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="mb-8 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
                <?= esc($msg) ?>
            </p>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <?php // A single summary at the top as well as the message beside
                  // each field: on a form this long the invalid box is often
                  // two screens away from the button that was just pressed. ?>
            <p role="alert" class="mb-8 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
                <?= esc(lang('Commerce.checkout.errors_summary', [count($errors)])) ?>
            </p>
        <?php endif; ?>

        <?php if ($warnings !== []): ?>
            <?php // Read from the inventory without a lock, purely to warn. The
                  // binding answer is taken under a lock when the order is
                  // placed; this only makes sure nobody discovers it after
                  // typing four attendees in. ?>
            <p role="alert" class="mb-8 rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm">
                <?= esc(lang('Commerce.checkout.seats_changed')) ?>
                <a href="<?= esc(locale_url('cart')) ?>" class="font-semibold underline decoration-line underline-offset-4">
                    <?= esc(lang('Commerce.checkout.back_to_cart')) ?>
                </a>
            </p>
        <?php endif; ?>

        <?php if ($user === null): ?>
            <p class="mb-8 rounded-xl border border-line bg-surface px-4 py-3 text-sm text-white/70">
                <?= esc(lang('Commerce.checkout.guest_ok')) ?>
                <a href="<?= esc(locale_url('account/login')) ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4">
                    <?= esc(lang('Commerce.checkout.sign_in')) ?>
                </a>
            </p>
        <?php endif; ?>

        <form method="post" action="<?= esc(locale_url('checkout')) ?>" class="space-y-12" x-data>
            <?= csrf_field() ?>

            <?php // One key per rendered form, minted server-side and held in
                  // the session. Every submission of this form carries the same
                  // one, so the second click, the back button and the mobile
                  // browser re-POSTing a request it thought had timed out all
                  // resolve to one order rather than three. ?>
            <input type="hidden" name="idempotency_key" value="<?= esc($idempotencyKey, 'attr') ?>">

            <?php // ── Who is paying ──────────────────────────────────────── ?>
            <section aria-labelledby="billing">
                <h2 id="billing" class="section-title"><?= esc(lang('Commerce.checkout.billing')) ?></h2>
                <p class="mt-2 text-sm text-white/60"><?= esc(lang('Commerce.checkout.billing_note')) ?></p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <label class="sm:col-span-1">
                        <span class="field-label"><?= esc(lang('Commerce.checkout.name')) ?> *</span>
                        <input x-ref="buyerName" id="billing_name" name="billing_name" type="text" required autocomplete="name"
                               class="field" value="<?= esc(old('billing_name', (string) ($user['name'] ?? ''), false), 'attr') ?>"
                               <?= $errorFor('billing_name') ? 'aria-invalid="true" aria-describedby="err-billing_name"' : '' ?>>
                        <?php if ($e = $errorFor('billing_name')): ?><span id="err-billing_name" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.email')) ?> *</span>
                        <input x-ref="buyerEmail" id="billing_email" name="billing_email" type="email" required autocomplete="email"
                               class="field" value="<?= esc(old('billing_email', (string) ($user['email'] ?? ''), false), 'attr') ?>"
                               <?= $errorFor('billing_email') ? 'aria-invalid="true" aria-describedby="err-billing_email"' : '' ?>>
                        <?php if ($e = $errorFor('billing_email')): ?><span id="err-billing_email" class="field-error"><?= esc($e) ?></span><?php endif; ?>
                        <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.checkout.email_note')) ?></span>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.phone')) ?></span>
                        <input id="billing_phone" name="billing_phone" type="tel" autocomplete="tel"
                               class="field" value="<?= esc(old('billing_phone', '', false), 'attr') ?>">
                        <?php if ($e = $errorFor('billing_phone')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.company')) ?></span>
                        <input id="billing_company" name="billing_company" type="text" autocomplete="organization"
                               class="field" value="<?= esc(old('billing_company', '', false), 'attr') ?>">
                    </label>

                    <label class="sm:col-span-2">
                        <span class="field-label"><?= esc(lang('Commerce.checkout.address')) ?></span>
                        <input id="billing_address" name="billing_address" type="text" autocomplete="street-address"
                               class="field" value="<?= esc(old('billing_address', '', false), 'attr') ?>">
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.city')) ?></span>
                        <input id="billing_city" name="billing_city" type="text" autocomplete="address-level2"
                               class="field" value="<?= esc(old('billing_city', '', false), 'attr') ?>">
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.postcode')) ?></span>
                        <input id="billing_postcode" name="billing_postcode" type="text" autocomplete="postal-code"
                               class="field" value="<?= esc(old('billing_postcode', '', false), 'attr') ?>">
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.country')) ?> *</span>
                        <select id="billing_country" name="billing_country" required autocomplete="country" class="field">
                            <?php foreach ($countries as $code => $name): ?>
                                <option value="<?= esc($code, 'attr') ?>" <?= $selectedCountry === $code ? 'selected' : '' ?>>
                                    <?= esc($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($e = $errorFor('billing_country')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>
                    </label>

                    <?php // Both optional and both asked for, because a company
                          // finance department will not pay an invoice that
                          // lacks its purchase-order number and will not
                          // reclaim tax against one with no registration
                          // number. Chasing them afterwards costs a fortnight. ?>
                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.tax_id')) ?></span>
                        <input id="billing_tax_id" name="billing_tax_id" type="text"
                               class="field" value="<?= esc(old('billing_tax_id', '', false), 'attr') ?>">
                    </label>

                    <label>
                        <span class="field-label"><?= esc(lang('Commerce.checkout.po_number')) ?></span>
                        <input id="billing_po_number" name="billing_po_number" type="text"
                               class="field" value="<?= esc(old('billing_po_number', '', false), 'attr') ?>">
                    </label>

                    <label class="sm:col-span-2">
                        <span class="field-label"><?= esc(lang('Commerce.checkout.notes')) ?></span>
                        <textarea id="billing_notes" name="billing_notes" rows="3" class="field"><?= esc(old('billing_notes', '', false)) ?></textarea>
                        <span class="mt-1.5 block text-xs text-white/50"><?= esc(lang('Commerce.checkout.notes_hint')) ?></span>
                    </label>
                </div>
            </section>

            <?php // ── Who is coming ──────────────────────────────────────── ?>
            <section aria-labelledby="attendees">
                <h2 id="attendees" class="section-title"><?= esc(lang('Commerce.checkout.attendees')) ?></h2>
                <p class="mt-2 max-w-2xl text-sm text-white/60"><?= esc(lang('Commerce.checkout.attendees_note')) ?></p>

                <div class="mt-6 space-y-6">
                    <?php $seatCounter = 0; ?>
                    <?php foreach ($totals['items'] as $item):
                        $itemId  = (int) $item['id'];
                        $seats   = (int) $item['qty'];
                        $isDated = $item['item_type'] === 'session';
                        $warning = $warnings[$itemId] ?? null;
                    ?>
                        <fieldset class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                            <legend class="px-2 text-sm font-semibold">
                                <?= esc(t_field($item['course_title'] ?? '')) ?>
                            </legend>

                            <p class="text-xs text-white/55">
                                <?php if ($isDated): ?>
                                    <?= esc(mode_label($item['session_mode'])) ?>
                                    · <?= esc(session_dates($item)) ?>
                                    <?php if ($times = session_times($item)): ?> · <?= esc($times) ?><?php endif; ?>
                                    <?php if (! empty($item['venue_city'])): ?> · <?= esc($item['venue_city']) ?><?php endif; ?>
                                <?php else: ?>
                                    <?= esc(lang('Commerce.checkout.programme')) ?>
                                <?php endif; ?>
                                · <?= esc(lang('Commerce.checkout.seats_count', [$seats])) ?>
                            </p>

                            <?php if ($warning !== null): ?>
                                <p role="alert" class="mt-3 rounded-lg border border-gold bg-gold/10 px-3 py-2 text-xs">
                                    <?= esc(lang('Commerce.checkout.seats_short', [(int) $warning['left'], (int) $warning['qty']])) ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-5 space-y-5">
                                <?php for ($seat = 0; $seat < $seats; $seat++):
                                    $base      = 'attendee.' . $itemId . '.' . $seat;
                                    $nameField = 'attendee[' . $itemId . '][' . $seat . '][name]';
                                    $mailField = 'attendee[' . $itemId . '][' . $seat . '][email]';
                                    $nameErr   = $errorFor($base . '.name');
                                    $mailErr   = $errorFor($base . '.email');
                                    $first     = $seatCounter === 0;
                                    $seatCounter++;
                                ?>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-white/50">
                                                <?= esc(lang('Commerce.checkout.attendee_n', [$seat + 1])) ?>
                                            </p>
                                            <?php if ($first): ?>
                                                <?php // Hidden until Alpine removes x-cloak, so a browser
                                                      // with no JavaScript is never offered a button that
                                                      // does nothing. Everything else on this form works
                                                      // without it. ?>
                                                <button type="button" x-cloak
                                                        @click="$refs.a0name.value = $refs.buyerName.value; $refs.a0email.value = $refs.buyerEmail.value"
                                                        class="text-xs font-medium text-brand-red underline decoration-line underline-offset-4">
                                                    <?= esc(lang('Commerce.checkout.attendee_is_me')) ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <label>
                                            <span class="field-label"><?= esc(lang('Commerce.checkout.attendee_name')) ?> *</span>
                                            <input type="text" required class="field" maxlength="128"
                                                   <?= $first ? 'x-ref="a0name"' : '' ?>
                                                   name="<?= esc($nameField, 'attr') ?>"
                                                   value="<?= esc(old($base . '.name', '', false), 'attr') ?>"
                                                   <?= $nameErr ? 'aria-invalid="true"' : '' ?>>
                                            <?php if ($nameErr): ?><span class="field-error"><?= esc($nameErr) ?></span><?php endif; ?>
                                        </label>

                                        <label>
                                            <span class="field-label"><?= esc(lang('Commerce.checkout.attendee_email')) ?> *</span>
                                            <input type="email" required class="field" maxlength="191"
                                                   <?= $first ? 'x-ref="a0email"' : '' ?>
                                                   name="<?= esc($mailField, 'attr') ?>"
                                                   value="<?= esc(old($base . '.email', '', false), 'attr') ?>"
                                                   <?= $mailErr ? 'aria-invalid="true"' : '' ?>>
                                            <?php if ($mailErr): ?><span class="field-error"><?= esc($mailErr) ?></span><?php endif; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php // ── How to pay ─────────────────────────────────────────── ?>
            <section aria-labelledby="payment">
                <h2 id="payment" class="section-title"><?= esc(lang('Commerce.checkout.payment')) ?></h2>

                <?php if ($gateways === []): ?>
                    <?php // No configured gateway takes this currency. Said
                          // plainly, with a way through, rather than showing a
                          // button that fails after the buyer has committed —
                          // which is exactly what GatewayRegistry refuses to
                          // let happen. ?>
                    <div class="mt-5 rounded-2xl border border-line bg-surface p-5">
                        <p class="text-white/75"><?= esc(lang('Commerce.checkout.no_gateway')) ?></p>
                        <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-ghost mt-4">
                            <?= esc(lang('Commerce.checkout.ask_for_invoice')) ?>
                        </a>
                    </div>
                <?php else: ?>
                    <?php if ($e = $errorFor('gateway')): ?>
                        <p class="field-error mt-2"><?= esc($e) ?></p>
                    <?php endif; ?>

                    <fieldset class="mt-5 space-y-3">
                        <legend class="sr-only"><?= esc(lang('Commerce.checkout.payment')) ?></legend>
                        <?php foreach ($gateways as $gateway): $key = $gateway->key(); ?>
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-surface p-4 transition hover:border-brand-red">
                                <input type="radio" name="gateway" value="<?= esc($key, 'attr') ?>" required
                                       <?= $selectedGateway === $key ? 'checked' : '' ?>
                                       class="mt-1 h-4 w-4 border-line text-brand-red focus-visible:ring-2 focus-visible:ring-brand-red">
                                <span class="min-w-0">
                                    <span class="block font-semibold"><?= esc($gateway->label()) ?></span>
                                    <span class="mt-1 block text-sm text-white/60"><?= esc(lang('Commerce.gateway.desc_' . $key)) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>
            </section>

            <?php // ── What is being agreed to ────────────────────────────── ?>
            <section aria-labelledby="terms-heading">
                <h2 id="terms-heading" class="sr-only"><?= esc(lang('Commerce.checkout.terms_label')) ?></h2>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="terms" value="1" required <?= old('terms', '', false) ? 'checked' : '' ?>
                           class="mt-0.5 h-4 w-4 rounded border-line text-brand-red focus-visible:ring-2 focus-visible:ring-brand-red"
                           <?= $errorFor('terms') ? 'aria-invalid="true"' : '' ?>>
                    <?php
                        // Composed from one translatable sentence with the two
                        // links as placeholders, so a translator can put them
                        // where their grammar needs them rather than receiving
                        // five fragments to reassemble.
                        $link = static fn (string $path, string $label): string => '<a href="' . esc(locale_url($path), 'attr')
                            . '" class="font-medium text-brand-red underline decoration-line underline-offset-4">' . esc($label) . '</a>';
                    ?>
                    <span class="text-white/75">
                        <?= lang('Commerce.checkout.terms_html', [
                            $link('terms', lang('Commerce.checkout.terms_link')),
                            $link('privacy', lang('Commerce.checkout.privacy_link')),
                        ]) ?>
                    </span>
                </label>
                <?php if ($e = $errorFor('terms')): ?><span class="field-error"><?= esc($e) ?></span><?php endif; ?>

                <div class="mt-8 flex flex-wrap items-center gap-5">
                    <button type="submit" class="btn-brand disabled:cursor-not-allowed disabled:opacity-50" <?= $gateways === [] ? 'disabled' : '' ?>>
                        <?= esc(lang('Commerce.checkout.place', [money($totals['total_cents'], $currency)])) ?>
                    </button>
                    <a href="<?= esc(locale_url('cart')) ?>" class="text-sm text-white/60 underline decoration-line underline-offset-4 hover:text-brand-red">
                        <?= esc(lang('Commerce.checkout.back_to_cart')) ?>
                    </a>
                </div>

                <?php // Said at the button, because this is the moment the
                      // question is asked, and because a payment page that
                      // implies the booking is confirmed the instant a card
                      // clears is a payment page that has to apologise later. ?>
                <p class="mt-4 max-w-2xl text-xs text-white/50"><?= esc(lang('Commerce.checkout.after_note')) ?></p>
            </section>
        </form>
    </div>

    <?php // ── The summary ────────────────────────────────────────────────── ?>
    <?php // Outside the form above, deliberately: the coupon box is a form of
          // its own and HTML has no way of nesting one form inside another. ?>
    <aside class="lg:sticky lg:top-28 lg:self-start" aria-labelledby="summary">
        <div class="rounded-3xl border border-line bg-surface p-6">
            <h2 id="summary" class="text-lg font-bold"><?= esc(lang('Commerce.checkout.summary')) ?></h2>

            <ul class="mt-5 space-y-4">
                <?php foreach ($totals['items'] as $item): ?>
                    <li class="flex gap-3 border-b border-line pb-4 last:border-0 last:pb-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium leading-snug"><?= esc(t_field($item['course_title'] ?? '')) ?></p>
                            <p class="mt-1 text-xs text-white/55">
                                <?php if ($item['item_type'] === 'session'): ?>
                                    <?= esc(session_dates($item)) ?> · <?= esc(mode_label($item['session_mode'])) ?>
                                <?php else: ?>
                                    <?= esc(lang('Commerce.checkout.programme')) ?>
                                <?php endif; ?>
                                · <?= esc(lang('Commerce.checkout.seats_count', [(int) $item['qty']])) ?>
                            </p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold tabular-nums">
                            <?= esc(money((int) $item['unit_price_cents'] * (int) $item['qty'], $currency)) ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php // ── Coupon ─────────────────────────────────────────────── ?>
            <form method="post" action="<?= esc(locale_url('cart/coupon')) ?>" class="mt-6 border-t border-line pt-5">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="checkout">
                <label class="field-label" for="coupon"><?= esc(lang('Commerce.checkout.coupon')) ?></label>
                <div class="flex gap-2">
                    <input id="coupon" name="code" type="text" class="field flex-1" autocomplete="off"
                           placeholder="<?= esc(lang('Commerce.checkout.coupon_placeholder'), 'attr') ?>">
                    <button type="submit" class="btn-ghost shrink-0"><?= esc(lang('Commerce.checkout.coupon_apply')) ?></button>
                </div>
            </form>

            <?php // ── The money ──────────────────────────────────────────── ?>
            <dl class="mt-6 space-y-2.5 border-t border-line pt-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-white/60"><?= esc(lang('Commerce.checkout.subtotal')) ?></dt>
                    <dd class="tabular-nums"><?= esc(money($totals['subtotal_cents'], $currency)) ?></dd>
                </div>

                <?php if ($totals['discount_cents'] > 0): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-white/60"><?= esc(lang('Commerce.checkout.discount')) ?></dt>
                        <dd class="tabular-nums text-gold">−<?= esc(money($totals['discount_cents'], $currency)) ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ($totals['coupon_cents'] > 0): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-white/60"><?= esc(lang('Commerce.checkout.coupon_saving')) ?></dt>
                        <dd class="tabular-nums text-gold">−<?= esc(money($totals['coupon_cents'], $currency)) ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ($totals['tax_cents'] > 0): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-white/60"><?= esc($totals['tax_label'] ?: lang('Commerce.checkout.tax')) ?></dt>
                        <dd class="tabular-nums"><?= esc(money($totals['tax_cents'], $currency)) ?></dd>
                    </div>
                <?php endif; ?>

                <div class="flex justify-between gap-4 border-t border-line pt-3 text-base font-bold">
                    <dt><?= esc(lang('Commerce.checkout.total')) ?></dt>
                    <dd class="tabular-nums"><?= esc(money($totals['total_cents'], $currency)) ?></dd>
                </div>
            </dl>

            <p class="mt-3 text-xs text-white/50"><?= esc(lang('Commerce.checkout.currency_note', [$currency])) ?></p>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
