<?php
helper(['url', 'norlanka', 'catalog', 'commerce']);
$this->extend('Modules\Admin\Views\layout');

/**
 * The transfer queue.
 *
 * One row is one decision, and the decision needs four facts on screen: who,
 * which class they are leaving, when they asked, and what dates they could
 * move to. The notice they gave is shown because it is what decided the fee —
 * a learner asking eleven working days out moves free, and one asking nine
 * days out does not, and an administrator looking at this row should be able
 * to see why the number beside it says what it says.
 *
 * The destination is a select of real dates, not a free-text box: a transfer
 * has to land on a session that exists and has a seat, and typing an id is how
 * somebody moves a learner onto next year's Photoshop by mistake.
 *
 * @var list<array> $rows
 * @var string $status
 * @var array<string,int> $counts
 * @var int $freeNotice
 */
$card = 'rounded-2xl border border-white/10 bg-white/[0.02] p-5';
$btn  = 'rounded-lg border px-3 py-1.5 text-xs font-semibold uppercase tracking-widest transition';

$person = static function (array $r): string {
    $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));

    return $name !== '' ? $name : (string) ($r['email'] ?? 'Learner');
};

$when = static function (?string $date): string {
    return $date ? date('j M Y', strtotime($date)) : 'Self-paced';
};

$chip = static function (string $status): string {
    $map = [
        'requested' => 'bg-amber-500/15 text-amber-300',
        'completed' => 'bg-emerald-500/15 text-emerald-300',
        'declined'  => 'bg-brand-red/15 text-brand-red',
    ];

    return '<span class="rounded-full ' . ($map[$status] ?? 'bg-white/10 text-white/60')
        . ' px-2.5 py-0.5 text-[11px] font-semibold capitalize">' . esc($status) . '</span>';
};
?>
<?= $this->section('content') ?>

<div class="mb-6 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3 text-xs leading-relaxed text-white/50">
    <span class="text-white/70">Approving moves the seat.</span> A place is taken on the new date first and
    only then given back on the old one, so a learner is never briefly on neither course, and a date that
    filled up while the request waited here refuses rather than overselling.
    <span class="text-white/70">No money moves from this screen.</span> The fee was quoted when the learner
    asked, from the notice on the date they are leaving — free at <?= (int) $freeNotice ?> or more working
    days — and taking it is the Orders screen's job.
</div>

<?= view('Modules\Admin\Views\partials\queue_filter', [
    'base'     => 'admin/transfers',
    'current'  => $status === 'all' ? '' : $status,
    'statuses' => ['requested', 'completed', 'declined'],
    'counts'   => $counts,
], ['saveData' => false]) ?>

