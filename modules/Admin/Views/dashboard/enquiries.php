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
    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
        <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Recent contact messages</h3>
        <?php if (($counts['newMsgs'] ?? 0) > 0): ?>
            <span class="rounded-full bg-brand-red/15 px-2.5 py-0.5 text-[11px] font-bold text-brand-red"><?= esc($counts['newMsgs']) ?> new</span>
        <?php endif; ?>
    </div>
    <?php if ($recent === []): ?>
        <p class="px-6 py-10 text-center text-sm text-white/40">No messages yet.</p>
    <?php else: ?>
        <ul class="divide-y divide-white/5">
            <?php foreach ($recent as $r): ?>
                <li>
                    <a href="<?= site_url('admin/contacts/' . $r['id'] . '/edit') ?>" class="flex items-center gap-4 px-6 py-3.5 transition hover:bg-white/[0.03]">
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/8 text-sm font-bold text-white/70"><?= esc(strtoupper(mb_substr((string) $r['name'], 0, 1))) ?></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium"><?= esc($r['name']) ?> <span class="font-normal text-white/40">· <?= esc($r['email']) ?></span></span>
                            <span class="block truncate text-xs text-white/45"><?= esc($r['subject'] ?: '—') ?></span>
                        </span>
                        <?= admin_chip((string) $r['status']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
