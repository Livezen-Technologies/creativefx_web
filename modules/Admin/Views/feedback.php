<?php
helper(['url', 'norlanka']);
$this->extend('Modules\Admin\Views\layout');
?>
<?= $this->section('content') ?>

<?php // The closed-loop counters (Clause 3.14). Overdue is the one that matters:
      // it is the only number here that says something is wrong. ?>
<div class="mb-6 grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <?php foreach ([
        'open'     => ['Open', false],
        'assigned' => ['With an officer', false],
        'answered' => ['Answered', false],
        'closed'   => ['Closed', false],
        'overdue'  => ['Overdue', true],
    ] as $key => [$label, $alarm]): ?>
        <div class="rounded-2xl border p-5 <?= $alarm && $stats[$key] > 0 ? 'border-brand-red bg-brand-red/10' : 'border-white/10 bg-white/[0.02]' ?>">
            <p class="text-xs uppercase tracking-widest text-white/45"><?= esc($label) ?></p>
            <p class="mt-1.5 text-2xl font-bold <?= $alarm && $stats[$key] > 0 ? 'text-brand-red' : '' ?>"><?= esc((string) $stats[$key]) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<form method="get" class="mb-6 flex flex-wrap items-end gap-3">
    <label class="min-w-[14rem] flex-1">
        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Search</span>
        <input type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Reference, name or subject"
               class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
    </label>
    <label class="min-w-[10rem]">
        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Status</span>
        <select name="status" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
            <option value="">All</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="min-w-[10rem]">
        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Type</span>
        <select name="kind" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
            <option value="">All</option>
            <?php foreach (['feedback', 'query', 'petition', 'complaint'] as $k): ?>
                <option value="<?= esc($k, 'attr') ?>" <?= $filters['kind'] === $k ? 'selected' : '' ?>><?= esc(ucfirst($k)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="rounded-lg bg-brand-red px-4 py-2 text-sm font-semibold text-white">Filter</button>
</form>

<?php if ($submissions === []): ?>
    <p class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 text-sm text-white/60">Nothing matches those filters.</p>
<?php else: ?>
    <div class="overflow-x-auto rounded-2xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">From</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Due</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $row):
                    $overdue = in_array($row['status'], ['open', 'assigned'], true)
                        && ! empty($row['due_at']) && $row['due_at'] < date('Y-m-d H:i:s');
                ?>
                    <tr class="border-t border-white/5 align-top">
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="<?= site_url('admin/feedback/' . (int) $row['id']) ?>" class="text-brand-red hover:underline"><?= esc($row['reference']) ?></a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= site_url('admin/feedback/' . (int) $row['id']) ?>" class="font-medium hover:text-brand-red"><?= esc($row['subject']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-white/70">
                            <?= esc($row['name']) ?>
                            <?php if (! empty($row['district'])): ?><span class="block text-xs text-white/40"><?= esc($row['district']) ?></span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 capitalize text-white/70"><?= esc($row['kind']) ?></td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-brand-red/15 px-2.5 py-0.5 text-xs font-semibold capitalize text-brand-red"><?= esc($row['status']) ?></span>
                        </td>
                        <td class="px-4 py-3 <?= $overdue ? 'font-semibold text-brand-red' : 'text-white/60' ?>">
                            <?= empty($row['due_at']) ? '—' : esc(date('j M Y', strtotime((string) $row['due_at']))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
