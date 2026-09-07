<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Everything this learner has been billed for.
 *
 * The distinction the page has to make honestly is between an **invoice** and a
 * **proforma**. The invoice series is gapless accounting, issued once when an
 * order is fulfilled; an order still waiting for money has not earned a number
 * in it, and minting one for a purchase that may never complete would put a
 * hole in the series. So an unpaid order links to a proforma against its order
 * number instead, which is exactly the document a company needs in order to
 * raise the payment in the first place.
 *
 * Money is read from the order and never recomputed. The totals were frozen at
 * the moment of sale, at the rates that applied that day; re-deriving them here
 * would show what would be charged now rather than what was charged then.
 *
 * @var list<array> $orders  each carrying an `invoice` row, or null
 * @var string      $current
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

/** What state an order is in, said in one word a buyer recognises. */
$statusLabel = static fn (array $order): string => match ((string) $order['status']) {
    'paid'               => lang('Account.invoices.status_paid'),
    'partially_refunded' => lang('Account.invoices.status_part_refunded'),
    'refunded'           => lang('Account.invoices.status_refunded'),
    'cancelled'          => lang('Account.invoices.status_cancelled'),
    default              => lang('Account.invoices.status_awaiting'),
};
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Account.nav.title'),
    'heading' => lang('Account.invoices.title'),
    'intro'   => lang('Account.invoices.intro'),
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-14 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-14">

    <?= view('Modules\Account\Views\partials\account_nav', ['current' => $current], ['saveData' => false]) ?>

    <div class="min-w-0 space-y-8">

        <?php if ($msg = session()->getFlashdata('notice')): ?>
            <p role="status" class="rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <?php if ($orders === []): ?>

            <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
                <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Account.invoices.empty_heading')) ?></h2>
                <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Account.invoices.empty_body')) ?></p>
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-6"><?= esc(lang('Account.dashboard.browse_courses')) ?></a>
            </section>

        <?php else: ?>

            <?php // Scrolls inside its own box on a narrow screen, so the page
                  // body never scrolls sideways. ?>
            <div class="relative overflow-x-auto rounded-2xl border border-line">
                <table class="w-full min-w-[42rem] border-collapse text-sm">
                    <caption class="sr-only"><?= esc(lang('Account.invoices.caption')) ?></caption>
                    <thead>
                        <tr class="border-b border-line bg-surface text-left text-xs uppercase tracking-wider text-white/55">
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.invoices.col_reference')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.invoices.col_date')) ?></th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold"><?= esc(lang('Account.invoices.col_total')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.invoices.col_status')) ?></th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold"><span class="sr-only"><?= esc(lang('Account.invoices.col_actions')) ?></span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $invoice = $order['invoice'] ?? null;
                            $dated   = (string) ($order['placed_at'] ?: $order['created_at']);
                            ?>
                            <tr>
                                <th scope="row" class="px-4 py-3 text-left font-normal">
                                    <span class="block font-mono font-medium">
                                        <?= esc($invoice !== null ? $invoice['number'] : $order['order_no']) ?>
                                    </span>
                                    <?php if ($invoice === null): ?>
                                        <?php // Named for what it is. A proforma
                                              // is not an invoice and a finance
                                              // department will say so. ?>
                                        <span class="text-xs uppercase tracking-wider text-white/45"><?= esc(lang('Account.invoices.proforma')) ?></span>
                                    <?php endif; ?>
                                </th>
                                <td class="px-4 py-3 text-white/70">
                                    <?= esc($dated === '' ? '—' : date('j M Y', strtotime($dated))) ?>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <?= esc(money((int) $order['total_cents'], (string) $order['currency'])) ?>
                                </td>
                                <td class="px-4 py-3 text-white/70"><?= esc($statusLabel($order)) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="<?= esc($invoice !== null
                                        ? locale_url('account/invoices/' . (int) $invoice['id'])
                                        : locale_url('order/' . $order['order_no'] . '/invoice')) ?>"
                                       class="font-medium text-brand-red underline decoration-line underline-offset-4">
                                        <?= esc($invoice !== null ? lang('Account.invoices.view') : lang('Account.invoices.view_proforma')) ?>
                                    </a>
                                    <a href="<?= esc(locale_url('order/' . $order['order_no'])) ?>"
                                       class="ml-4 font-medium text-white/60 underline decoration-line underline-offset-4 hover:text-brand-red">
                                        <?= esc(lang('Account.invoices.order')) ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="max-w-2xl text-sm text-white/55"><?= esc(lang('Account.invoices.note')) ?></p>

        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
