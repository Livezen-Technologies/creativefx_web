<?php
helper(['url', 'norlanka']);
$this->extend('Modules\Admin\Views\layout');
?>
<?= $this->section('content') ?>

<?= view('Modules\Admin\Views\partials\queue_filter', [
    'base'     => 'admin/comments',
    'current'  => $status,
    'statuses' => $statuses,
    'counts'   => $counts,
], ['saveData' => false]) ?>

<?php if ($comments === []): ?>
    <p class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 text-sm text-white/60">Nothing in this queue.</p>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($comments as $comment): ?>
            <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold"><?= esc($comment['author']) ?></p>
                        <p class="mt-0.5 text-xs text-white/50">
                            <?= esc(t_field($comment['topic_title'] ?? '')) ?>
                            <?= ! empty($comment['district']) ? ' · ' . esc($comment['district']) : '' ?>
                            · <?= esc(date('j M Y H:i', strtotime((string) $comment['created_at']))) ?>
                            · <?= esc(strtoupper($comment['locale'])) ?>
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full bg-brand-red/15 px-3 py-1 text-xs font-semibold capitalize text-brand-red"><?= esc($comment['status']) ?></span>
                </div>

                <p class="mt-4 whitespace-pre-line rounded-lg border border-white/10 bg-black/20 p-4 text-sm leading-relaxed text-white/80"><?= esc($comment['body']) ?></p>

                <?php if (! empty($comment['email'])): ?>
                    <p class="mt-2 text-xs text-white/40">Contact: <?= esc($comment['email']) ?> — never published</p>
                <?php endif; ?>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php foreach (['approved' => 'Publish', 'rejected' => 'Reject', 'pending' => 'Back to pending'] as $s => $label): ?>
                        <?php if ($comment['status'] === $s) { continue; } ?>
                        <form method="post" action="<?= site_url('admin/comments/' . (int) $comment['id']) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= esc($s, 'attr') ?>">
                            <button type="submit" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold transition hover:border-brand-red hover:text-brand-red"><?= esc($label) ?></button>
                        </form>
                    <?php endforeach; ?>
                    <form method="post" action="<?= site_url('admin/comments/' . (int) $comment['id'] . '/delete') ?>"
                          onsubmit="return confirm('Delete this comment permanently? Rejecting keeps the record; deleting does not.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="rounded-lg border border-white/10 px-4 py-2 text-sm text-white/50 transition hover:border-red-500 hover:text-red-400">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
