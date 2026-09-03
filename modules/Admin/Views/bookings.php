<?php
helper(['url', 'norlanka']);
$this->extend('Modules\Admin\Views\layout');
?>
<?= $this->section('content') ?>

<?= view('Modules\Admin\Views\partials\queue_filter', [
    'base'     => 'admin/bookings',
    'current'  => $status,
    'statuses' => $statuses,
    'counts'   => $counts,
    'extra'    => ['programme' => $programmeId],
], ['saveData' => false]) ?>

<form method="get" class="mb-6 flex flex-wrap items-end gap-3">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= esc($status, 'attr') ?>"><?php endif; ?>
    <label class="min-w-[16rem]">
        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Programme</span>
        <select name="programme" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
            <option value="">All programmes</option>
            <?php foreach ($programmes as $programme): ?>
                <option value="<?= (int) $programme['id'] ?>" <?= $programmeId === (int) $programme['id'] ? 'selected' : '' ?>>
                    <?= esc(t_field($programme['title'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="rounded-lg bg-brand-red px-4 py-2 text-sm font-semibold text-white">Filter</button>
</form>

<?php if ($bookings === []): ?>
    <p class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 text-sm text-white/60">Nothing in this queue.</p>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($bookings as $booking): ?>
            <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-mono text-xs text-white/50"><?= esc($booking['reference']) ?></p>
                        <p class="mt-1 text-base font-semibold"><?= esc($booking['name']) ?></p>
                        <p class="mt-0.5 text-sm text-white/60"><?= esc(t_field($booking['programme_title'] ?? '')) ?></p>
                    </div>
                    <span class="shrink-0 rounded-full bg-brand-red/15 px-3 py-1 text-xs font-semibold capitalize text-brand-red"><?= esc($booking['status']) ?></span>
                </div>

                <dl class="mt-4 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <?php foreach ([
                        'Telephone'    => $booking['phone'],
                        'Email'        => $booking['email'],
                        'NIC'          => $booking['nic'],
                        'District'     => $booking['district'],
                        'Society'      => $booking['society'],
                        'Participants' => $booking['participants'],
                        'Residential'  => (int) $booking['residential'] === 1 ? 'Yes' : 'No',
                        'Received'     => date('j M Y', strtotime((string) $booking['created_at'])),
                    ] as $label => $value): ?>
                        <?php if (trim((string) $value) === '') { continue; } ?>
                        <div>
                            <dt class="text-xs uppercase tracking-widest text-white/40"><?= esc($label) ?></dt>
                            <dd class="mt-0.5 break-words text-white/80"><?= esc((string) $value) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>

                <?php if (! empty($booking['notes'])): ?>
                    <p class="mt-4 rounded-lg border border-white/10 bg-black/20 p-3 text-sm leading-relaxed text-white/70"><?= nl2br(esc($booking['notes'])) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= site_url('admin/bookings/' . (int) $booking['id']) ?>" class="mt-4 flex flex-wrap items-end gap-3">
                    <?= csrf_field() ?>
                    <label class="min-w-[10rem]">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Decision</span>
                        <select name="status" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= esc($s, 'attr') ?>" <?= $booking['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="min-w-[16rem] flex-1">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Note to the applicant</span>
                        <input type="text" name="officer_note" value="<?= esc($booking['officer_note'], 'attr') ?>" maxlength="500"
                               class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
                    </label>
                    <button type="submit" class="rounded-lg bg-brand-red px-4 py-2 text-sm font-semibold text-white">Save & notify</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
