<?php
helper(['url', 'norlanka', 'catalog', 'commerce']);
$this->extend('Modules\Admin\Views\layout');

/**
 * Every place the school has sold or given away.
 *
 * The breakdown along the top is by source rather than by status, which is the
 * one decision on this screen worth defending. Status answers "where is this
 * learner up to"; source answers "was this a sale", and only one of those two
 * questions can be got wrong in a way that misreports the business. A month
 * with forty enrolments of which fifteen are free retakes is not a month with
 * forty sales, and the figure that says so has to be on the screen rather than
 * one export away.
 *
 * Nothing here writes a seat count or moves money, and the banner under the
 * chips says so in as many words.
 */

$select = 'rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';

// Rebuild the query string with one value changed, so a filter survives paging
// and a chip survives a course already having been chosen.
$qs = static function (array $over = []) use ($filters): string {
    $q = array_filter(
        array_merge($filters, $over),
        static fn ($v): bool => $v !== '' && $v !== 0 && $v !== null
    );

    return $q === [] ? '' : '?' . http_build_query($q);
};

$sourceLabels = [
    'purchase'  => 'Sold',
    'corporate' => 'Corporate',
    'retake'    => 'Free retake',
    'comp'      => 'Complimentary',
    'import'    => 'Imported',
];

// Coloured by whether money changed hands, not by the name of the source. The
// two greens are revenue; the ambers are places given away; grey is a row that
// arrived from somewhere else entirely.
$sourceTones = [
    'purchase'  => 'text-emerald-300',
    'corporate' => 'text-emerald-300',
    'retake'    => 'text-amber-300',
    'comp'      => 'text-amber-300',
    'import'    => 'text-white/50',
];

$statusLabels = [
    'active'      => 'Active',
    'completed'   => 'Completed',
    'cancelled'   => 'Cancelled',
    'no_show'     => 'No show',
    'transferred' => 'Transferred',
];

$statusChip = static function (string $status) use ($statusLabels): string {
    $map = [
        'active'      => 'bg-sky-500/15 text-sky-300',
        'completed'   => 'bg-emerald-500/15 text-emerald-300',
        'cancelled'   => 'bg-brand-red/15 text-brand-red',
        'no_show'     => 'bg-amber-500/15 text-amber-300',
        'transferred' => 'bg-white/10 text-white/50',
    ];

    return '<span class="rounded-full ' . ($map[$status] ?? 'bg-white/10 text-white/60')
        . ' px-2.5 py-0.5 text-[11px] font-semibold">' . esc($statusLabels[$status] ?? $status) . '</span>';
};

$learnerName = static fn (array $e): string => trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')) ?: (string) ($e['email'] ?? 'Unknown');

$unpaidTotal = array_sum(array_intersect_key($counts, array_flip($unpaidSources)));
$allTotal    = array_sum($counts);
?>
<?= $this->section('content') ?>

<!-- Source is the headline breakdown, and it is also the filter -->
<div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    <a href="<?= esc(site_url('admin/enrolments') . $qs(['source' => '', 'page' => '']), 'attr') ?>"
       class="rounded-xl border p-4 transition <?= $filters['source'] === '' ? 'border-brand-red bg-brand-red/10' : 'border-white/10 bg-white/[0.02] hover:border-white/25' ?>">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-white/45">All places</p>
        <p class="mt-1.5 text-2xl font-bold"><?= esc((string) $allTotal) ?></p>
        <p class="mt-0.5 text-xs text-white/40"><?= esc((string) $unpaidTotal) ?> at no price</p>
    </a>
    <?php foreach ($sources as $s): ?>
        <a href="<?= esc(site_url('admin/enrolments') . $qs(['source' => $s, 'page' => '']), 'attr') ?>"
           class="rounded-xl border p-4 transition <?= $filters['source'] === $s ? 'border-brand-red bg-brand-red/10' : 'border-white/10 bg-white/[0.02] hover:border-white/25' ?>">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-white/45"><?= esc($sourceLabels[$s] ?? $s) ?></p>
            <p class="mt-1.5 text-2xl font-bold <?= ($counts[$s] ?? 0) > 0 ? esc($sourceTones[$s] ?? 'text-white') : 'text-white/25' ?>"><?= esc((string) ($counts[$s] ?? 0)) ?></p>
            <p class="mt-0.5 text-xs text-white/40"><?= in_array($s, $unpaidSources, true) ? 'No revenue' : 'Revenue' ?></p>
        </a>
    <?php endforeach; ?>
</div>

