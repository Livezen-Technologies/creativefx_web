<?php
helper(['url']);
$this->extend('Modules\Admin\Views\layout');

$ago = static function (?string $when): string {
    if (empty($when)) { return 'never'; }
    $diff = time() - strtotime($when);
    if ($diff < 60)    { return 'just now'; }
    if ($diff < 3600)  { return (int) ($diff / 60) . ' min ago'; }
    if ($diff < 86400) { return (int) ($diff / 3600) . ' hr ago'; }
    return (int) ($diff / 86400) . ' days ago';
};
?>
<?= $this->section('content') ?>

<div class="mb-6 rounded-2xl border border-white/10 bg-white/[0.02] p-6">
    <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">The cron entry</h3>
    <p class="mt-2 text-sm leading-relaxed text-white/50">
        One line, hourly. The schedule lives in the table below, so adding a task never means editing the
        server's crontab again — which is the step that gets forgotten. Run <code class="rounded bg-black/30 px-1.5 py-0.5 text-xs">crontab -e</code>
        on the server and paste this:
    </p>
    <pre class="mt-4 overflow-x-auto rounded-lg border border-white/10 bg-black/40 p-4 text-xs leading-relaxed text-white/80"><code><?= esc($command) ?></code></pre>
    <p class="mt-3 text-xs text-white/35">
        Until that is in place nothing below runs on its own — but every task can still be run by hand from this page.
    </p>
</div>

<div class="overflow-x-auto rounded-2xl border border-white/10">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
            <tr>
                <th class="px-4 py-3">Task</th>
                <th class="px-4 py-3">Every</th>
                <th class="px-4 py-3">Last run</th>
                <th class="px-4 py-3">Result</th>
                <th class="px-4 py-3">Next</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($tasks as $key => $t): ?>
                <tr class="align-top hover:bg-white/[0.02]">
                    <td class="px-4 py-3.5">
                        <p class="font-medium <?= (int) $t['enabled'] === 1 ? '' : 'text-white/40' ?>"><?= esc($t['label']) ?></p>
                        <p class="mt-1 max-w-md text-xs leading-relaxed text-white/40"><?= esc($t['about']) ?></p>
                        <?php if ($t['runs'] > 0): ?>
                            <p class="mt-1 text-[11px] text-white/30">
                                <?= esc($t['runs']) ?> run<?= $t['runs'] === 1 ? '' : 's' ?><?= $t['failures'] > 0 ? ', ' . esc($t['failures']) . ' failed' : '' ?>
                            </p>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3.5 capitalize text-white/60"><?= esc($t['frequency']) ?></td>
                    <td class="px-4 py-3.5 whitespace-nowrap text-white/60">
                        <?= esc($ago($t['last_run_at'])) ?>
                        <?php if ($t['last_ms'] !== null): ?>
                            <span class="block text-[11px] text-white/30"><?= esc($t['last_ms']) ?> ms</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3.5">
                        <?php if ($t['last_status'] === null): ?>
                            <span class="text-white/35">—</span>
                        <?php else: ?>
                            <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold <?= $t['last_status'] === 'ok'
                                ? 'bg-emerald-400/15 text-emerald-300'
                                : ($t['last_status'] === 'failed' ? 'bg-brand-red/20 text-brand-red' : 'bg-white/10 text-white/60') ?>">
                                <?= esc($t['last_status']) ?>
                            </span>
                            <?php // The message, not a log reference: whoever is
                                  // reading this may not have shell access. ?>
                            <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-white/45"><?= esc((string) $t['last_message']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3.5 whitespace-nowrap text-white/50">
                        <?php if ((int) $t['enabled'] !== 1): ?>
                            <span class="text-white/30">paused</span>
                        <?php elseif ($t['running']): ?>
                            <span class="text-amber-300/80">running</span>
                        <?php else: ?>
                            <?= esc($t['is_due'] ? 'due now' : $t['due_at']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center justify-end gap-2">
                            <form method="post" action="<?= site_url('admin/tasks/' . $key . '/run') ?>">
                                <?= csrf_field() ?>
                                <button class="rounded-lg border border-white/15 px-2.5 py-1 text-xs font-semibold text-white/75 transition hover:border-white/40 hover:text-white">Run now</button>
                            </form>
                            <form method="post" action="<?= site_url('admin/tasks/' . $key . '/toggle') ?>">
                                <?= csrf_field() ?>
                                <button class="rounded-lg border border-white/15 px-2.5 py-1 text-xs font-semibold text-white/55 transition hover:border-white/40 hover:text-white">
                                    <?= (int) $t['enabled'] === 1 ? 'Pause' : 'Resume' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="mt-6 text-xs leading-relaxed text-white/35">
    A task is claimed while it runs, so an hourly cron cannot start a second copy of one that has not finished —
    which for a job that deletes rows or sends mail would be a bug rather than a slowdown. A claim older than an
    hour is treated as abandoned, so a process killed mid-run does not leave its task stuck.
</p>

<?= $this->endSection() ?>
