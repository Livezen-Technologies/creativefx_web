<?php
helper('admin');

/**
 * One dashboard widget.
 *
 * Split out of the single dashboard view so the layout can decide which of
 * these appear, in what order and at what width. Each is a self-contained
 * panel: it draws its own card, and knows nothing about the grid around it.
 */
?>
<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
    <?php foreach ($kpis as $k): ?>
        <a href="<?= site_url($k['path']) ?>" class="group rounded-2xl border border-white/10 bg-white/[0.02] p-5 transition hover:border-brand-red/50 hover:bg-white/[0.04]">
            <div class="flex items-center justify-between">
                <span class="text-white/35 transition group-hover:text-brand-red"><?= admin_icon($k['icon']) ?></span>
                <?php if (($k['week'] ?? null) !== null && $k['week'] > 0): ?>
                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-300">+<?= esc($k['week']) ?> this week</span>
                <?php endif; ?>
            </div>
            <div class="mt-3 text-3xl font-bold tabular-nums"><?= esc(number_format($k['total'])) ?></div>
            <div class="mt-1 text-xs font-medium text-white/50"><?= esc($k['label']) ?></div>
        </a>
    <?php endforeach; ?>
</div>
