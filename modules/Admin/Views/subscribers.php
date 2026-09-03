<?php
helper(['url', 'norlanka']);
$this->extend('Modules\Admin\Views\layout');
?>
<?= $this->section('content') ?>

<div class="mb-6 grid gap-4 sm:grid-cols-4">
    <?php foreach (['active' => 'Confirmed', 'pending' => 'Awaiting confirmation', 'unsubscribed' => 'Unsubscribed', 'bounced' => 'Bouncing'] as $key => $label): ?>
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-5">
            <p class="text-xs uppercase tracking-widest text-white/45"><?= esc($label) ?></p>
            <p class="mt-1.5 text-2xl font-bold"><?= esc((string) ($stats[$key] ?? 0)) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="mb-6 flex flex-wrap items-center gap-3">
    <?= view('Modules\Admin\Views\partials\queue_filter', [
        'base'     => 'admin/subscribers',
        'current'  => $status,
        'statuses' => ['active', 'pending', 'unsubscribed', 'bounced'],
        'counts'   => $stats,
    ], ['saveData' => false]) ?>
    <a href="<?= site_url('admin/subscribers/export') ?>"
       class="mb-6 rounded-lg border border-white/10 px-4 py-1.5 text-sm font-semibold transition hover:border-brand-red hover:text-brand-red">
        Export the confirmed list
    </a>
</div>

<p class="mb-5 max-w-3xl text-sm leading-relaxed text-white/50">
    There is no way to add a subscriber here, deliberately. Every address on this list proved itself by following a
    confirmation link; one typed in by an officer has not, and adding it would put the Authority in the position of
    mailing somebody who never asked.
</p>

<?php if ($subscribers === []): ?>
    <p class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 text-sm text-white/60">No subscribers match that.</p>
<?php else: ?>
    <div class="overflow-x-auto rounded-2xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
                <tr>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Topics</th>
                    <th class="px-4 py-3">Language</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Confirmed</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $row): ?>
                    <tr class="border-t border-white/5">
                        <td class="px-4 py-3 break-words"><?= esc($row['email']) ?></td>
                        <td class="px-4 py-3 text-white/70"><?= esc($row['name'] ?: '—') ?></td>
                        <td class="px-4 py-3 text-xs text-white/60"><?= esc($row['topics'] ?: 'everything') ?></td>
                        <td class="px-4 py-3 text-white/70"><?= esc(strtoupper((string) $row['locale'])) ?></td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-brand-red/15 px-2.5 py-0.5 text-xs font-semibold capitalize text-brand-red"><?= esc($row['status']) ?></span>
                            <?php if ((int) $row['bounce_count'] > 0): ?>
                                <span class="ml-1 text-xs text-white/40"><?= (int) $row['bounce_count'] ?> bounces</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-white/60"><?= empty($row['confirmed_at']) ? '—' : esc(date('j M Y', strtotime((string) $row['confirmed_at']))) ?></td>
                        <td class="px-4 py-3 text-right">
                            <form method="post" action="<?= site_url('admin/subscribers/' . (int) $row['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Remove this subscriber?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-xs text-white/50 transition hover:text-red-400">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
