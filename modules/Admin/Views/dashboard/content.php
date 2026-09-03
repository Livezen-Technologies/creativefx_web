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
<div class="rounded-2xl border border-white/10 bg-white/[0.02]">
    <div class="border-b border-white/10 px-6 py-4">
        <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Recently updated content</h3>
    </div>
    <?php if ($content === []): ?>
        <p class="px-6 py-10 text-center text-sm text-white/40">Nothing here yet.</p>
    <?php else: ?>
        <ul class="divide-y divide-white/5">
            <?php foreach ($content as $c):
                $href = $c['kind'] === 'news'
                    ? site_url('admin/news-posts/' . $c['id'] . '/edit')
                    : site_url('admin/pages/' . $c['id'] . '/content'); ?>
                <li>
                    <a href="<?= $href ?>" class="flex items-center gap-4 px-6 py-3 transition hover:bg-white/[0.03]">
                        <span class="rounded-md bg-white/8 px-2 py-1 text-[10px] font-bold uppercase tracking-widest text-white/50"><?= esc($c['kind']) ?></span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium"><?= esc(t_field(json_decode($c['title'] ?? '[]', true) ?: []) ?: $c['slug']) ?></span>
                        <span class="hidden text-xs text-white/40 sm:block"><?= esc($c['updated_at'] ? date('j M Y, H:i', strtotime($c['updated_at'])) : '') ?></span>
                        <?= admin_chip((string) $c['status']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
