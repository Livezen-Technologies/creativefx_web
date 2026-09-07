<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The basket.
 *
 * A basket page is not a receipt: it is the last place a buyer checks that the
 * site understood them. So every line repeats, in full, what was actually
 * chosen — the dates, the mode, the room, the price for one seat — rather than
 * a course title and a number. Nearly all the abandonment on a training site
 * happens here, and most of it is somebody who could not tell whether they had
 * booked the Tuesday or the Thursday.
 *
 * Three things on the page are there because of how seats work:
 *
 *   The hold expiry is stated, with a clock, because a fifteen-minute hold that
 *   nobody mentions is a fifteen-minute hold that expires while somebody is
 *   fetching their card, and then the failure at checkout looks like a bug.
 *
 *   A line whose class has since filled, closed or been cancelled says so, in
 *   its own words, next to the line. `CheckoutService::place()` re-checks every
 *   seat under a lock and would refuse the basket; discovering that after four
 *   attendee names have been typed in is the worst possible moment for it.
 *
 *   The empty state is a way back into the catalogue. "Your cart is empty" is a
 *   dead end shown to somebody who was, a moment ago, trying to spend money.
 *
 * @var array|null  $cart
 * @var list<array> $lines   cart lines, decorated with warning / seats_left / held_until
 * @var array|null  $totals  from CheckoutService::totals()
 * @var array|null  $coupon  the applied coupon row, if any
 * @var bool        $stale   there was a cart cookie, but the cart has expired
 * @var bool        $blocked at least one line would be refused at checkout
 * @var string|null $holdUntilLabel  earliest hold expiry, in the school's clock
 * @var int         $maxQty  the seats-per-line ceiling the controller clamps to
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$currency = (string) ($cart['currency'] ?? current_currency());
$notice   = session()->getFlashdata('notice');
$error    = session()->getFlashdata('error');

