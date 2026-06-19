<?php helper('norlanka'); $this->extend('Modules\Admin\Views\layout'); ?>
<?= $this->section('content') ?>
<div class="mb-6 flex items-center justify-between">
    <p class="text-sm text-white/50"><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?></p>
    <?php if ($canCreate): ?>
        <a href="<?= site_url('admin/' . $route . '/new') ?>" class="btn-brand">New <?= esc($singular) ?></a>
    <?php endif; ?>
</div>

<div class="overflow-x-auto rounded-xl border border-white/10">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
            <tr>
                <?php foreach ($listColumns as $col): ?><th class="px-4 py-3"><?= esc($col['label']) ?></th><?php endforeach; ?>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($rows as $row): ?>
                <tr class="hover:bg-white/[0.02]">
                    <?php foreach ($listColumns as $col):
                        $val  = $row[$col['name']] ?? '';
                        $type = $col['type'] ?? 'text'; ?>
                        <td class="px-4 py-3 align-top">
                            <?php if ($type === 'locale'): ?>
                                <?= esc(t_field($val)) ?>
                            <?php elseif ($type === 'badge'): ?>
                                <span class="rounded-full bg-white/10 px-2 py-0.5 text-xs"><?= esc((string) $val) ?></span>
                            <?php else: ?>
                                <?= esc(is_scalar($val) ? mb_strimwidth((string) $val, 0, 80, '…') : '') ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <?php foreach (($extraActions ?? []) as $ea): ?>
                            <a href="<?= site_url('admin/' . str_replace('{id}', (string) $row['id'], $ea['path'])) ?>" class="mr-3 text-white/70 hover:text-white"><?= esc($ea['label']) ?></a>
                        <?php endforeach; ?>
                        <a href="<?= site_url('admin/' . $route . '/' . $row['id'] . '/edit') ?>" class="text-brand-red hover:underline">Edit</a>
                        <?php if ($canDelete): ?>
                            <form method="post" action="<?= site_url('admin/' . $route . '/' . $row['id'] . '/delete') ?>" class="ml-3 inline" onsubmit="return confirm('Delete this <?= esc(strtolower($singular)) ?>?')">
                                <?= csrf_field() ?>
                                <button class="text-white/40 hover:text-brand-red">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="<?= count($listColumns) + 1 ?>" class="px-4 py-10 text-center text-white/40">No records yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
