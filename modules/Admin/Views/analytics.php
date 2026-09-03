<?php
helper(['url']);
$this->extend('Modules\Admin\Views\layout');

$bar = static function (array $rows, string $key = 'views'): int {
    $max = 1;
    foreach ($rows as $r) { $max = max($max, (int) $r[$key]); }
    return $max;
};
$delta = static function (?float $change): string {
    if ($change === null) { return ''; }
    $cls = $change >= 0 ? 'text-emerald-400' : 'text-brand-red';
    return '<span class="text-xs font-semibold ' . $cls . '">' . ($change >= 0 ? '+' : '') . esc($change) . '%</span>';
};
?>
<?= $this->section('content') ?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-1.5">
        <?php foreach ($ranges as $key => $label): ?>
            <a href="<?= site_url('admin/analytics?range=' . $key) ?>"
               class="rounded-lg px-3 py-1.5 text-sm transition <?= (string) $range === (string) $key
                   ? 'bg-white/15 font-semibold text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' ?>">
                <?= esc($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php // Wraps rather than scrolls: two date fields, an Apply and an Export
          // do not fit beside each other on a phone, and a row that runs off
          // the side of the screen is one whose last control cannot be reached. ?>
    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:gap-3">
        <form method="get" action="<?= site_url('admin/analytics') ?>" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="date" name="from" value="<?= esc($from, 'attr') ?>" aria-label="From"
                   class="min-w-0 flex-1 rounded-lg border border-white/15 bg-black/30 px-2 py-1.5 text-white sm:flex-none">
            <span class="text-white/40">to</span>
            <input type="date" name="to" value="<?= esc($to, 'attr') ?>" aria-label="To"
                   class="min-w-0 flex-1 rounded-lg border border-white/15 bg-black/30 px-2 py-1.5 text-white sm:flex-none">
            <button class="rounded-lg border border-white/15 px-3 py-1.5 font-semibold text-white/75 transition hover:border-white/40 hover:text-white">Apply</button>
        </form>
        <a href="<?= site_url('admin/analytics/export?range=' . $range . '&from=' . $from . '&to=' . $to) ?>"
           class="rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/75 transition hover:border-white/40 hover:text-white">
            Export CSV
        </a>
    </div>
</div>

<?php // Summary cards. Each carries its change against the period immediately
      // before this one, which is the only comparison that is meaningful
      // without knowing what the hotel was doing at the time. ?>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <?php foreach ([
        ['Page views', number_format($summary['views']), $summary['views_change']],
        ['Visitors', number_format($summary['visitors']), $summary['visitors_change']],
        ['Booking requests', number_format($summary['bookings']), $summary['bookings_change']],
        ['Messages', number_format($summary['messages']), $summary['messages_change']],
        ['WhatsApp clicks', number_format($summary['whatsapp']), $summary['whatsapp_change']],
    ] as [$label, $value, $change]): ?>
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-5">
            <p class="text-[10px] uppercase tracking-widest text-white/40"><?= esc($label) ?></p>
            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold"><?= esc($value) ?></span>
                <?= $delta($change) ?>
            </p>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-4 rounded-2xl border border-white/10 bg-white/[0.02] p-5">
    <p class="text-[10px] uppercase tracking-widest text-white/40">Conversion</p>
    <p class="mt-2 text-2xl font-bold"><?= esc($summary['conversion']) ?>%</p>
    <p class="mt-1 text-xs leading-relaxed text-white/45">
        Enquiries — booking requests and messages together — as a share of visitors.
        Compared with <?= esc(date('j M', strtotime($summary['prev_from']))) ?>–<?= esc(date('j M', strtotime($summary['prev_to']))) ?>.
    </p>
</div>

<!-- Traffic over the range -->
<div class="mt-6 rounded-2xl border border-white/10 bg-white/[0.02] p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Traffic</h3>
        <div class="flex items-center gap-4 text-[11px] text-white/50">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-red"></span> Page views</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-white/35"></span> Visitors</span>
        </div>
    </div>
    <?php $max = $bar($days); ?>
    <div class="flex h-48 items-end gap-[2px] overflow-x-auto sm:gap-1">
        <?php foreach ($days as $d): ?>
            <div class="group flex h-full min-w-[6px] flex-1 items-end justify-center gap-[2px]"
                 title="<?= esc($d['label'] . ': ' . $d['views'] . ' page views, ' . $d['visitors'] . ' visitors', 'attr') ?>">
                <div class="w-1/2 max-w-[12px] rounded-t bg-brand-red/80 transition group-hover:bg-brand-red"
                     style="height: <?= $d['views'] > 0 ? max(3, (int) round($d['views'] / $max * 100)) : 0 ?>%"></div>
                <div class="w-1/2 max-w-[12px] rounded-t bg-white/25 transition group-hover:bg-white/45"
                     style="height: <?= $d['visitors'] > 0 ? max(3, (int) round($d['visitors'] / $max * 100)) : 0 ?>%"></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-2 flex justify-between text-[10px] text-white/35">
        <span><?= esc($days[0]['label'] ?? '') ?></span>
        <span><?= esc($days[(int) (count($days) / 2)]['label'] ?? '') ?></span>
        <span><?= esc(end($days)['label'] ?? '') ?></span>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <?php
    $panels = [
        ['Most-viewed pages', $paths, 'No page views in this period yet.'],
        ['Where people came from', $referrers, 'Nothing recorded yet.'],
        ['Devices', $devices, 'Nothing recorded yet.'],
        ['Browsers', $browsers, 'Nothing recorded yet.'],
        ['Languages', $locales, 'Nothing recorded yet.'],
    ];
    foreach ($panels as [$heading, $rows, $empty]): $max = $bar($rows); ?>
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-widest text-white/60"><?= esc($heading) ?></h3>
            <?php if ($rows === []): ?>
                <p class="text-sm text-white/40"><?= esc($empty) ?></p>
            <?php else: ?>
                <ul class="space-y-2.5">
                    <?php foreach ($rows as $r): ?>
                        <li>
                            <div class="flex items-baseline justify-between gap-4 text-sm">
                                <span class="min-w-0 truncate text-white/80"><?= esc($r['label']) ?></span>
                                <span class="shrink-0 tabular-nums text-white/50"><?= number_format($r['views']) ?></span>
                            </div>
                            <?php // The bar is the comparison; the number is the
                                  // fact. Both, because a bar alone cannot be
                                  // read precisely and a number alone cannot be
                                  // scanned. ?>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-brand-red/70" style="width: <?= max(2, (int) round($r['views'] / $max * 100)) ?>%"></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php // Said plainly rather than shown as an empty panel that looks broken. ?>
    <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-widest text-white/60">Countries and cities</h3>
        <p class="text-sm leading-relaxed text-white/45">
            Not collected. Working out where a visitor is requires either their IP address, which this site
            deliberately does not store, or sending each visit to a third-party lookup service the visitor
            never agreed to.
            <?php if ($ga4 !== ''): ?>
                Google Analytics is configured (<?= esc($ga4) ?>) and reports geography in its own console.
            <?php else: ?>
                Adding a Google Analytics 4 ID under Settings → SEO and analytics would report it there.
            <?php endif; ?>
        </p>
    </div>
</div>

<p class="mt-6 text-xs leading-relaxed text-white/35">
    Measured on this server, with no third-party script on the public site. A visitor is counted by a hash of
    their address and browser that changes every day, so nobody can be identified or followed between days —
    which also means a visitor seen on two days counts twice over a longer range. Requests sending Do Not Track
    or Global Privacy Control, and known bots, are not recorded at all.
</p>

<?= $this->endSection() ?>
