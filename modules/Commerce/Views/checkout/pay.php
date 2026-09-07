<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The hand-off to the payment gateway.
 *
 * One page for all four routes, because the three shapes a gateway can ask for
 * are the same problem wearing different clothes:
 *
 *   `redirect`     — follow a URL (Stripe Checkout, PayPal approval)
 *   `post`         — auto-submit signed fields to the gateway (PayHere)
 *   `instructions` — nothing to follow at all (bank transfer)
 *
 * Two rules shape the markup. **Nothing here depends on JavaScript**: the
 * redirect is a zero-delay meta refresh with a real link underneath it, and the
 * auto-submitted form has a real button. A payment page that is blank when a
 * script is blocked is a payment page that silently loses the sale.
 *
 * And **`instructions` is not always good news.** Stripe and PayPal both fall
 * back to `instructions` with an error message when their API will not start a
 * session, so the distinction drawn below is on the gateway's key rather than
 * on the mode: only the bank-transfer gateway's instructions are instructions.
 * Everything else that lands there is a failure, and a failure has to end in
 * another way to pay rather than in a dead end.
 *
 * The order already exists and its seats are already held. Nothing on this page
 * marks anything paid — that is a verified webhook's job, in
 * EnrolmentService::fulfil().
 *
 * @var array  $order
 * @var list<array> $items
 * @var \Modules\Commerce\Services\Gateways\PaymentGateway $gateway
 * @var array  $handoff  mode, and url | action+fields | message
 * @var list<\Modules\Commerce\Services\Gateways\PaymentGateway> $others
 */
helper(['norlanka', 'commerce', 'url']);

$mode     = (string) ($handoff['mode'] ?? 'instructions');
$currency = (string) $order['currency'];
$isBank   = $gateway->key() === 'bank';

// A gateway that could not start. See the docblock: mode alone does not say so.
$failed = $mode === 'instructions' && ! $isBank;
?>

<?= $this->section('head') ?>
<?php if ($mode === 'redirect' && ! empty($handoff['url'])): ?>
    <?php // Zero delay on purpose. A meta refresh with a timeout is a WCAG 2.2.1
          // failure; an immediate one is the ordinary redirect it looks like,
          // and the link in the page covers a browser that blocks it. ?>
    <meta http-equiv="refresh" content="0;url=<?= esc($handoff['url'], 'attr') ?>">
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Commerce.order.title', [$order['order_no']])]],
    'eyebrow' => lang('Commerce.pay.eyebrow'),
    'heading' => $failed ? lang('Commerce.pay.failed_title') : lang('Commerce.pay.title'),
    'intro'   => $failed ? '' : lang('Commerce.pay.intro', [$gateway->label()]),
], ['saveData' => false]) ?>