/** One sentence per refusal. See Cart::decorate() for which is chosen when. */
$warningText = static function (array $line): string {
    return match ($line['warning']) {
        'missing'   => lang('Commerce.cart.warn.missing'),
        'cancelled' => lang('Commerce.cart.warn.cancelled'),
        'closed'    => lang('Commerce.cart.warn.closed'),
        'gone'      => lang('Commerce.cart.warn.gone'),
        'short'     => lang('Commerce.cart.warn.short', [(int) $line['seats_left'], (int) $line['qty']]),
        'lapsed'    => lang('Commerce.cart.warn.lapsed'),
        default     => '',
    };
};
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Commerce.cart.eyebrow'),
    'heading' => lang('Commerce.cart.heading'),
    'intro'   => $lines === [] ? lang('Commerce.cart.intro_empty') : lang('Commerce.cart.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x py-12 lg:py-14">

    <?php // Flash messages. role="alert" on the failure because it is the
          // answer to something the visitor just did and has to be announced;
          // role="status" on the confirmation, which is polite. ?>
    <?php if ($error): ?>
        <p role="alert" class="mb-8 rounded-2xl border border-brand-red/40 bg-brand-red/10 px-5 py-4 text-sm leading-relaxed text-white/85">
            <?= esc($error) ?>
        </p>
    <?php endif; ?>
    <?php if ($notice): ?>
        <p role="status" class="mb-8 rounded-2xl border border-line bg-surface px-5 py-4 text-sm text-white/80">
            <?= esc($notice) ?>
        </p>
    <?php endif; ?>

    <?php if ($lines === []): ?>

        <?php // ── The empty basket ───────────────────────────────────────────
              // Four real routes back into the catalogue, because somebody who
              // reached this page wanted to buy something and the useful reply
              // is where to find it, not a shrug. ?>
        <div class="mx-auto max-w-2xl rounded-3xl border border-line bg-surface p-7 text-center sm:p-10">
            <h2 class="text-xl font-bold sm:text-2xl">
                <?= esc($stale ? lang('Commerce.cart.empty.expired_heading') : lang('Commerce.cart.empty.heading')) ?>
            </h2>
            <p class="mx-auto mt-3 max-w-lg leading-relaxed text-white/70">
                <?= esc($stale ? lang('Commerce.cart.empty.expired_body') : lang('Commerce.cart.empty.body')) ?>
            </p>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand"><?= esc(lang('Commerce.cart.empty.courses')) ?></a>
                <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Commerce.cart.empty.schedule')) ?></a>
                <a href="<?= esc(locale_url('certificates')) ?>" class="btn-ghost"><?= esc(lang('Commerce.cart.empty.certificates')) ?></a>
                <a href="<?= esc(locale_url('on-demand')) ?>" class="btn-ghost"><?= esc(lang('Commerce.cart.empty.on_demand')) ?></a>
            </div>
        </div>

    <?php else: ?>

        <?php // ── The hold ───────────────────────────────────────────────────
              // The earliest expiry across the basket, since that is the one
              // that bites first. Stated before the lines, because it is the
              // reason to read them now rather than later. ?>
        <?php if ($holdUntilLabel !== null): ?>
            <p class="mb-8 flex items-start gap-3 rounded-2xl border border-gold/40 bg-gold/10 px-5 py-4 text-sm leading-relaxed text-white/80">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M10 5.8V10l2.8 1.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span><?= esc(lang('Commerce.cart.hold_until', [$holdUntilLabel])) ?></span>
            </p>
        <?php endif; ?>

        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">

            <?php // ── The lines ──────────────────────────────────────────── ?>
            <div class="min-w-0 space-y-5">
                <?php foreach ($lines as $line):
                    $isSession = $line['item_type'] === 'session';
                    // A line whose course row has gone still has to be
                    // nameable: it is the thing the buyer is being asked to
                    // remove, and "Remove" on its own names nothing.
                    $title     = t_field($line['course_title'] ?? '') ?: lang('Commerce.cart.untitled');
                    $qty       = (int) $line['qty'];

                    // A session's own page is addressed by the session id, not
                    // by the cart line's id — `ci.*` puts the cart line's own id
                    // in `id`, which is exactly the wrong number to link with.
                    $url = null;
                    if (! empty($line['course_slug'])) {
                        $url = $isSession
                            ? session_url(['id' => (int) $line['item_id'], 'course_slug' => $line['course_slug']])
                            : locale_url((($line['bundle_type'] ?? '') === 'bootcamp' ? 'bootcamps/' : 'certificates/') . $line['course_slug']);
                    }

                    $meta    = json_decode((string) ($line['meta_json'] ?? ''), true) ?: [];
                    $compare = isset($meta['compare_at_cents']) ? (int) $meta['compare_at_cents'] : null;
                    $warning = $warningText($line);
                ?>
                    <article class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="line-<?= (int) $line['id'] ?>">
                        <div class="flex flex-col gap-5 sm:flex-row">

                            <?php if (! empty($line['hero_image'])): ?>
                                <?php // Decorative: the title is right beside it,
                                      // so an alt text here would only be read
                                      // out twice. ?>
                                <img src="<?= esc(media_src($line['hero_image'])) ?>" alt=""
                                     loading="lazy" width="160" height="96"
                                     class="h-24 w-full shrink-0 rounded-xl border border-line object-cover sm:w-40">
                            <?php endif; ?>

                            <div class="min-w-0 flex-1">
                                <h2 id="line-<?= (int) $line['id'] ?>" class="text-base font-semibold leading-snug sm:text-lg">
                                    <?php if ($url !== null): ?>
                                        <a href="<?= esc($url) ?>" class="underline decoration-line underline-offset-4 transition hover:text-brand-red">
                                            <?= esc($title) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= esc($title) ?>
                                    <?php endif; ?>
                                </h2>

                                <?php // What was actually chosen. A basket that
                                      // shows only a course name cannot answer
                                      // "did I book the Tuesday or the
                                      // Thursday?", which is the one question
                                      // this page exists to answer. ?>
                                <?php // A withdrawn session has nothing left to
                                      // describe — the join comes back all
                                      // nulls — and printing "Online" for a
                                      // class that no longer exists is worse
                                      // than printing nothing at all. ?>
                                <?php if ($line['warning'] !== 'missing'): ?>
                                <ul class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-white/60">
                                    <?php if ($isSession): ?>
                                        <li><?= esc(session_dates($line)) ?></li>
                                        <?php if ($times = session_times($line)): ?>
                                            <li><?= esc($times) ?></li>
                                        <?php endif; ?>
                                        <?php if ($mode = mode_label($line['session_mode'] ?? null)): ?>
                                            <li><?= esc($mode) ?></li>
                                        <?php endif; ?>
                                        <li>
                                            <?= esc($line['venue_name']
                                                ? trim($line['venue_name'] . ($line['venue_city'] ? ', ' . $line['venue_city'] : ''))
                                                : lang('Commerce.cart.online')) ?>
                                        </li>
                                    <?php else: ?>
                                        <?php // A bundle takes no seats: the
                                              // learner picks their dates from
                                              // the account area afterwards,
                                              // which is the whole point of
                                              // buying a programme rather than
                                              // four separate classes. ?>
                                        <li><?= esc(lang('Commerce.cart.bundle_dates')) ?></li>
                                    <?php endif; ?>
                                </ul>
                                <?php endif; ?>

                                <?php if ($isSession && $line['held_until_label'] !== null): ?>
                                    <p class="mt-2 text-xs text-white/45">
                                        <?= esc(lang('Commerce.cart.line_held')) ?>
                                        <time datetime="<?= esc((string) $line['held_until_iso'], 'attr') ?>" class="tabular-nums"><?= esc($line['held_until_label']) ?></time>
                                    </p>
                                <?php endif; ?>

                                <?php if ($warning !== ''): ?>
                                    <p class="mt-3 rounded-xl border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm leading-relaxed text-white/80">
                                        <?= esc($warning) ?>
                                    </p>
                                <?php endif; ?>

                                <?php // ── Seats, and getting rid of the line ── ?>
                                <div class="mt-4 flex flex-wrap items-end gap-x-3 gap-y-2">
                                    <form method="post" action="<?= esc(locale_url('cart/update')) ?>" class="flex items-end gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item" value="<?= (int) $line['id'] ?>">
                                        <div>
                                            <label class="field-label text-xs" for="qty-<?= (int) $line['id'] ?>">
                                                <?= esc(lang('Commerce.cart.seats')) ?>
                                            </label>
                                            <?php // aria-describedby, so the
                                                  // course this box belongs to
                                                  // is announced with it. Every
                                                  // line's label says "Seats",
                                                  // and a form-controls list
                                                  // otherwise reads as four
                                                  // identical boxes. ?>
                                            <input id="qty-<?= (int) $line['id'] ?>" name="qty" type="number"
                                                   min="0" max="<?= (int) $maxQty ?>" step="1" value="<?= $qty ?>"
                                                   inputmode="numeric" aria-describedby="line-<?= (int) $line['id'] ?>"
                                                   class="field w-20 text-center">
                                        </div>
                                        <button type="submit" class="btn-ghost px-4 py-2 text-xs"
                                                aria-label="<?= esc(lang('Commerce.cart.update_named', [$title]), 'attr') ?>">
                                            <?= esc(lang('Commerce.cart.update')) ?>
                                        </button>
                                    </form>

                                    <?php // A separate form, because a form
                                          // inside a form is not valid HTML and
                                          // the browser silently drops one of
                                          // them — usually the remove button. ?>
                                    <form method="post" action="<?= esc(locale_url('cart/remove')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item" value="<?= (int) $line['id'] ?>">
                                        <button type="submit" class="pb-2 text-xs text-white/50 underline decoration-line underline-offset-4 transition hover:text-brand-red"
                                                aria-label="<?= esc(lang('Commerce.cart.remove_named', [$title]), 'attr') ?>">
                                            <?= esc(lang('Commerce.cart.remove')) ?>
                                        </button>
                                    </form>

                                    <?php if ($line['warning'] === 'short'): ?>
                                        <?php // One click to take what is left,
                                              // rather than leaving somebody to
                                              // work out the number themselves
                                              // from the sentence above. ?>
                                        <form method="post" action="<?= esc(locale_url('cart/update')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="item" value="<?= (int) $line['id'] ?>">
                                            <input type="hidden" name="qty" value="<?= (int) $line['seats_left'] ?>">
                                            <button type="submit" class="pb-2 text-xs font-medium text-brand-red underline decoration-line underline-offset-4">
                                                <?= esc(lang('Commerce.cart.take_remaining', [(int) $line['seats_left']])) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php // ── The money for this line ─────────────── ?>
                            <div class="shrink-0 text-left sm:w-40 sm:text-right">
                                <p class="text-lg font-bold tabular-nums"><?= esc(money((int) $line['line_total_cents'], $currency)) ?></p>
                                <p class="mt-1 text-xs text-white/50">
                                    <?= esc(lang('Commerce.cart.per_seat', [money((int) $line['unit_price_cents'], $currency)])) ?>
                                </p>
                                <?php if ($compare !== null && $compare > (int) $line['unit_price_cents']): ?>
                                    <p class="mt-1 text-xs text-white/40 line-through tabular-nums"><?= esc(money($compare, $currency)) ?></p>
                                <?php endif; ?>
                                <?php if ((int) $line['discount_cents'] > 0): ?>
                                    <p class="mt-1 text-xs font-medium text-gold tabular-nums">
                                        <?= esc(lang('Commerce.cart.line_discount', [money((int) $line['discount_cents'], $currency)])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <a href="<?= esc(locale_url('courses')) ?>" class="inline-block text-sm text-white/55 underline decoration-line underline-offset-4 transition hover:text-brand-red">
                    <?= esc(lang('Commerce.cart.keep_browsing')) ?>
                </a>
            </div>

            <?php // ── The summary ────────────────────────────────────────── ?>
            <aside class="lg:sticky lg:top-28 lg:self-start" aria-labelledby="summary-title">
                <div class="rounded-3xl border border-line bg-surface p-6">
                    <h2 id="summary-title" class="text-lg font-bold"><?= esc(lang('Commerce.cart.summary')) ?></h2>

                    <?php // ── Coupon ─────────────────────────────────────── ?>
                    <?php if ($coupon !== null): ?>
                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line px-4 py-3">
                            <p class="text-sm">
                                <span class="font-semibold"><?= esc($coupon['code']) ?></span>
                                <?php // A stored coupon is re-validated on every
                                      // render, so one that has since expired or
                                      // run out of uses is worth nothing today.
                                      // Saying so beats a code sitting on the
                                      // page next to a total it did not change. ?>
                                <?php if ((int) $totals['coupon_cents'] === 0): ?>
                                    <span class="block text-xs text-white/55"><?= esc(lang('Commerce.cart.coupon.no_longer')) ?></span>
                                <?php endif; ?>
                            </p>
                            <form method="post" action="<?= esc(locale_url('cart/coupon')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="text-xs text-white/50 underline decoration-line underline-offset-4 transition hover:text-brand-red">
                                    <?= esc(lang('Commerce.cart.coupon.remove')) ?>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="post" action="<?= esc(locale_url('cart/coupon')) ?>" class="mt-5">
                            <?= csrf_field() ?>
                            <label class="field-label" for="coupon-code"><?= esc(lang('Commerce.cart.coupon.label')) ?></label>
                            <div class="mt-1 flex gap-2">
                                <input id="coupon-code" name="code" type="text" autocomplete="off" spellcheck="false"
                                       maxlength="64" class="field min-w-0 flex-1 uppercase"
                                       placeholder="<?= esc(lang('Commerce.cart.coupon.placeholder'), 'attr') ?>">
                                <button type="submit" class="btn-ghost shrink-0 px-4"><?= esc(lang('Commerce.cart.coupon.apply')) ?></button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php // ── Totals ─────────────────────────────────────── ?>
                    <dl class="mt-6 space-y-2 border-t border-line pt-5 text-sm">
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="text-white/60"><?= esc(lang('Commerce.cart.subtotal', [(int) $totals['count']])) ?></dt>
                            <dd class="tabular-nums"><?= esc(money((int) $totals['subtotal_cents'], $currency)) ?></dd>
                        </div>

                        <?php if ((int) $totals['discount_cents'] > 0): ?>
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-white/60"><?= esc(lang('Commerce.cart.discount')) ?></dt>
                                <dd class="tabular-nums text-gold">−<?= esc(money((int) $totals['discount_cents'], $currency)) ?></dd>
                            </div>
                        <?php endif; ?>

                        <?php if ((int) $totals['coupon_cents'] > 0): ?>
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-white/60"><?= esc(lang('Commerce.cart.coupon.line')) ?></dt>
                                <dd class="tabular-nums text-gold">−<?= esc(money((int) $totals['coupon_cents'], $currency)) ?></dd>
                            </div>
                        <?php endif; ?>

                        <?php // Shown only when there is tax to show, and under
                              // the label the rule carries, because "VAT" and
                              // "GST" are not interchangeable on an invoice. ?>
                        <?php if ((int) $totals['tax_cents'] > 0): ?>
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-white/60"><?= esc($totals['tax_label'] ?: lang('Commerce.cart.tax')) ?></dt>
                                <dd class="tabular-nums"><?= esc(money((int) $totals['tax_cents'], $currency)) ?></dd>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-baseline justify-between gap-4 border-t border-line pt-3 text-base font-bold">
                            <dt><?= esc(lang('Commerce.cart.total')) ?></dt>
                            <dd class="tabular-nums"><?= esc(money((int) $totals['total_cents'], $currency)) ?></dd>
                        </div>
                    </dl>

                    <?php // ── On to checkout ─────────────────────────────── ?>
                    <?php if ($blocked): ?>
                        <?php // Checkout re-checks every seat under a lock and
                              // would refuse this basket. Sending somebody into
                              // it anyway means they type four attendee names
                              // before finding out. ?>
                        <p class="mt-6 rounded-xl border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm leading-relaxed text-white/80">
                            <?= esc(lang('Commerce.cart.blocked')) ?>
                        </p>
                    <?php else: ?>
                        <a href="<?= esc(locale_url('checkout')) ?>" class="btn-brand mt-6 w-full"><?= esc(lang('Commerce.cart.checkout')) ?></a>
                    <?php endif; ?>

                    <?php // True, and worth saying at the button: a seat becomes
                          // an enrolment when the payment is confirmed by the
                          // gateway, not when the browser comes back from it. ?>
                    <p class="mt-3 text-xs leading-relaxed text-white/50"><?= esc(lang('Commerce.cart.checkout_note')) ?></p>

                    <?php if (strtoupper($currency) !== strtoupper(current_currency())): ?>
                        <?php // The currency is frozen onto the cart at the
                              // moment it is created and prices are published
                              // per currency rather than converted, so a basket
                              // and a page can honestly disagree. Switching is
                              // offered rather than done: it re-reads every line
                              // at the other currency's published price and
                              // drops anything that has none. ?>
                        <p class="mt-4 border-t border-line pt-4 text-xs leading-relaxed text-white/55">
                            <?= esc(lang('Commerce.cart.currency_note', [$currency])) ?>
                            <a href="<?= esc(locale_url('currency/' . current_currency())) ?>"
                               class="font-medium text-brand-red underline decoration-line underline-offset-4">
                                <?= esc(lang('Commerce.cart.currency_switch', [current_currency()])) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
