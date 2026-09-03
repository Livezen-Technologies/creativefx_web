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
<div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
    <h3 class="mb-3 text-sm font-semibold uppercase tracking-widest text-white/60">The site</h3>
    <ul class="space-y-2.5 text-sm">
        <li class="flex justify-between"><span class="text-white/55">Media storage</span><span class="font-semibold tabular-nums"><?= esc(admin_bytes($storage['bytes'])) ?> · <?= esc($storage['files']) ?> files</span></li>
        <li class="flex justify-between"><span class="text-white/55">Pages</span><span class="font-semibold tabular-nums"><?= esc($counts['pages']) ?></span></li>
        <li class="flex justify-between"><span class="text-white/55">Rooms</span><span class="font-semibold tabular-nums"><?= esc($counts['rooms']) ?></span></li>
        <li class="flex justify-between"><span class="text-white/55">Tourist locations</span><span class="font-semibold tabular-nums"><?= esc($counts['locations']) ?></span></li>
        <li class="flex justify-between"><span class="text-white/55">Menu items</span><span class="font-semibold tabular-nums"><?= esc($counts['menu']) ?></span></li>
    </ul>
</div>
