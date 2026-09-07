<?php helper(['url', 'norlanka', 'commerce']); $this->extend('Modules\Admin\Views\layout');

/**
 * The order book.
 *
 * The status cards are the filter as well as the totals, on purpose. A separate
 * row of chips and a separate row of figures would say the same thing twice and
 * let the two drift; here the thing you look at to see how much is awaiting
 * payment is the thing you click to go and chase it.
 *
 * Every figure is per currency and none of them are added together — see
 * Orders::summary(). Two lines under "Awaiting payment" is not a rendering
 * accident, it is the site selling in two currencies.
 */

$labels = [
    'pending_payment'    => 'Awaiting payment',
    'paid'               => 'Paid',
    'partially_refunded' => 'Part refunded',
    'refunded'           => 'Refunded',
    'cancelled'          => 'Cancelled',
    'failed'             => 'Failed',
];

// Coloured by what a status means for the business rather than by its name:
// money still to come is the one worth looking at, money gone back is the one
// worth noticing, and everything settled is quiet.
$tones = [
    'pending_payment'    => 'text-amber-300',
    'paid'               => 'text-emerald-300',
    'partially_refunded' => 'text-amber-300',
    'refunded'           => 'text-white/50',
    'cancelled'          => 'text-white/40',
    'failed'             => 'text-brand-red',
];

$link = static fn (?string $status): string => site_url('admin/orders')
    . ($status === null || $status === '' ? '' : '?status=' . $status);

// The buyer as the order froze them. billing_json is the snapshot taken at
// checkout and is what the invoice carries, so it wins over the user row, which
// somebody may have edited since.
$buyer = static function (array $row): array {
    $billing = json_decode((string) ($row['billing_json'] ?? ''), true);
    $billing = is_array($billing) ? $billing : [];
    $name    = trim((string) ($billing['name'] ?? '')) ?: trim(($row['user_first'] ?? '') . ' ' . ($row['user_last'] ?? ''));
    $email   = trim((string) ($billing['email'] ?? '')) ?: (string) ($row['user_email'] ?? '');

    return ['name' => $name !== '' ? $name : ($email !== '' ? $email : 'Guest'), 'email' => $email, 'company' => (string) ($billing['company'] ?? '')];
};

$allCount = array_sum(array_column($summary, 'count'));
?>
<?= $this->section('content') ?>

<!-- Status totals, which are also the filter -->
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
    <a href="<?= esc($link(null), 'attr') ?>"
       class="rounded-xl border p-4 transition <?= $current === '' ? 'border-brand-red bg-brand-red/10' : 'border-white/10 bg-white/[0.02] hover:border-white/25' ?>">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-white/45">All orders</p>
        <p class="mt-1.5 text-2xl font-bold"><?= esc((string) $allCount) ?></p>
    </a>
    <?php foreach ($statuses as $s):
        $card = $summary[$s] ?? ['count' => 0, 'money' => []]; ?>
        <a href="<?= esc($link($s), 'attr') ?>"
           class="rounded-xl border p-4 transition <?= $current === $s ? 'border-brand-red bg-brand-red/10' : 'border-white/10 bg-white/[0.02] hover:border-white/25' ?>">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-white/45"><?= esc($labels[$s] ?? $s) ?></p>
            <p class="mt-1.5 text-2xl font-bold <?= $card['count'] > 0 ? esc($tones[$s] ?? 'text-white') : 'text-white/25' ?>"><?= esc((string) $card['count']) ?></p>
            <?php foreach ($card['money'] as $currency => $cents): ?>
                <p class="mt-0.5 text-xs text-white/50"><?= esc(money((int) $cents, (string) $currency)) ?></p>
            <?php endforeach; ?>
            <?php if ($card['money'] === []): ?>
                <p class="mt-0.5 text-xs text-white/25">—</p>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="overflow-x-auto rounded-xl border border-white/10">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
            <tr>
                <th class="px-4 py-3">Order</th>
                <th class="px-4 py-3">Buyer</th>
                <th class="hidden px-4 py-3 md:table-cell">Placed</th>
                <th class="hidden px-4 py-3 sm:table-cell">Gateway</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($rows as $r): $b = $buyer($r); ?>
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-4 py-3">
                        <a href="<?= site_url('admin/orders/' . $r['id']) ?>" class="font-mono text-[13px] font-medium hover:text-brand-red"><?= esc($r['order_no']) ?></a>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium"><?= esc($b['name']) ?></div>
                        <div class="text-xs text-white/45"><?= esc($b['company'] !== '' ? $b['company'] . ' · ' . $b['email'] : $b['email']) ?></div>
                    </td>
                    <td class="hidden whitespace-nowrap px-4 py-3 text-white/50 md:table-cell">
                        <?= esc($r['placed_at'] ? date('j M Y, H:i', strtotime((string) $r['placed_at'])) : '—') ?>
                    </td>
                    <td class="hidden px-4 py-3 text-white/60 sm:table-cell"><?= esc($r['gateway'] ?: '—') ?></td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <span class="font-medium"><?= esc(money((int) $r['total_cents'], (string) $r['currency'])) ?></span>
                        <span class="ml-1 text-xs text-white/40"><?= esc(strtoupper((string) $r['currency'])) ?></span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold <?= esc($tones[$r['status']] ?? 'text-white/60') ?>">
                            <?= esc($labels[$r['status']] ?? $r['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-white/40">
                        <?php if ($current !== ''): ?>
                            No orders are <?= esc(strtolower($labels[$current] ?? $current)) ?>.
                        <?php else: ?>
                            No orders yet. The first one will appear here the moment somebody reaches the end of the checkout.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php // Older / newer rather than numbered pages: the controller asks for one
      // row more than it shows to find out whether there is a next page, which
      // is a cheaper question than "how many pages are there". ?>
<?php if ($hasMore || $page > 1): ?>
    <div class="mt-4 flex items-center justify-between text-xs">
        <?php if ($page > 1): ?>
            <a href="<?= esc($link($current) . ($current === '' ? '?' : '&') . 'page=' . ($page - 1), 'attr') ?>"
               class="rounded-lg border border-white/15 px-4 py-2 uppercase tracking-widest text-white/70 hover:border-white">← Newer</a>
        <?php else: ?><span></span><?php endif; ?>
        <span class="text-white/35">Page <?= esc((string) $page) ?></span>
        <?php if ($hasMore): ?>
            <a href="<?= esc($link($current) . ($current === '' ? '?' : '&') . 'page=' . ($page + 1), 'attr') ?>"
               class="rounded-lg border border-white/15 px-4 py-2 uppercase tracking-widest text-white/70 hover:border-white">Older →</a>
        <?php else: ?><span></span><?php endif; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
