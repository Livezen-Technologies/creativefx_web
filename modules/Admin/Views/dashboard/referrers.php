<?php
/**
 * One dashboard widget.
 *
 * Split out of the single dashboard view so the layout can decide which of
 * these appear, in what order and at what width. Each is a self-contained
 * panel: it draws its own card, and knows nothing about the grid around it.
 */
?>
<?php // Was a recent-applications feed for a careers portal this site does
      // not run. Enquiries are what actually arrives here. ?>
<div class="rounded-2xl border border-white/10 bg-white/[0.02]">
    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
        <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Where visitors came from</h3>
        <a href="<?= site_url('admin/analytics') ?>" class="text-xs font-semibold text-brand-red hover:underline">Analytics</a>
    </div>
    <?php if ($referrers === []): ?>
        <p class="px-6 py-10 text-center text-sm text-white/40">Nothing recorded yet.</p>
    <?php else: ?>
        <ul class="divide-y divide-white/5">
            <?php foreach ($referrers as $r): ?>
                <li class="flex items-center justify-between gap-4 px-6 py-3.5">
                    <span class="min-w-0 truncate text-sm text-white/75"><?= esc($r['label']) ?></span>
                    <span class="shrink-0 text-sm tabular-nums text-white/45"><?= number_format($r['visitors']) ?> visitors</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Recent messages -->
