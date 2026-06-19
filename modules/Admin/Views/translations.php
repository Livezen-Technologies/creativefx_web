<?php
$this->extend('Modules\Admin\Views\layout');
$inputCls = 'w-full rounded-lg border border-white/15 bg-black/40 px-2.5 py-1.5 text-sm focus:border-brand-red focus:outline-none';
?>
<?= $this->section('content') ?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs uppercase tracking-widest text-white/40">Group</span>
        <?php foreach ($groups as $g): ?>
            <a href="<?= site_url('admin/translations?group=' . urlencode($g)) ?>"
               class="rounded-full px-3 py-1 text-xs <?= $g === $group ? 'bg-brand-red text-white' : 'bg-white/5 text-white/70 hover:text-white' ?>"><?= esc($g) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="post" action="<?= site_url('admin/translations/import') ?>" onsubmit="return confirm('Import UI strings from language files? Existing edits are preserved.')">
        <?= csrf_field() ?>
        <button class="btn-ghost">Import UI strings</button>
    </form>
</div>

<form method="post" action="<?= site_url('admin/translations') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="<?= esc($group, 'attr') ?>">

    <div class="overflow-x-auto rounded-xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
                <tr>
                    <th class="px-4 py-3">Key</th>
                    <?php foreach ($locales as $l): ?><th class="px-4 py-3"><?= esc($l) ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php $i = 0; foreach ($matrix as $key => $byLocale): ?>
                    <tr class="align-top hover:bg-white/[0.02]">
                        <td class="px-4 py-2 font-mono text-xs text-white/60">
                            <?= esc($key) ?>
                            <input type="hidden" name="items[<?= $i ?>][key]" value="<?= esc($key, 'attr') ?>">
                        </td>
                        <?php foreach ($locales as $l): ?>
                            <td class="px-4 py-2">
                                <input type="text" name="items[<?= $i ?>][<?= $l ?>]" value="<?= esc($byLocale[$l] ?? '') ?>" class="<?= $inputCls ?>">
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php $i++; endforeach; ?>
                <?php if ($matrix === []): ?>
                    <tr><td colspan="<?= count($locales) + 1 ?>" class="px-4 py-10 text-center text-white/40">No strings in this group. Use “Import UI strings”, or add one below.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        <button class="btn-brand">Save translations</button>
    </div>
</form>

<!-- Add a new key -->
<form method="post" action="<?= site_url('admin/translations/add') ?>" class="mt-10 rounded-xl border border-white/10 bg-white/[0.02] p-5">
    <?= csrf_field() ?>
    <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-white/50">Add a translation key</h2>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <input type="text" name="group" value="<?= esc($group, 'attr') ?>" placeholder="Group" class="<?= $inputCls ?>">
        <input type="text" name="key" placeholder="key.path" class="<?= $inputCls ?>">
        <?php foreach ($locales as $l): ?>
            <input type="text" name="<?= $l ?>" placeholder="<?= esc($l) ?>" class="<?= $inputCls ?>">
        <?php endforeach; ?>
    </div>
    <button class="btn-ghost mt-4">Add key</button>
</form>

<p class="mt-6 text-xs text-white/40">Edits here override the file-based language strings immediately (e.g. <code>Site.nav.story</code> → the main navigation).</p>
<?= $this->endSection() ?>