<div class="container-x grid gap-12 py-14 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-14">
    <div class="min-w-0 space-y-8">

        <?php if ($mode === 'redirect' && ! empty($handoff['url'])): ?>
            <section class="rounded-3xl border border-line bg-surface p-7">
                <p class="text-white/75"><?= esc(lang('Commerce.pay.redirect_body', [$gateway->label()])) ?></p>
                <a href="<?= esc($handoff['url'], 'attr') ?>" class="btn-brand mt-6" rel="noopener">
                    <?= esc(lang('Commerce.pay.redirect_cta', [$gateway->label()])) ?>
                </a>
            </section>

        <?php elseif ($mode === 'post' && ! empty($handoff['action'])): ?>
            <section class="rounded-3xl border border-line bg-surface p-7">
                <p class="text-white/75"><?= esc(lang('Commerce.pay.redirect_body', [$gateway->label()])) ?></p>

                <?php // The fields are signed by the gateway class and must go
                      // across exactly as they were built — a re-ordered or
                      // re-cased value breaks the hash silently rather than
                      // loudly. No CSRF token: this posts outward, to the
                      // gateway, not back to this site. ?>
                <form id="gateway-form" method="post" action="<?= esc($handoff['action'], 'attr') ?>" class="mt-6">
                    <?php foreach (($handoff['fields'] ?? []) as $name => $value): ?>
                        <input type="hidden" name="<?= esc((string) $name, 'attr') ?>" value="<?= esc((string) $value, 'attr') ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn-brand">
                        <?= esc(lang('Commerce.pay.redirect_cta', [$gateway->label()])) ?>
                    </button>
                </form>
                <?php // Submitted for the buyer if scripting is available; the
                      // button above is what happens when it is not. ?>
                <script>document.getElementById('gateway-form').submit();</script>
            </section>

        <?php elseif ($isBank): ?>
            <section class="rounded-3xl border border-line bg-surface p-7">
                <h2 class="text-xl font-bold"><?= esc(lang('Commerce.pay.bank_title')) ?></h2>
                <p class="mt-3 text-white/75"><?= esc(lang('Commerce.pay.bank_body')) ?></p>

                <dl class="mt-6 grid gap-4 border-y border-line py-5 sm:grid-cols-2">
                    <div>
                        <dt class="field-label"><?= esc(lang('Commerce.order.reference')) ?></dt>
                        <dd class="font-mono text-lg font-bold"><?= esc($order['order_no']) ?></dd>
                    </div>
                    <div>
                        <dt class="field-label"><?= esc(lang('Commerce.order.amount_due')) ?></dt>
                        <dd class="text-lg font-bold tabular-nums"><?= esc(money((int) $order['total_cents'], $currency)) ?></dd>
                    </div>
                    <?php if (! empty($order['due_at'])): ?>
                        <div class="sm:col-span-2">
                            <dt class="field-label"><?= esc(lang('Commerce.order.pay_by')) ?></dt>
                            <dd class="font-semibold"><?= esc(date('j M Y', strtotime((string) $order['due_at']))) ?></dd>
                            <dd class="mt-1 text-sm text-white/60"><?= esc(lang('Commerce.pay.held_until')) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <?php $instructions = trim((string) ($handoff['message'] ?? '')); ?>
                <?php if ($instructions !== ''): ?>
                    <div class="prose-site prose-sm mt-6 text-sm"><?= rich_text($instructions) ?></div>
                <?php else: ?>
                    <?php // No bank details are configured yet. Nothing is
                          // invented here: an account number guessed onto a
                          // payment page is money sent to a stranger. The order
                          // stands, the seats are held, and the school sends the
                          // details. ?>
                    <p class="mt-6 rounded-xl border border-line px-4 py-3 text-sm text-white/70">
                        <?= esc(lang('Commerce.pay.bank_details_pending')) ?>
                    </p>
                <?php endif; ?>

                <div class="mt-7 flex flex-wrap gap-4">
                    <a href="<?= esc(locale_url('order/' . $order['order_no'] . '/invoice')) ?>" class="btn-brand">
                        <?= esc(lang('Commerce.order.view_invoice')) ?>
                    </a>
                    <a href="<?= esc(locale_url('order/' . $order['order_no'])) ?>" class="btn-ghost">
                        <?= esc(lang('Commerce.order.view_order')) ?>
                    </a>
                </div>
            </section>

        <?php else: ?>
            <?php // The gateway would not start. Honest about it, and never a
                  // dead end: the order and its seats are intact, so the only
                  // thing needed is another way to pay. ?>
            <section class="rounded-3xl border border-brand-red bg-brand-red/10 p-7" role="alert">
                <h2 class="text-xl font-bold"><?= esc(lang('Commerce.pay.failed_title')) ?></h2>
                <p class="mt-3 text-white/80"><?= esc($handoff['message'] ?? lang('Commerce.gateway.unavailable')) ?></p>
                <p class="mt-3 text-sm text-white/70"><?= esc(lang('Commerce.pay.failed_body', [$order['order_no']])) ?></p>
            </section>
        <?php endif; ?>

        <?php if ($others !== []): ?>
            <section aria-labelledby="other-methods">
                <h2 id="other-methods" class="section-title"><?= esc(lang('Commerce.pay.other_methods')) ?></h2>
                <ul class="mt-4 space-y-2">
                    <?php foreach ($others as $other): ?>
                        <li>
                            <a href="<?= esc(locale_url('checkout/pay/' . $order['order_no']) . '?method=' . rawurlencode($other->key())) ?>"
                               class="flex items-center justify-between gap-4 rounded-2xl border border-line bg-surface px-5 py-4 transition hover:border-brand-red">
                                <span>
                                    <span class="block font-semibold"><?= esc($other->label()) ?></span>
                                    <span class="mt-0.5 block text-sm text-white/60"><?= esc(lang('Commerce.gateway.desc_' . $other->key())) ?></span>
                                </span>
                                <span aria-hidden="true" class="shrink-0 text-brand-red">&rarr;</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>

    <?php // ── What is being paid for ─────────────────────────────────────── ?>
    <aside class="lg:sticky lg:top-28 lg:self-start" aria-labelledby="pay-summary">
        <div class="rounded-3xl border border-line bg-surface p-6">
            <h2 id="pay-summary" class="text-lg font-bold"><?= esc(lang('Commerce.order.title', [$order['order_no']])) ?></h2>

            <ul class="mt-5 space-y-4">
                <?php foreach ($items as $item): ?>
                    <li class="flex gap-3 border-b border-line pb-4 last:border-0 last:pb-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium leading-snug"><?= esc($item['title_snapshot']) ?></p>
                            <p class="mt-1 text-xs text-white/55"><?= esc(lang('Commerce.checkout.seats_count', [(int) $item['qty']])) ?></p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold tabular-nums"><?= esc(money((int) $item['total_cents'], $currency)) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="mt-5 flex justify-between gap-4 border-t border-line pt-4 text-base font-bold">
                <span><?= esc(lang('Commerce.checkout.total')) ?></span>
                <span class="tabular-nums"><?= esc(money((int) $order['total_cents'], $currency)) ?></span>
            </p>

            <p class="mt-4 text-xs text-white/50"><?= esc(lang('Commerce.pay.confirmation_note')) ?></p>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
