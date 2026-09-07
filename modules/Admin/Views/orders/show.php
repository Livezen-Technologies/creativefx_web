<?php helper(['url', 'norlanka', 'commerce', 'catalog']); $this->extend('Modules\Admin\Views\layout');

/**
 * One order, end to end: what was bought, who is coming, what has been paid,
 * what has gone back, and what it produced.
 *
 * The line items are printed from their frozen snapshots — `title_snapshot` and
 * `meta_snapshot_json` — and not from the course and session rows they point
 * at. That is the whole reason those columns exist: a course renamed, a class
 * moved to another venue or a date withdrawn two years from now must not
 * silently rewrite what somebody's receipt says they bought.
 *
 * The billing block lists its fields by name rather than looping the decoded
 * JSON. `billing_json` also carries `cart_token_hash`, which is half of what
 * lets a guest open their own order without an account; printing the whole map
 * would put it on screen, into a screenshot and into a support ticket.
 */

$statusLabels = [
    'pending_payment'    => 'Awaiting payment',
    'paid'               => 'Paid',
    'partially_refunded' => 'Part refunded',
    'refunded'           => 'Refunded',
    'cancelled'          => 'Cancelled',
    'failed'             => 'Failed',
];
$statusTones = [
    'pending_payment'    => 'bg-amber-500/15 text-amber-300',
    'paid'               => 'bg-emerald-500/15 text-emerald-300',
    'partially_refunded' => 'bg-amber-500/15 text-amber-300',
    'refunded'           => 'bg-white/10 text-white/60',
    'cancelled'          => 'bg-white/10 text-white/40',
    'failed'             => 'bg-brand-red/15 text-brand-red',
];

$currency = strtoupper((string) $order['currency']);
$billing  = json_decode((string) $order['billing_json'], true);
$billing  = is_array($billing) ? $billing : [];

$outstanding = (int) $order['total_cents'] - $received;
$room        = $received - $refunded;

$paidPayments = array_values(array_filter($payments, static fn (array $p): bool => (string) $p['status'] === 'paid'));

$when = static fn (?string $ts, string $format = 'j M Y, H:i'): string => $ts ? date($format, strtotime($ts)) : '—';

// The refund box needs the amount back as a plain figure a person can edit,
// which money() will not give (it carries a symbol and drops LKR decimals).
// Split with intdiv and a modulo rather than dividing: this is the one file in
// the admin that would otherwise put a float in front of money, and the
// controller parses it back with the same integer arithmetic.
$plain = static fn (int $cents): string => intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
?>
<?= $this->section('content') ?>

<a href="<?= site_url('admin/orders') ?>" class="text-xs uppercase tracking-widest text-white/50 hover:text-white">← All orders</a>

