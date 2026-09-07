<?php
helper(['norlanka', 'url']);

/**
 * To whoever paid.
 *
 * On a corporate order this is a finance mailbox that will never open the
 * course, so it carries the numbers and nothing else: what was bought, what it
 * cost, and the order reference to quote.
 *
 * @var array  $order
 * @var array  $billing
 * @var list<array> $items
 * @var string $school
 */
$fmt = static function (int $cents, string $currency): string {
    // Formatted from integers to the end. The division happens once, here, at
    // the last possible moment before a human reads it.
    $symbol = $currency === 'LKR' ? 'Rs ' : '$';

    return $symbol . number_format($cents / 100, 2);
};

ob_start(); ?>
<p style="margin:0 0 16px;">Hello <?= esc($billing['name'] ?? '') ?: 'there' ?>,</p>

<p style="margin:0 0 20px;">
    Thank you — order <strong><?= esc($order['order_no']) ?></strong> is confirmed
    and everybody on it has had their joining instructions.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #dfe3ef;border-radius:10px;margin:0 0 22px;font-size:14px;">
    <?php foreach ($items as $item):
        $meta = json_decode((string) $item['meta_snapshot_json'], true) ?: []; ?>
        <tr>
            <td style="padding:12px 16px;border-bottom:1px solid #eef0f7;">
                <strong><?= esc($item['title_snapshot']) ?></strong><br>
                <span style="color:#55607a;">
                    <?= esc($item['qty']) ?> &times;
                    <?php if (! empty($meta['start_date'])): ?>
                        <?= esc((new DateTimeImmutable($meta['start_date']))->format('j M Y')) ?>
                    <?php endif; ?>
                    <?php if (! empty($meta['city'])): ?> &middot; <?= esc($meta['city']) ?><?php endif; ?>
                </span>
            </td>
            <td align="right" style="padding:12px 16px;border-bottom:1px solid #eef0f7;white-space:nowrap;">
                <?= esc($fmt((int) $item['total_cents'], (string) $order['currency'])) ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ((int) $order['discount_cents'] > 0): ?>
        <tr>
            <td style="padding:10px 16px;color:#55607a;">Discount</td>
            <td align="right" style="padding:10px 16px;color:#55607a;">&minus;<?= esc($fmt((int) $order['discount_cents'], (string) $order['currency'])) ?></td>
        </tr>
    <?php endif; ?>
    <?php if ((int) $order['tax_cents'] > 0): ?>
        <tr>
            <td style="padding:10px 16px;color:#55607a;">Tax</td>
            <td align="right" style="padding:10px 16px;color:#55607a;"><?= esc($fmt((int) $order['tax_cents'], (string) $order['currency'])) ?></td>
        </tr>
    <?php endif; ?>
    <tr>
        <td style="padding:12px 16px;font-weight:bold;">Total paid</td>
        <td align="right" style="padding:12px 16px;font-weight:bold;"><?= esc($fmt((int) $order['total_cents'], (string) $order['currency'])) ?></td>
    </tr>
</table>

<p style="margin:0;font-size:14px;color:#55607a;">
    A tax invoice is in the account area under Invoices. Quote
    <?= esc($order['order_no']) ?> in any correspondence about this booking.
</p>
<?php
$body = ob_get_clean();

echo view('Modules\Learning\Views\emails\_layout', [
    'title'  => 'Order ' . $order['order_no'],
    'body'   => $body,
    'school' => $school,
], ['saveData' => false]);