<p class="mb-6 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3 text-xs leading-relaxed text-white/50">
    A free retake, a complimentary place and an imported row are each an enrolment at no price. Counting
    them alongside sales shows a business growing when it is not, which is why the split above is the first
    thing on this screen. <span class="text-white/70">Changing a status here releases no seat and refunds
    no money</span> — a seat comes back when the order is refunded, on the Orders screen.
</p>

<form method="get" action="<?= site_url('admin/enrolments') ?>" class="mb-6 flex flex-wrap items-end gap-3">
    <?php // The chosen source rides along as a hidden field, so filtering by
          // course does not silently drop the chip somebody has clicked. ?>
    <input type="hidden" name="source" value="<?= esc($filters['source'], 'attr') ?>">

    <label class="block">
        <span class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-white/40">Course</span>
        <select name="course" class="<?= $select ?>">
            <option value="">All courses</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filters['course'] === (int) $c['id'] ? 'selected' : '' ?>><?= esc(t_field($c['title'])) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="block">
        <span class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-white/40">Date</span>
        <select name="session" class="<?= $select ?>">
            <option value=""><?= $filters['course'] > 0 ? 'Any date on this course' : 'Any date' ?></option>
            <?php foreach ($sessions as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= $filters['session'] === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= esc(session_dates($s)) ?><?= $filters['course'] > 0 ? '' : ' — ' . esc(t_field($s['course_title'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="block">
        <span class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-white/40">Status</span>
        <select name="status" class="<?= $select ?>">
            <option value="">Any status</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= esc($statusLabels[$s] ?? $s) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <button class="btn-brand">Filter</button>
    <a href="<?= site_url('admin/enrolments') ?>" class="btn-ghost">Clear</a>
</form>

<div class="relative overflow-x-auto rounded-xl border border-white/10">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
            <tr>
                <th class="px-4 py-3">Learner</th>
                <th class="px-4 py-3">On</th>
                <th class="px-4 py-3">Source</th>
                <th class="hidden px-4 py-3 lg:table-cell">Booked</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Certificate</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($rows as $r):
                $id     = (int) $r['id'];
                $source = (string) $r['source'];
                // Cancelled and transferred places take no certificate — see
                // Enrolments::issueCertificate(), which refuses them; the form
                // is hidden here so nobody is offered a button that says no.
                $certifiable = ! in_array((string) $r['status'], ['cancelled', 'transferred'], true); ?>
                <tr class="align-top hover:bg-white/[0.02]">
                    <td class="px-4 py-3">
                        <div class="font-medium"><?= esc($learnerName($r)) ?></div>
                        <div class="text-xs text-white/45"><?= esc((string) ($r['email'] ?? '')) ?></div>
                        <?php if (! empty($r['company'])): ?>
                            <div class="text-xs text-white/40"><?= esc($r['company']) ?></div>
                        <?php endif; ?>
                    </td>

                    <td class="px-4 py-3">
                        <?php if (! empty($r['session_id'])): ?>
                            <a href="<?= site_url('admin/course-sessions/' . (int) $r['session_id']) ?>" class="font-medium hover:text-brand-red"><?= esc(t_field($r['course_title'] ?? '')) ?></a>
                        <?php else: ?>
                            <span class="font-medium"><?= esc(t_field($r['course_title'] ?? '')) ?></span>
                        <?php endif; ?>
                        <div class="text-xs text-white/45">
                            <?= esc(session_dates($r)) ?> · <?= esc(mode_label((string) $r['mode'])) ?>
                        </div>
                        <?php if (! empty($r['bundle_id'])): ?>
                            <div class="text-xs text-white/40">Part of a programme</div>
                        <?php endif; ?>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="text-xs font-semibold <?= esc($sourceTones[$source] ?? 'text-white/60') ?>"><?= esc($sourceLabels[$source] ?? $source) ?></span>
                        <?php // The price of the order line this place came from. The unit
                              // price, not the line total: a line can be four seats, and a
                              // quarter of a total is not what one of them cost. A blank is
                              // the honest answer that no money was taken. ?>
                        <div class="text-xs text-white/40">
                            <?php if ($r['order_id'] === null): ?>
                                No order — nothing paid
                            <?php else: ?>
                                <?= esc(money((int) $r['unit_price_cents'], (string) $r['currency'])) ?> per seat
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="hidden whitespace-nowrap px-4 py-3 text-white/50 lg:table-cell">
                        <div><?= esc($r['enrolled_at'] ? date('j M Y', strtotime((string) $r['enrolled_at'])) : '—') ?></div>
                        <?php if (! empty($r['order_id'])): ?>
                            <a href="<?= site_url('admin/orders/' . (int) $r['order_id']) ?>" class="font-mono text-xs hover:text-brand-red"><?= esc((string) $r['order_no']) ?></a>
                        <?php endif; ?>
                    </td>

                    <td class="px-4 py-3">
                        <?php if (in_array((string) $r['status'], $editable, true)): ?>
                            <form method="post" action="<?= site_url('admin/enrolments/' . $id) ?>" class="flex items-center gap-1.5">
                                <?= csrf_field() ?>
                                <select name="status" class="rounded-lg border border-white/15 bg-black/40 px-2 py-1.5 text-xs focus:border-brand-red focus:outline-none">
                                    <?php foreach ($editable as $s): ?>
                                        <option value="<?= esc($s, 'attr') ?>" <?= (string) $r['status'] === $s ? 'selected' : '' ?>><?= esc($statusLabels[$s] ?? $s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="rounded-lg border border-white/15 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/70 hover:border-white hover:text-white">Save</button>
                            </form>
                        <?php else: ?>
                            <?php // `transferred` is what a reschedule leaves behind. It is
                                  // not offered in the dropdown, so a row already in it is
                                  // shown as a chip rather than as a form that could move it
                                  // somewhere the reschedule never agreed to. ?>
                            <?= $statusChip((string) $r['status']) ?>
                        <?php endif; ?>
                    </td>

                    <td class="px-4 py-3">
                        <?php if (! empty($r['certificate_serial'])): ?>
                            <a href="<?= esc(rtrim(base_url(), '/') . '/verify/' . $r['certificate_code'], 'attr') ?>" target="_blank" rel="noopener"
                               class="font-mono text-xs hover:text-brand-red"><?= esc((string) $r['certificate_serial']) ?></a>
                            <?php if (! empty($r['certificate_revoked_at'])): ?>
                                <div class="text-xs text-brand-red">Revoked</div>
                            <?php else: ?>
                                <div class="text-xs text-white/40"><?= esc(date('j M Y', strtotime((string) $r['certificate_issued_at']))) ?></div>
                            <?php endif; ?>
                        <?php elseif (! $certifiable): ?>
                            <span class="text-xs text-white/25">—</span>
                        <?php else: ?>
                            <form method="post" action="<?= site_url('admin/enrolments/' . $id . '/certificate') ?>">
                                <?= csrf_field() ?>
                                <button class="rounded-lg border border-white/15 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/70 hover:border-white hover:text-white">Issue</button>
                            </form>

                            <?php // A second form rather than a checkbox inside the first, and
                                  // deliberately not `required`: a required control inside a
                                  // collapsed <details> is one a browser cannot scroll to, and
                                  // it blocks the ordinary Issue button with a validation
                                  // message nobody can see. The note is checked server-side. ?>
                            <details class="mt-1.5">
                                <summary class="cursor-pointer text-xs text-white/35 hover:text-white/70">Issue anyway</summary>
                                <form method="post" action="<?= site_url('admin/enrolments/' . $id . '/certificate') ?>" class="mt-2 w-56 space-y-1.5 rounded-lg border border-amber-500/30 bg-amber-500/5 p-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="force" value="1">
                                    <p class="text-[11px] leading-snug text-amber-200/80">
                                        For a learner who was there when the register was not marked. Not for
                                        somebody who did not attend.
                                    </p>
                                    <input type="text" name="reason" maxlength="255" placeholder="Why the rule is being overridden"
                                           class="w-full rounded border border-white/15 bg-black/40 px-2 py-1 text-xs focus:border-brand-red focus:outline-none">
                                    <button class="w-full rounded border border-amber-500/40 px-2 py-1 text-[11px] font-semibold uppercase tracking-widest text-amber-200 hover:border-amber-400">Override and issue</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-white/40">
                        <?php if ($allTotal > 0): ?>
                            No enrolments match these filters.
                        <?php else: ?>
                            Nobody is enrolled yet. The first place appears here the moment an order is paid —
                            by a verified webhook, or by an administrator recording a bank transfer on the
                            Orders screen. Nothing else creates one.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 flex items-center justify-between text-xs text-white/40">
    <p><?= number_format($total) ?> place<?= $total === 1 ? '' : 's' ?><?= $pages > 1 ? ' · page ' . $page . ' of ' . $pages : '' ?></p>
    <?php if ($pages > 1): ?>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
                <a href="<?= esc(site_url('admin/enrolments') . $qs(['page' => $page - 1]), 'attr') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Previous</a>
            <?php endif; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= esc(site_url('admin/enrolments') . $qs(['page' => $page + 1]), 'attr') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
