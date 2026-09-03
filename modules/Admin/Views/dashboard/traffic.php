<?php
/**
 * One dashboard widget.
 *
 * Split out of the single dashboard view so the layout can decide which of
 * these appear, in what order and at what width. Each is a self-contained
 * panel: it draws its own card, and knows nothing about the grid around it.
 */
?>
<?php // Visits and visitors, which is what a hotel looks at first. This was
      // applications and contact messages — a careers pipeline the hotel
      // does not run, so it drew two rows of zeros under a legend. ?>
<div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 xl:col-span-2">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Traffic — last 14 days</h3>
            <p class="mt-2 flex flex-wrap items-baseline gap-x-4 gap-y-1">
                <span class="text-2xl font-bold"><?= number_format($traffic['views']) ?></span>
                <span class="text-xs uppercase tracking-widest text-white/45">page views</span>
                <span class="text-2xl font-bold"><?= number_format($traffic['visitors']) ?></span>
                <span class="text-xs uppercase tracking-widest text-white/45">visitors</span>
                <?php // A change against the previous fortnight, shown only
                      // when there is a previous fortnight to compare to. ?>
                <?php if ($traffic['views_change'] !== null && $traffic['views_prev'] > 0): ?>
                    <span class="text-xs font-semibold <?= $traffic['views_change'] >= 0 ? 'text-emerald-400' : 'text-brand-red' ?>">
                        <?= $traffic['views_change'] >= 0 ? '+' : '' ?><?= esc($traffic['views_change']) ?>% vs the fortnight before
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex items-center gap-4 text-[11px] text-white/50">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-red"></span> Page views</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-white/35"></span> Visitors</span>
        </div>
    </div>
    <?php
    $max = 1;
    foreach ($days as $d) { $max = max($max, $d['views'], $d['visitors']); }
    ?>
    <div class="flex h-40 items-end gap-1.5 sm:gap-2.5">
        <?php foreach ($days as $d): ?>
            <div class="group relative flex h-full flex-1 items-end justify-center gap-[3px]"
                 title="<?= esc($d['label'] . ': ' . $d['views'] . ' page views, ' . $d['visitors'] . ' visitors', 'attr') ?>">
                <?php // A bar for a day with nothing in it is drawn at zero
                      // height, not at a 4% minimum: a floor makes an empty
                      // fortnight look like a quiet one. ?>
                <div class="w-1/2 max-w-[14px] rounded-t bg-brand-red/80 transition group-hover:bg-brand-red"
                     style="height: <?= $d['views'] > 0 ? max(4, (int) round($d['views'] / $max * 100)) : 0 ?>%"></div>
                <div class="w-1/2 max-w-[14px] rounded-t bg-white/25 transition group-hover:bg-white/45"
                     style="height: <?= $d['visitors'] > 0 ? max(4, (int) round($d['visitors'] / $max * 100)) : 0 ?>%"></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-2 flex justify-between text-[10px] text-white/35">
        <span><?= esc($days[0]['label'] ?? '') ?></span>
        <span><?= esc($days[(int) (count($days) / 2)]['label'] ?? '') ?></span>
        <span>Today</span>
    </div>

    <?php // Today and this month beside the chart, and the two things a
          // visitor can do that are worth counting. ?>
    <div class="mt-6 grid grid-cols-2 gap-4 border-t border-white/10 pt-5 sm:grid-cols-4">
        <?php foreach ([
            ['Today', number_format($today['views']), number_format($today['visitors']) . ' visitors'],
            ['Enquiries', number_format($traffic['bookings'] + $traffic['messages']), 'in 14 days'],
            ['WhatsApp clicks', number_format($traffic['whatsapp']), 'in 14 days'],
            ['Conversion', $traffic['conversion'] . '%', 'enquiries per visitor'],
        ] as [$label, $value, $note]): ?>
            <div>
                <p class="text-[10px] uppercase tracking-widest text-white/40"><?= esc($label) ?></p>
                <p class="mt-1 text-xl font-bold"><?= esc($value) ?></p>
                <p class="text-[11px] text-white/35"><?= esc($note) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <a href="<?= site_url('admin/analytics') ?>" class="mt-5 inline-block text-xs font-semibold text-brand-red hover:underline">
        Full analytics &rarr;
    </a>
</div>
