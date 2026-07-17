<?php helper(['url', 'norlanka']); $this->extend('Modules\Admin\Views\layout'); ?>
<?= $this->section('content') ?>

<!-- Pipeline tabs -->
<div class="mb-6 flex flex-wrap items-center gap-2">
    <a href="<?= site_url('admin/applications') ?>"
       class="rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest transition <?= $current === '' ? 'bg-brand-red text-white' : 'bg-white/5 text-white/60 hover:text-white' ?>">
        All (<?= array_sum($counts) ?>)
    </a>
    <?php foreach ($statuses as $s): ?>
        <a href="<?= site_url('admin/applications') . '?status=' . $s ?>"
           class="rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest transition <?= $current === $s ? 'bg-brand-red text-white' : 'bg-white/5 text-white/60 hover:text-white' ?>">
            <?= esc(ucfirst($s)) ?> (<?= $counts[$s] ?>)
        </a>
    <?php endforeach; ?>
    <a href="<?= site_url('admin/applications/export') . ($current !== '' ? '?status=' . $current : '') ?>"
       class="ml-auto rounded-lg border border-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest hover:border-white">Export CSV</a>
</div>

<div class="overflow-hidden rounded-xl border border-white/10">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
            <tr>
                <th class="px-4 py-3">Applicant</th>
                <th class="px-4 py-3">Position</th>
                <th class="hidden px-4 py-3 md:table-cell">Applied</th>
                <th class="px-4 py-3">Rating</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($rows as $r): ?>
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-4 py-3">
                        <a href="<?= site_url('admin/applications/' . $r['id']) ?>" class="font-medium hover:text-brand-red"><?= esc($r['name']) ?></a>
                        <div class="text-xs text-white/45"><?= esc($r['email']) ?></div>
                    </td>
                    <td class="px-4 py-3 text-white/70"><?= esc(t_field(json_decode($r['job_title'] ?? '[]', true) ?: [])) ?></td>
                    <td class="hidden px-4 py-3 text-white/50 md:table-cell"><?= esc(date('j M Y', strtotime($r['created_at']))) ?></td>
                    <td class="px-4 py-3 text-amber-400"><?= $r['rating'] ? str_repeat('★', (int) $r['rating']) : '<span class="text-white/25">—</span>' ?></td>
                    <td class="px-4 py-3"><span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs capitalize"><?= esc($r['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="px-4 py-10 text-center text-white/40">No applications<?= $current !== '' ? ' in "' . esc($current) . '"' : '' ?> yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