<div class="mt-4 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h2 class="font-mono text-2xl font-bold"><?= esc($order['order_no']) ?></h2>
        <p class="mt-1 text-sm text-white/55">
            Placed <?= esc($when($order['placed_at'])) ?>
            <?php if ($order['gateway']): ?> · via <?= esc($order['gateway']) ?><?php endif; ?>
            <?php if ($order['paid_at']): ?> · paid <?= esc($when($order['paid_at'])) ?><?php endif; ?>
        </p>
    </div>
    <div class="text-right">
        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= esc($statusTones[$order['status']] ?? 'bg-white/10 text-white/60') ?>">
            <?= esc($statusLabels[$order['status']] ?? $order['status']) ?>
        </span>
        <p class="mt-2 text-2xl font-bold"><?= esc(money((int) $order['total_cents'], $currency)) ?> <span class="text-sm font-normal text-white/40"><?= esc($currency) ?></span></p>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">

        <!-- What was bought, as it was at the moment of sale -->
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <h3 class="text-xs font-semibold uppercase tracking-widest text-white/45">Line items</h3>

            <?php foreach ($items as $item):
                $meta      = json_decode((string) $item['meta_snapshot_json'], true) ?: [];
                $attendees = json_decode((string) $item['attendee_json'], true) ?: [];
                $seats     = $enrolments[(int) $item['id']] ?? []; ?>
                <div class="mt-5 border-t border-white/10 pt-5 first:mt-4 first:border-0 first:pt-0">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium"><?= esc($item['title_snapshot'] ?: 'Item #' . $item['id']) ?></p>
                            <p class="mt-1 text-xs text-white/45">
                                <?= esc(ucfirst((string) $item['item_type'])) ?>
                                <?php if (! empty($meta['mode'])): ?> · <?= esc(mode_label((string) $meta['mode']) ?: $meta['mode']) ?><?php endif; ?>
                                <?php if (! empty($meta['start_date'])): ?>
                                    · <?= esc(date('j M Y', strtotime((string) $meta['start_date']))) ?>
                                    <?php if (! empty($meta['end_date']) && $meta['end_date'] !== $meta['start_date']): ?>–<?= esc(date('j M Y', strtotime((string) $meta['end_date']))) ?><?php endif; ?>
                                <?php endif; ?>
                                <?php if (! empty($meta['venue'])): ?> · <?= esc($meta['venue']) ?><?php endif; ?>
                                <?php if (! empty($meta['timezone'])): ?> · <?= esc($meta['timezone']) ?><?php endif; ?>
                            </p>
                            <?php if (! empty($item['session_id'])): ?>
                                <a href="<?= site_url('admin/course-sessions/' . (int) $item['session_id']) ?>" class="mt-1 inline-block text-xs text-brand-red hover:underline">Open the class</a>
                            <?php endif; ?>
                        </div>
                        <div class="whitespace-nowrap text-right text-sm">
                            <p><?= esc((string) $item['qty']) ?> × <?= esc(money((int) $item['unit_price_cents'], $currency)) ?></p>
                            <?php if ((int) $item['discount_cents'] > 0): ?>
                                <p class="text-xs text-emerald-300">− <?= esc(money((int) $item['discount_cents'], $currency)) ?></p>
                            <?php endif; ?>
                            <p class="mt-0.5 font-semibold"><?= esc(money((int) $item['total_cents'], $currency)) ?></p>
                        </div>
                    </div>

                    <?php // Attendees as typed at checkout, beside the enrolments they
                          // actually produced. The two can differ — an attendee left
                          // blank falls back to the buyer, see
                          // EnrolmentService::attendeesFor() — and seeing both is how
                          // somebody works out why a name is not on the register. ?>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-widest text-white/35">Attendees named</p>
                            <?php if ($attendees === []): ?>
                                <p class="mt-1 text-xs text-white/40">None named — the seats fall back to the buyer.</p>
                            <?php else: ?>
                                <ul class="mt-1 space-y-1 text-xs text-white/70">
                                    <?php foreach ($attendees as $a): ?>
                                        <li><?= esc((string) ($a['name'] ?? '')) ?> <span class="text-white/40"><?= esc((string) ($a['email'] ?? '')) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-widest text-white/35">Enrolments created</p>
                            <?php if ($seats === []): ?>
                                <p class="mt-1 text-xs text-white/40">None yet — enrolments are created when the payment is confirmed.</p>
                            <?php else: ?>
                                <ul class="mt-1 space-y-1 text-xs">
                                    <?php foreach ($seats as $e): ?>
                                        <li>
                                            <span class="text-white/80"><?= esc(trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')) ?: (string) $e['email']) ?></span>
                                            <span class="text-white/40"><?= esc((string) $e['email']) ?></span>
                                            <span class="ml-1 rounded bg-white/10 px-1.5 py-0.5 text-[10px] capitalize text-white/60"><?= esc((string) $e['status']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($items === []): ?>
                <p class="mt-4 text-sm text-white/40">This order has no line items, which should not be possible. Tell a developer.</p>
            <?php endif; ?>
        </div>

        <!-- The money, in and back out -->
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <h3 class="text-xs font-semibold uppercase tracking-widest text-white/45">Payments</h3>
            <?php if ($payments === []): ?>
                <p class="mt-3 text-sm text-white/40">Nothing received yet.</p>
            <?php else: ?>
                <table class="mt-3 w-full text-sm">
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="py-2 pr-3">
                                    <span class="text-white/80"><?= esc($p['gateway']) ?></span>
                                    <div class="font-mono text-xs text-white/40"><?= esc($p['gateway_ref']) ?></div>
                                </td>
                                <td class="py-2 pr-3 text-xs text-white/50"><?= esc($when($p['received_at'])) ?></td>
                                <td class="py-2 pr-3">
                                    <?php // `mismatch` is set by the webhook handler when a
                                          // verified delivery named the wrong sum. It is
                                          // evidence, not income, and must never read as a
                                          // payment for this order. ?>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= (string) $p['status'] === 'paid' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-brand-red/15 text-brand-red' ?>"><?= esc($p['status']) ?></span>
                                </td>
                                <td class="py-2 text-right font-medium"><?= esc(money((int) $p['amount_cents'], strtoupper((string) $p['currency']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h3 class="mt-7 text-xs font-semibold uppercase tracking-widest text-white/45">Refunds</h3>
            <?php if ($refunds === []): ?>
                <p class="mt-3 text-sm text-white/40">None.</p>
            <?php else: ?>
                <table class="mt-3 w-full text-sm">
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($refunds as $r): ?>
                            <tr>
                                <td class="py-2 pr-3">
                                    <span class="text-white/80"><?= esc($r['reason'] ?: 'Refund') ?></span>
                                    <div class="font-mono text-xs text-white/40"><?= esc((string) $r['gateway_ref']) ?></div>
                                </td>
                                <td class="py-2 pr-3 text-xs text-white/50"><?= esc($when($r['processed_at'])) ?></td>
                                <td class="py-2 pr-3"><span class="rounded-full bg-white/10 px-2 py-0.5 text-[11px] capitalize text-white/60"><?= esc((string) $r['status']) ?></span></td>
                                <td class="py-2 text-right font-medium text-amber-300">− <?= esc(money((int) $r['amount_cents'], strtoupper((string) $r['currency']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-6">

        <!-- Totals -->
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <h3 class="text-xs font-semibold uppercase tracking-widest text-white/45">Totals</h3>
            <dl class="mt-3 space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-white/50">Subtotal</dt><dd><?= esc(money((int) $order['subtotal_cents'], $currency)) ?></dd></div>
                <?php if ((int) $order['discount_cents'] > 0): ?>
                    <div class="flex justify-between">
                        <dt class="text-white/50">Discount<?php if ($coupon): ?> <span class="text-white/35">(<?= esc($coupon['code']) ?>)</span><?php endif; ?></dt>
                        <dd class="text-emerald-300">− <?= esc(money((int) $order['discount_cents'], $currency)) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if ((int) $order['tax_cents'] > 0): ?>
                    <div class="flex justify-between"><dt class="text-white/50">Tax</dt><dd><?= esc(money((int) $order['tax_cents'], $currency)) ?></dd></div>
                <?php endif; ?>
                <div class="flex justify-between border-t border-white/10 pt-2 font-semibold"><dt>Total</dt><dd><?= esc(money((int) $order['total_cents'], $currency)) ?></dd></div>
                <div class="flex justify-between"><dt class="text-white/50">Received</dt><dd class="text-emerald-300"><?= esc(money($received, $currency)) ?></dd></div>
                <?php if ($refunded > 0): ?>
                    <div class="flex justify-between"><dt class="text-white/50">Refunded</dt><dd class="text-amber-300">− <?= esc(money($refunded, $currency)) ?></dd></div>
                <?php endif; ?>
                <?php if ($outstanding > 0): ?>
                    <div class="flex justify-between font-semibold"><dt class="text-amber-300">Outstanding</dt><dd class="text-amber-300"><?= esc(money($outstanding, $currency)) ?></dd></div>
                <?php endif; ?>
            </dl>

            <?php if ($order['due_at']): ?>
                <p class="mt-3 text-xs text-white/40">Payment due <?= esc($when($order['due_at'], 'j M Y')) ?>. The seats are held until then.</p>
            <?php endif; ?>

            <?php if ($invoice): ?>
                <p class="mt-3 border-t border-white/10 pt-3 text-xs text-white/50">
                    Invoice <span class="font-mono text-white/80"><?= esc($invoice['number']) ?></span>
                    · issued <?= esc($when($invoice['issued_at'], 'j M Y')) ?>
                    · <?= esc((string) $invoice['status']) ?>
                </p>
            <?php else: ?>
                <p class="mt-3 border-t border-white/10 pt-3 text-xs text-white/40">No invoice yet — one is issued with its own number when the order is fulfilled.</p>
            <?php endif; ?>
        </div>

        <!-- Buyer -->
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <h3 class="text-xs font-semibold uppercase tracking-widest text-white/45">Buyer</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <?php
                // Named explicitly. See the docblock: billing_json also holds
                // cart_token_hash, and a loop over the map would print it.
                $lines = [
                    'Name'     => (string) ($billing['name'] ?? ''),
                    'Email'    => (string) ($billing['email'] ?? ''),
                    'Phone'    => (string) ($billing['phone'] ?? ''),
                    'Company'  => (string) ($billing['company'] ?? ''),
                    'Address'  => trim(implode(', ', array_filter([
                        (string) ($billing['address'] ?? ''),
                        (string) ($billing['city'] ?? ''),
                        (string) ($billing['postcode'] ?? ''),
                        (string) ($billing['country'] ?? $order['country'] ?? ''),
                    ]))),
                    'Tax ID'   => (string) ($billing['tax_id'] ?? ''),
                    'PO'       => (string) ($billing['po_number'] ?? ''),
                ];
                foreach ($lines as $label => $value): if ($value === '') { continue; } ?>
                    <div>
                        <dt class="text-[11px] uppercase tracking-widest text-white/35"><?= esc($label) ?></dt>
                        <dd class="mt-0.5 text-white/85"><?= esc($value) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <?php if (! empty($billing['notes'])): ?>
                <p class="mt-3 whitespace-pre-line border-t border-white/10 pt-3 text-xs text-white/60"><?= esc((string) $billing['notes']) ?></p>
            <?php endif; ?>
        </div>

        <?php // ── Confirm a bank transfer ─────────────────────────────────────
              // Shown only where it can do anything. On a paid order this is not
              // a disabled button, it is absent: a button that looks like it
              // would take the money again is a button somebody presses. ?>
        <?php if (in_array((string) $order['status'], ['pending_payment', 'failed'], true)): ?>
            <form method="post" action="<?= site_url('admin/orders/' . (int) $order['id'] . '/mark-paid') ?>"
                  class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-6"
                  onsubmit="return confirm('Confirm that <?= esc(money((int) $order['total_cents'], $currency), 'attr') ?> has arrived? This enrols everybody on the order and emails them.')">
                <?= csrf_field() ?>
                <h3 class="text-xs font-semibold uppercase tracking-widest text-amber-300">Mark as paid</h3>
                <p class="mt-2 text-xs leading-relaxed text-white/60">
                    Use this only once you can see <strong class="text-white/85"><?= esc(money((int) $order['total_cents'], $currency)) ?></strong>
                    on the bank statement. It records the payment, enrols every attendee, issues the invoice and sends the joining instructions.
                </p>

                <label class="mt-4 block text-[11px] uppercase tracking-widest text-white/40">Bank reference</label>
                <input type="text" name="reference" required maxlength="191" value="<?= esc(old('reference'), 'attr') ?>"
                       class="mt-1.5 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 font-mono text-sm focus:border-brand-red focus:outline-none"
                       placeholder="The reference on the statement">
                <p class="mt-1 text-[11px] text-white/35">The bank's own reference, not one you invent. It is what stops the same transfer being recorded twice.</p>

                <label class="mt-4 block text-[11px] uppercase tracking-widest text-white/40">Date received</label>
                <input type="date" name="received" value="<?= esc(old('received', date('Y-m-d')), 'attr') ?>"
                       class="mt-1.5 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">

                <label class="mt-4 block text-[11px] uppercase tracking-widest text-white/40">Note (optional)</label>
                <textarea name="note" rows="2" maxlength="500"
                          class="mt-1.5 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none"
                          placeholder="Anything the next person should know"><?= esc(old('note')) ?></textarea>

                <button type="submit" class="mt-4 w-full rounded-lg bg-brand-red px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-brand-red-dark">
                    Record payment and enrol
                </button>
            </form>
        <?php endif; ?>

        <?php // ── Record a refund ─────────────────────────────────────────────
              // Only where there is money that could go back. ?>
        <?php if ($paidPayments !== [] && $room > 0): ?>
            <form method="post" action="<?= site_url('admin/orders/' . (int) $order['id'] . '/refund') ?>"
                  class="rounded-2xl border border-white/10 bg-white/[0.02] p-6"
                  onsubmit="return confirm('Record this refund? It does not move any money — refund it in the gateway first.')">
                <?= csrf_field() ?>
                <h3 class="text-xs font-semibold uppercase tracking-widest text-white/45">Record a refund</h3>

                <p class="mt-2 rounded-lg border border-brand-red/40 bg-brand-red/10 px-3 py-2 text-xs leading-relaxed text-white/75">
                    <strong class="text-brand-red">This does not refund the card.</strong>
                    Move the money in the gateway's own dashboard first, then record it here so the seats, the enrolments and the accounts agree with the bank.
                </p>

                <label class="mt-4 block text-[11px] uppercase tracking-widest text-white/40">Against payment</label>
                <select name="payment_id" class="mt-1.5 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
                    <?php foreach ($paidPayments as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= esc($p['gateway']) ?> · <?= esc(money((int) $p['amount_cents'], strtoupper((string) $p['currency']))) ?> · <?= esc((string) $p['gateway_ref']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="mt-4 block text-[11px] uppercase tracking-widest text-white/40">Amount (<?= esc($currency) ?>)</label>
                <input type="text" name="amount" required inputmode="decimal" value="<?= esc(old('amount'), 'attr') ?>"
                       class="mt-1.5 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none"
                       placeholder="<?= esc($plain($room), 'attr') ?>">
                <p class="mt-1 text-[11px] text-white/35">
                    Up to <?= esc(money($room, $currency)) ?>. Refunding all of it releases the seats and cancels the enrolments; anything less records the money and leaves both alone.
                </p>

                <label class="mt-4 block text-[11px] uppercase tracking-widest text-white/40">Refund reference</label>
                <input type="text" name="gateway_ref" required maxlength="191" value="<?= esc(old('gateway_ref'), 'attr') ?>"
                       class="mt-1.5 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 font-mono text-sm focus:border-brand-red focus:outline-none"
                       placeholder="The id the gateway gave the refund">
                <p class="mt-1 text-[11px] text-white/35">Required, and it is what stops a double submit refunding the seats twice.</p>

                <label class="mt-4 block text-[11px] uppercase tracking-widest text-white/40">Reason</label>
                <input type="text" name="reason" maxlength="255" value="<?= esc(old('reason'), 'attr') ?>"
                       class="mt-1.5 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none"
                       placeholder="Cancelled by the learner, class did not run…">

                <button type="submit" class="mt-4 w-full rounded-lg border border-white/15 px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white/80 hover:border-brand-red hover:text-white">
                    Record refund
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