<?php if ($rows === []): ?>
    <div class="<?= $card ?> text-center">
        <p class="text-white/60">
            <?= $status === 'requested'
                ? 'Nothing is waiting. Requests appear here the moment a learner asks to move a booking.'
                : 'No ' . esc($status) . ' requests.' ?>
        </p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($rows as $r): ?>
            <?php
            $id       = (int) $r['id'];
            $notice   = $r['from_date']
                ? \Modules\Learning\Models\RescheduleRequestModel::businessDaysUntil((string) $r['from_date'])
                : null;
            $open     = $r['status'] === 'requested';
            ?>
            <article class="<?= $card ?>">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold">
                            <?= esc($person($r)) ?>
                            <span class="text-white/40">·</span>
                            <span class="text-white/70"><?= esc(t_field($r['course_title'] ?? '')) ?></span>
                        </h2>
                        <p class="mt-1 text-xs text-white/50">
                            <?= esc((string) ($r['email'] ?? '')) ?>
                            <?php if (! empty($r['requested_at'])): ?>
                                · asked <?= esc(date('j M Y', strtotime((string) $r['requested_at']))) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?= $chip((string) $r['status']) ?>
                </div>

                <dl class="mt-4 grid gap-3 text-xs sm:grid-cols-4">
                    <div>
                        <dt class="text-white/40">Leaving</dt>
                        <dd class="mt-0.5 text-white/80"><?= esc($when($r['from_date'] ?? null)) ?></dd>
                        <?php if (! empty($r['from_mode'])): ?>
                            <dd class="text-white/40"><?= esc(mode_label((string) $r['from_mode'])) ?></dd>
                        <?php endif; ?>
                    </div>
                    <div>
                        <dt class="text-white/40">Notice given</dt>
                        <dd class="mt-0.5 <?= $notice !== null && $notice < $freeNotice ? 'text-amber-300' : 'text-white/80' ?>">
                            <?= $notice === null ? '—' : esc($notice . ' working day' . ($notice === 1 ? '' : 's')) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-white/40">Fee quoted</dt>
                        <dd class="mt-0.5 text-white/80">
                            <?= (int) $r['fee_cents'] > 0 && ! empty($r['currency'])
                                ? esc(money((int) $r['fee_cents'], (string) $r['currency']))
                                : 'Free' ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-white/40"><?= $open ? 'Asked for' : 'Moved to' ?></dt>
                        <dd class="mt-0.5 text-white/80"><?= esc($when($r['to_date'] ?? null)) ?></dd>
                    </div>
                </dl>

                <?php if (trim((string) ($r['reason'] ?? '')) !== ''): ?>
                    <p class="mt-3 rounded-lg border border-white/10 bg-black/20 p-3 text-xs leading-relaxed text-white/70">
                        <?= nl2br(esc((string) $r['reason'])) ?>
                    </p>
                <?php endif; ?>

                <?php if (! $open): ?>
                    <?php if (trim((string) ($r['decision_note'] ?? '')) !== ''): ?>
                        <p class="mt-3 text-xs text-white/50">
                            <span class="text-white/70">Decision:</span> <?= esc((string) $r['decision_note']) ?>
                        </p>
                    <?php endif; ?>
                <?php elseif ($r['alternatives'] === []): ?>
                    <p class="mt-4 text-xs text-amber-300">
                        This course has no other bookable date in the calendar, so there is nowhere to move
                        them yet. Schedule one, or decline with a reason.
                    </p>
                <?php endif; ?>

                <?php if ($open): ?>
                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        <?php if ($r['alternatives'] !== []): ?>
                            <form method="post" action="<?= site_url('admin/transfers/' . $id . '/approve') ?>"
                                  class="space-y-2 rounded-lg border border-emerald-500/25 bg-emerald-500/5 p-3">
                                <?= csrf_field() ?>
                                <label class="block text-xs text-white/60" for="to-<?= $id ?>">Move to</label>
                                <select id="to-<?= $id ?>" name="to_session_id" required
                                        class="w-full rounded border border-white/15 bg-black/40 px-2 py-1.5 text-xs">
                                    <?php foreach ($r['alternatives'] as $alt): ?>
                                        <?php
                                        $left = (int) $alt['seats_total'] === 0
                                            ? null
                                            : max(0, (int) $alt['seats_total'] - (int) $alt['seats_sold']);
                                        ?>
                                        <option value="<?= (int) $alt['id'] ?>">
                                            <?= esc($when($alt['start_date'] ?? null)) ?>
                                            · <?= esc(mode_label((string) $alt['mode'])) ?>
                                            <?= $left === null ? '' : '· ' . $left . ' seat' . ($left === 1 ? '' : 's') . ' left' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="note" type="text" maxlength="255" placeholder="Note (optional)"
                                       class="w-full rounded border border-white/15 bg-black/40 px-2 py-1.5 text-xs">
                                <button class="<?= $btn ?> border-emerald-500/40 text-emerald-300 hover:border-emerald-400">Approve and move</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="<?= site_url('admin/transfers/' . $id . '/decline') ?>"
                              class="space-y-2 rounded-lg border border-brand-red/25 bg-brand-red/10 p-3">
                            <?= csrf_field() ?>
                            <label class="block text-xs text-white/60" for="no-<?= $id ?>">Why not?</label>
                            <input id="no-<?= $id ?>" name="note" type="text" maxlength="255" required
                                   class="w-full rounded border border-white/15 bg-black/40 px-2 py-1.5 text-xs">
                            <p class="text-xs text-white/40">The learner is shown this, and keeps their original seat.</p>
                            <button class="<?= $btn ?> border-brand-red/50 text-brand-red hover:border-brand-red">Decline</button>
                        </form>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
