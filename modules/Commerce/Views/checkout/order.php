<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One order, exactly as the database currently holds it.
 *
 * This is both the page a buyer lands on when a gateway sends them back and the
 * page they reach later from a link, and it is deliberately the same page,
 * because the return from a gateway is not a fact about payment. It can be
 * replayed, typed into an address bar, or never arrive at all. An order becomes
 * paid in `EnrolmentService::fulfil()`, called by a verified webhook or by an
 * administrator — never by a browser arriving here.
 *
 * So the page has two honest states and refuses to blur them:
 *
 *   **paid** — the webhook has landed. The seats are sold, the enrolments
 *   exist, and each attendee is being written to at their own address.
 *
 *   **waiting** — the money may well have left the buyer's account, and we do
 *   not know it yet. The page says exactly that, says the seats are being held,
 *   and does not congratulate anybody. A confirmation shown before confirmation
 *   is a promise the school may have to withdraw, in front of somebody who has
 *   just paid for it.
 *
 * @var array  $order
 * @var list<array> $items
 * @var array  $billing
 * @var ?array $invoice
 * @var bool   $justReturned  true when arriving back from a gateway
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$currency = (string) $order['currency'];
$status   = (string) $order['status'];
$paid     = ! empty($order['paid_at']) && in_array($status, ['paid', 'partially_refunded'], true);
$waiting  = $status === 'pending_payment';
$isBank   = ($order['gateway'] ?? '') === 'bank';

// The heading follows what actually happened, not what the buyer was doing a
// moment ago: somebody returning from a gateway to an unconfirmed order should
// not be greeted with "thank you for your booking".
$heading = $paid
    ? ($justReturned ? lang('Commerce.order.thanks') : lang('Commerce.order.title', [$order['order_no']]))
    : lang('Commerce.order.title', [$order['order_no']]);
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Commerce.order.eyebrow'),
    'heading' => $heading,
    'intro'   => $paid ? lang('Commerce.order.paid_intro') : '',
], ['saveData' => false]) ?>

