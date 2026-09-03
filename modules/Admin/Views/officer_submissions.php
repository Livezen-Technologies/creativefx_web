<?php
helper(['url', 'norlanka']);
$this->extend('Modules\Admin\Views\layout');
?>
<?= $this->section('content') ?>

<?= view('Modules\Admin\Views\partials\queue_filter', [
    'base'     => 'admin/officer-submissions',
    'current'  => $status,
    'statuses' => $statuses,
], ['saveData' => false]) ?>

<?php if ($submissions === []): ?>
    <p class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 text-sm text-white/60">Nothing in this queue.</p>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($submissions as $row): ?>
            <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-mono text-xs text-white/50"><?= esc($row['reference']) ?></p>
                        <p class="mt-1 text-base font-semibold"><?= esc($row['subject']) ?></p>
                        <p class="mt-0.5 text-xs text-white/50">
                            <?= esc($row['officer_name'] ?? 'Unknown officer') ?>
                            <?php if (! empty($row['office_name'])): ?> · <?= esc(t_field($row['office_name'])) ?><?php endif; ?>
                            · <?= esc(str_replace('_', ' ', (string) $row['kind'])) ?>
                            · <?= esc(date('j M Y H:i', strtotime((string) $row['created_at']))) ?>
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full bg-brand-red/15 px-3 py-1 text-xs font-semibold capitalize text-brand-red"><?= esc($row['status']) ?></span>
                </div>

                <p class="mt-4 whitespace-pre-line rounded-lg border border-white/10 bg-black/20 p-4 text-sm leading-relaxed text-white/80"><?= esc($row['body']) ?></p>

                <?php if (! empty($row['attachment'])): ?>
                    <p class="mt-3">
                        <a href="<?= site_url('admin/officer-submissions/' . (int) $row['id'] . '/attachment') ?>"
                           class="text-sm font-semibold text-brand-red hover:underline"><?= esc($row['attachment_name'] ?: 'Attachment') ?> &darr;</a>
                    </p>
                <?php endif; ?>

                <form method="post" action="<?= site_url('admin/officer-submissions/' . (int) $row['id']) ?>" class="mt-4 flex flex-wrap items-end gap-3">
                    <?= csrf_field() ?>
                    <label class="min-w-[10rem]">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Status</span>
                        <select name="status" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= esc($s, 'attr') ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="min-w-[16rem] flex-1">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Note back to the officer</span>
                        <input type="text" name="officer_note" value="<?= esc($row['officer_note'], 'attr') ?>" maxlength="500"
                               class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
                    </label>
                    <button type="submit" class="rounded-lg bg-brand-red px-4 py-2 text-sm font-semibold text-white">Save</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
