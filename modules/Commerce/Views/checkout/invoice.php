<?php
/**
 * The invoice, as an A4 document.
 *
 * Deliberately a standalone page rather than a view inside the site layout, and
 * for one reason: a document that gets printed and filed cannot inherit a theme
 * that flips. The site's palette is a set of CSS variables — `white` is not
 * white and `brand-black` is not black — so a themed invoice would print as
 * pale text on a dark ground for anybody reading the site in dark mode, which
 * is unreadable on paper and empties a toner cartridge doing it. Everything
 * below therefore carries its own plain black-on-white styling, and the site's
 * header, footer, preloader and chat widget stay out of a document that a
 * finance department will attach to a payment run.
 *
 * It is available before payment as well as after. A company cannot pay by bank
 * transfer without a document to pay against, so an order with no row in
 * `invoices` yet prints as a **proforma** against its order number. The invoice
 * series itself is gapless accounting issued once at fulfilment; minting a
 * number for an order that may never be paid would put a hole in it.
 *
 * Money is read from the order, not recomputed. `subtotal`, `discount`, `tax`
 * and `total` were frozen at the moment of sale at the rates that applied that
 * day; re-deriving them here would mean a reprint next year showing what would
 * be charged now rather than what was charged then.
 *
 * @var array  $order
 * @var list<array> $items
 * @var ?array $invoice  the invoices row, or null while the order is unpaid
 * @var array  $billing
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$currency = (string) $order['currency'];
$paid     = ! empty($order['paid_at']) && in_array((string) $order['status'], ['paid', 'partially_refunded'], true);
$isBank   = ($order['gateway'] ?? '') === 'bank';

$number   = $invoice !== null ? (string) $invoice['number'] : (string) $order['order_no'];
$issued   = $invoice !== null && ! empty($invoice['issued_at'])
    ? (string) $invoice['issued_at']
    : (string) ($order['placed_at'] ?: $order['created_at']);

// The school's own details. Every one of these is a setting rather than a
// constant, and every one is printed only when it has been filled in: an
// invoice carrying an invented registration number is a document somebody has
// to be told to ignore.
$school = [
    'name'    => (string) setting('site_name', ''),
    'address' => (string) setting('address', '', 'contact'),
    'email'   => (string) setting('email', '', 'contact'),
    'phone'   => (string) setting('phone', '', 'contact'),
    'reg'     => (string) setting('reg_number', '', 'invoice'),
    'tax'     => (string) setting('tax_number', '', 'invoice'),
];
?>
<!DOCTYPE html>
<html lang="<?= esc(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc(($invoice !== null ? lang('Commerce.invoice.title') : lang('Commerce.invoice.proforma')) . ' ' . $number) ?></title>
    <style>
        /* A4 with a printer-safe margin. The screen view mimics the sheet so
           that what somebody checks on a laptop is what comes out of the tray. */
        @page { size: A4; margin: 14mm; }

        :root { color-scheme: light; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e9e9ec;
            color: #111114;
            font: 13px/1.55 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            padding: 18mm 16mm;
            background: #ffffff;
            box-shadow: 0 2px 18px rgba(0, 0, 0, 0.14);
        }

        h1 { margin: 0; font-size: 26px; letter-spacing: -0.01em; }
        h2 { margin: 0 0 6px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #5b5b66; }

        .head { display: flex; flex-wrap: wrap; gap: 24px; justify-content: space-between; align-items: flex-start; }
        .head .meta { text-align: right; }
        .muted { color: #5b5b66; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .parties { display: flex; flex-wrap: wrap; gap: 32px; margin-top: 28px; }
        .parties > div { flex: 1 1 220px; }
        address { font-style: normal; }

        .status {
            display: inline-block; margin-top: 10px; padding: 3px 10px;
            border: 1px solid currentColor; border-radius: 999px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
        }
        .status-paid { color: #14663f; }
        .status-due  { color: #8a4b00; }

        table { width: 100%; border-collapse: collapse; margin-top: 28px; }
        thead th {
            border-bottom: 1.5px solid #111114;
            padding: 0 0 7px; text-align: left;
            font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: #5b5b66;
        }
        tbody td { border-bottom: 1px solid #dcdce2; padding: 11px 0; vertical-align: top; }
        /* A line and its attendees must not be split across two sheets: a name
           orphaned at the top of page two reads as a different booking. */
        tbody tr { break-inside: avoid; page-break-inside: avoid; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .attendees { margin: 7px 0 0; padding: 0; list-style: none; font-size: 11.5px; color: #5b5b66; }
        .attendees li { padding: 1px 0; }

        .totals { margin-left: auto; margin-top: 18px; width: 62mm; }
        .totals td { padding: 4px 0; border: 0; }
        .totals .grand td { border-top: 1.5px solid #111114; padding-top: 9px; font-size: 15px; font-weight: 700; }

        .notes { margin-top: 30px; border-top: 1px solid #dcdce2; padding-top: 16px; font-size: 11.5px; color: #3f3f4a; }
        .notes p { margin: 0 0 8px; }

        .toolbar { max-width: 210mm; margin: 20px auto 0; text-align: right; }
        .toolbar button, .toolbar a {
            display: inline-block; margin-left: 8px; padding: 8px 18px;
            border: 1px solid #111114; border-radius: 999px; background: #111114; color: #ffffff;
            font: inherit; font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer;
        }
        .toolbar a { background: transparent; color: #111114; }
        .toolbar button:focus-visible, .toolbar a:focus-visible { outline: 3px solid #8a4b00; outline-offset: 2px; }

        @media print {
            body { background: #ffffff; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <a href="<?= esc(locale_url('order/' . $order['order_no'])) ?>"><?= esc(lang('Commerce.invoice.back')) ?></a>
    <button type="button" onclick="window.print()"><?= esc(lang('Commerce.invoice.print')) ?></button>
</div>

<main class="sheet">

    <div class="head">
        <div>
            <?php if ($school['name'] !== ''): ?>
                <h1><?= esc($school['name']) ?></h1>
            <?php endif; ?>
            <address class="muted" style="margin-top:8px">
                <?php if ($school['address'] !== ''): ?><?= nl2br(esc($school['address'])) ?><br><?php endif; ?>
                <?php if ($school['email'] !== ''): ?><?= esc($school['email']) ?><br><?php endif; ?>
                <?php if ($school['phone'] !== ''): ?><?= esc($school['phone']) ?><br><?php endif; ?>
                <?php if ($school['reg'] !== ''): ?><?= esc(lang('Commerce.invoice.reg_number', [$school['reg']])) ?><br><?php endif; ?>
                <?php if ($school['tax'] !== ''): ?><?= esc(lang('Commerce.invoice.tax_number', [$school['tax']])) ?><?php endif; ?>
            </address>
        </div>

        <div class="meta">
            <h2><?= esc($invoice !== null ? lang('Commerce.invoice.title') : lang('Commerce.invoice.proforma')) ?></h2>
            <p class="mono" style="margin:0;font-size:17px;font-weight:700"><?= esc($number) ?></p>

            <p class="muted" style="margin:10px 0 0">
                <?= esc(lang('Commerce.invoice.issued')) ?>:
                <strong><?= esc(date('j M Y', strtotime($issued))) ?></strong>
            </p>
            <p class="muted" style="margin:2px 0 0">
                <?= esc(lang('Commerce.order.reference')) ?>:
                <strong class="mono"><?= esc($order['order_no']) ?></strong>
            </p>
            <?php if (! $paid && ! empty($order['due_at'])): ?>
                <p class="muted" style="margin:2px 0 0">
                    <?= esc(lang('Commerce.invoice.due')) ?>:
                    <strong><?= esc(date('j M Y', strtotime((string) $order['due_at']))) ?></strong>
                </p>
            <?php endif; ?>

            <?php // Stated on the face of the document, because the single most
                  // common question a finance department asks about a printed
                  // invoice is whether it has already been settled. ?>
            <span class="status <?= $paid ? 'status-paid' : 'status-due' ?>">
                <?= esc($paid ? lang('Commerce.invoice.status_paid') : lang('Commerce.invoice.status_due')) ?>
            </span>
        </div>
    </div>

    <div class="parties">
        <div>
            <h2><?= esc(lang('Commerce.invoice.bill_to')) ?></h2>
            <?php // Named fields only: billing_json also carries the hashed cart
                  // token that lets a guest reopen their order, and a loop over
                  // the array would print it onto the invoice. ?>
            <address>
                <?php foreach (['company', 'name', 'address', 'city', 'postcode', 'country'] as $field): ?>
                    <?php if (! empty($billing[$field])): ?>
                        <?= esc((string) $billing[$field]) ?><br>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (! empty($billing['email'])): ?><?= esc((string) $billing['email']) ?><br><?php endif; ?>
                <?php if (! empty($billing['tax_id'])): ?><?= esc(lang('Commerce.invoice.tax_number', [$billing['tax_id']])) ?><?php endif; ?>
            </address>
        </div>

        <?php if (! empty($billing['po_number'])): ?>
            <div>
                <h2><?= esc(lang('Commerce.checkout.po_number')) ?></h2>
                <p class="mono" style="margin:0"><?= esc((string) $billing['po_number']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th scope="col"><?= esc(lang('Commerce.invoice.description')) ?></th>
                <th scope="col" class="num"><?= esc(lang('Commerce.invoice.qty')) ?></th>
                <th scope="col" class="num"><?= esc(lang('Commerce.invoice.unit_price')) ?></th>
                <th scope="col" class="num"><?= esc(lang('Commerce.invoice.amount')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item):
                $meta      = json_decode((string) $item['meta_snapshot_json'], true) ?: [];
                $attendees = json_decode((string) $item['attendee_json'], true) ?: [];
                // Gross of any discount, so the column sums exactly to the
                // subtotal below and the discount appears once, on its own line,
                // where an accountant expects to find it.
                $amount    = (int) $item['unit_price_cents'] * (int) $item['qty'];
            ?>
                <tr>
                    <td>
                        <strong><?= esc($item['title_snapshot']) ?></strong>
                        <div class="muted" style="margin-top:3px;font-size:11.5px">
                            <?php if (! empty($meta['mode'])): ?><?= esc(mode_label($meta['mode'])) ?><?php endif; ?>
                            <?php if (! empty($meta['start_date'])): ?>
                                · <?= esc(session_dates(['start_date' => $meta['start_date'], 'end_date' => $meta['end_date'] ?? null])) ?>
                            <?php endif; ?>
                            <?php if (! empty($meta['venue'])): ?> · <?= esc((string) $meta['venue']) ?><?php endif; ?>
                        </div>
                        <?php if ($attendees !== []): ?>
                            <ul class="attendees">
                                <?php foreach ($attendees as $attendee): ?>
                                    <li><?= esc((string) ($attendee['name'] ?? '')) ?> — <?= esc((string) ($attendee['email'] ?? '')) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= (int) $item['qty'] ?></td>
                    <td class="num"><?= esc(money((int) $item['unit_price_cents'], $currency)) ?></td>
                    <td class="num"><?= esc(money($amount, $currency)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tbody>
            <tr>
                <td class="muted"><?= esc(lang('Commerce.checkout.subtotal')) ?></td>
                <td class="num"><?= esc(money((int) $order['subtotal_cents'], $currency)) ?></td>
            </tr>
            <?php if ((int) $order['discount_cents'] > 0): ?>
                <tr>
                    <td class="muted"><?= esc(lang('Commerce.checkout.discount')) ?></td>
                    <td class="num">−<?= esc(money((int) $order['discount_cents'], $currency)) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <?php // Always printed, zero included: an invoice that simply
                      // omits the tax line leaves the reader unable to tell
                      // whether tax was zero or forgotten. ?>
                <td class="muted"><?= esc(lang('Commerce.checkout.tax')) ?></td>
                <td class="num"><?= esc(money((int) $order['tax_cents'], $currency)) ?></td>
            </tr>
            <tr class="grand">
                <td><?= esc(lang('Commerce.invoice.total', [$currency])) ?></td>
                <td class="num"><?= esc(money((int) $order['total_cents'], $currency)) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="notes">
        <?php if ($paid): ?>
            <p><strong><?= esc(lang('Commerce.invoice.paid_note', [date('j M Y', strtotime((string) $order['paid_at']))])) ?></strong></p>
        <?php elseif ($isBank): ?>
            <p><strong><?= esc(lang('Commerce.invoice.how_to_pay')) ?></strong></p>
            <?php $bank = trim((string) setting('bank_instructions', '', 'payments')); ?>
            <?php if ($bank !== ''): ?>
                <p><?= nl2br(esc($bank)) ?></p>
            <?php else: ?>
                <?php // No bank details configured. Nothing is invented on a
                      // document somebody is about to pay against. ?>
                <p><?= esc(lang('Commerce.pay.bank_details_pending')) ?></p>
            <?php endif; ?>
            <p><?= esc(lang('Commerce.invoice.reference_note', [$order['order_no']])) ?></p>
        <?php else: ?>
            <p><strong><?= esc(lang('Commerce.invoice.awaiting_note')) ?></strong></p>
        <?php endif; ?>

        <?php if (! empty($billing['notes'])): ?>
            <p><?= esc(lang('Commerce.invoice.buyer_notes')) ?>: <?= esc((string) $billing['notes']) ?></p>
        <?php endif; ?>

        <?php if ($footer = (string) setting('invoice_footer', '', 'invoice')): ?>
            <p class="muted"><?= nl2br(esc($footer)) ?></p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