<div class="container-x grid gap-12 py-14 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-14">
    <div class="min-w-0 space-y-10">

        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <?php // ── Where this order stands ────────────────────────────────── ?>
        <section aria-labelledby="status" class="rounded-3xl border border-line bg-surface p-7">
            <h2 id="status" class="text-xl font-bold">
                <?php if ($paid): ?>
                    <?= esc(lang('Commerce.order.state_paid')) ?>
                <?php elseif ($waiting && $isBank): ?>
                    <?= esc(lang('Commerce.order.state_awaiting_transfer')) ?>
                <?php elseif ($waiting): ?>
                    <?= esc(lang('Commerce.order.state_awaiting_payment')) ?>
                <?php elseif ($status === 'refunded'): ?>
                    <?= esc(lang('Commerce.order.state_refunded')) ?>
                <?php elseif ($status === 'cancelled'): ?>
                    <?= esc(lang('Commerce.order.state_cancelled')) ?>
                <?php else: ?>
                    <?= esc(lang('Commerce.order.state_failed')) ?>
                <?php endif; ?>
            </h2>

            <p class="mt-3 max-w-2xl text-white/75">
                <?php if ($paid): ?>
                    <?= esc(lang('Commerce.order.paid_body')) ?>
                <?php elseif ($waiting && $isBank): ?>
                    <?= esc(lang('Commerce.order.awaiting_transfer_body')) ?>
                <?php elseif ($waiting): ?>
                    <?php // The sentence that matters on this whole page. The
                          // browser coming back from a gateway is not evidence,
                          // so this says what is true — we are waiting, and the
                          // seats are not going anywhere while we do. ?>
                    <?= esc(lang('Commerce.order.awaiting_payment_body')) ?>
                <?php else: ?>
                    <?= esc(lang('Commerce.order.closed_body')) ?>
                <?php endif; ?>
            </p>

            <?php if ($waiting && ! empty($order['due_at'])): ?>
                <p class="mt-4 text-sm">
                    <span class="text-white/55"><?= esc(lang('Commerce.order.pay_by')) ?>:</span>
                    <span class="font-semibold"><?= esc(date('j M Y', strtotime((string) $order['due_at']))) ?></span>
                </p>
            <?php endif; ?>

            <div class="mt-6 flex flex-wrap gap-4">
                <?php if ($waiting): ?>
                    <a href="<?= esc(locale_url('checkout/pay/' . $order['order_no'])) ?>" class="btn-brand">
                        <?= esc($isBank ? lang('Commerce.order.transfer_details') : lang('Commerce.order.pay_now')) ?>
                    </a>
                <?php endif; ?>
                <a href="<?= esc(locale_url('order/' . $order['order_no'] . '/invoice')) ?>" class="btn-ghost">
                    <?= esc($invoice !== null ? lang('Commerce.order.view_invoice') : lang('Commerce.order.view_proforma')) ?>
                </a>
                <?php if ($paid): ?>
                    <a href="<?= esc(locale_url('account/courses')) ?>" class="btn-ghost">
                        <?= esc(lang('Commerce.order.go_to_account')) ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($paid): ?>
                <p class="mt-5 text-sm text-white/55"><?= esc(lang('Commerce.order.account_note')) ?></p>
            <?php endif; ?>
        </section>

        <?php // ── What was bought, and who is coming ──────────────────────── ?>
        <section aria-labelledby="lines">
            <h2 id="lines" class="section-title"><?= esc(lang('Commerce.order.what_you_booked')) ?></h2>

            <div class="mt-5 space-y-5">
                <?php foreach ($items as $item):
                    $meta      = json_decode((string) $item['meta_snapshot_json'], true) ?: [];
                    $attendees = json_decode((string) $item['attendee_json'], true) ?: [];
                ?>
                    <article class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                        <div class="flex flex-wrap items-baseline justify-between gap-3">
                            <?php // The title as it was at the moment of sale.
                                  // A course renamed next year must not rewrite
                                  // the history of somebody's receipt. ?>
                            <h3 class="font-semibold"><?= esc($item['title_snapshot']) ?></h3>
                            <p class="shrink-0 font-semibold tabular-nums"><?= esc(money((int) $item['total_cents'], $currency)) ?></p>
                        </div>

                        <p class="mt-1 text-sm text-white/55">
                            <?php if (! empty($meta['mode'])): ?><?= esc(mode_label($meta['mode'])) ?><?php endif; ?>
                            <?php if (! empty($meta['start_date'])): ?>
                                · <?= esc(session_dates(['start_date' => $meta['start_date'], 'end_date' => $meta['end_date'] ?? null])) ?>
                            <?php endif; ?>
                            <?php if (! empty($meta['venue'])): ?> · <?= esc($meta['venue']) ?><?php endif; ?>
                            <?php if (! empty($meta['city'])): ?> · <?= esc($meta['city']) ?><?php endif; ?>
                            · <?= esc(lang('Commerce.checkout.seats_count', [(int) $item['qty']])) ?>
                        </p>

                        <?php if ($attendees !== []): ?>
                            <ul class="mt-4 divide-y divide-line border-t border-line pt-1">
                                <?php foreach ($attendees as $attendee): ?>
                                    <li class="flex flex-wrap items-baseline justify-between gap-x-4 py-2 text-sm">
                                        <span class="font-medium"><?= esc((string) ($attendee['name'] ?? '')) ?></span>
                                        <span class="text-white/55"><?= esc((string) ($attendee['email'] ?? '')) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($paid): ?>
                                <p class="mt-3 text-xs text-white/50"><?= esc(lang('Commerce.order.attendees_notified')) ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php // ── Who it was billed to ────────────────────────────────────── ?>
        <section aria-labelledby="billed-to">
            <h2 id="billed-to" class="section-title"><?= esc(lang('Commerce.order.billed_to')) ?></h2>
            <?php // Named fields only. billing_json also carries the hashed cart
                  // token that lets a guest reopen this order, and looping over
                  // the array would put it on the page. ?>
            <address class="mt-4 not-italic text-sm leading-relaxed text-white/75">
                <?php foreach (['name', 'company', 'address', 'city', 'postcode', 'country', 'email', 'phone'] as $field): ?>
                    <?php if (! empty($billing[$field])): ?>
                        <span class="block"><?= esc((string) $billing[$field]) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </address>
        </section>
    </div>

    <?php // ── The money ──────────────────────────────────────────────────── ?>
    <aside class="lg:sticky lg:top-28 lg:self-start" aria-labelledby="order-total">
        <div class="rounded-3xl border border-line bg-surface p-6">
            <h2 id="order-total" class="text-lg font-bold"><?= esc(lang('Commerce.order.summary')) ?></h2>

            <dl class="mt-5 space-y-2.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-white/60"><?= esc(lang('Commerce.order.reference')) ?></dt>
                    <dd class="font-mono font-semibold"><?= esc($order['order_no']) ?></dd>
                </div>
                <?php if ($invoice !== null): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-white/60"><?= esc(lang('Commerce.invoice.number')) ?></dt>
                        <dd class="font-mono font-semibold"><?= esc($invoice['number']) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (! empty($order['placed_at'])): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-white/60"><?= esc(lang('Commerce.order.placed')) ?></dt>
                        <dd><?= esc(date('j M Y', strtotime((string) $order['placed_at']))) ?></dd>
                    </div>
                <?php endif; ?>

                <div class="flex justify-between gap-4 border-t border-line pt-3">
                    <dt class="text-white/60"><?= esc(lang('Commerce.checkout.subtotal')) ?></dt>
                    <dd class="tabular-nums"><?= esc(money((int) $order['subtotal_cents'], $currency)) ?></dd>
                </div>
                <?php if ((int) $order['discount_cents'] > 0): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-white/60"><?= esc(lang('Commerce.checkout.discount')) ?></dt>
                        <dd class="tabular-nums text-gold">−<?= esc(money((int) $order['discount_cents'], $currency)) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if ((int) $order['tax_cents'] > 0): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-white/60"><?= esc(lang('Commerce.checkout.tax')) ?></dt>
                        <dd class="tabular-nums"><?= esc(money((int) $order['tax_cents'], $currency)) ?></dd>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between gap-4 border-t border-line pt-3 text-base font-bold">
                    <dt><?= esc(lang('Commerce.checkout.total')) ?></dt>
                    <dd class="tabular-nums"><?= esc(money((int) $order['total_cents'], $currency)) ?></dd>
                </div>
            </dl>

            <?php // Only when there is an address to give. An invitation to
                  // write to nobody is worse than no invitation. ?>
            <?php if ($supportEmail = (string) setting('email', '', 'contact')): ?>
                <p class="mt-5 text-xs text-white/50">
                    <?= esc(lang('Commerce.order.help')) ?>
                    <a href="mailto:<?= esc($supportEmail, 'attr') ?>?subject=<?= esc(rawurlencode((string) $order['order_no']), 'attr') ?>"
                       class="text-brand-red underline decoration-line underline-offset-4"><?= esc($supportEmail) ?></a>
                </p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
