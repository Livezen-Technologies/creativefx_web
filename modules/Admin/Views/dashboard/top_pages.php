<?php
/**
 * One dashboard widget.
 *
 * Split out of the single dashboard view so the layout can decide which of
 * these appear, in what order and at what width. Each is a self-contained
 * panel: it draws its own card, and knows nothing about the grid around it.
 */
?>
<div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
    <h3 class="mb-4 text-sm font-semibold uppercase tracking-widest text-white/60">Most-viewed pages</h3>
    <?php if ($topPaths === []): ?>
        <p class="text-sm text-white/40">No page views recorded yet — they will appear here as people visit.</p>
    <?php else: ?>
        <?php $topMax = max(1, max(array_column($topPaths, 'views'))); ?>
        <ul class="space-y-3">
            <?php foreach ($topPaths as $t): ?>
                <li>
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="min-w-0 truncate text-white/75"><?= esc($t['label']) ?></span>
                        <span class="shrink-0 tabular-nums text-white/45"><?= number_format($t['views']) ?></span>
                    </div>
                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white/5">
                        <div class="h-full rounded-full bg-brand-red/70" style="width: <?= max(2, (int) round($t['views'] / $topMax * 100)) ?>%"></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="<?= site_url('admin/analytics') ?>" class="mt-4 inline-block text-xs font-semibold text-brand-red hover:underline">Full analytics &rarr;</a>
    <?php endif; ?>
</div>
