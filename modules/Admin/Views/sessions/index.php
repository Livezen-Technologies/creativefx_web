<?php
helper(['url', 'norlanka', 'catalog', 'commerce']);
$this->extend('Modules\Admin\Views\layout');

/**
 * Every date the school runs, in one table.
 *
 * The four numbers on each row — sold, held, total, minimum — are the whole
 * point of the screen: they are what somebody scans on a Monday morning to
 * decide which class is safe, which needs a push and which is about to have to
 * be cancelled. They are read-only here and everywhere else in the admin.
 */

$select = 'rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';

// Rebuild the current query string with one value changed, so the tabs and the
// pager keep the filters the administrator has already set rather than dropping
// them the moment they page forward.
$qs = static function (array $over = []) use ($filters): string {
    $q = array_filter(
        array_merge($filters, $over),
        static fn ($v): bool => $v !== '' && $v !== 0 && $v !== null
    );

    // Joined with &amp; rather than &, because these strings are concatenated
    // straight into an href and a bare ampersand in an attribute is only
    // accidentally safe.
    return $q === [] ? '' : '?' . http_build_query($q, '', '&amp;');
};

$statusChip = static function (string $status): string {
    $map = [
        'draft'     => 'bg-white/10 text-white/50',
        'open'      => 'bg-sky-500/15 text-sky-300',
        'confirmed' => 'bg-emerald-500/15 text-emerald-300',
        'waitlist'  => 'bg-amber-500/15 text-amber-300',
        'full'      => 'bg-amber-500/15 text-amber-300',
        'running'   => 'bg-emerald-500/15 text-emerald-300',
        'completed' => 'bg-white/10 text-white/50',
        'cancelled' => 'bg-brand-red/15 text-brand-red',
    ];

    return '<span class="rounded-full ' . ($map[$status] ?? 'bg-white/10 text-white/60')
        . ' px-2.5 py-0.5 text-[11px] font-semibold capitalize">' . esc($status) . '</span>';
};
?>
<?= $this->section('content') ?>

<!-- Past dates are a tab, never a delete: last quarter's classes are the
     register a certificate was issued on and the revenue a report is built
     from. -->
<div class="mb-5 flex flex-wrap items-center gap-2">
    <?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All dates'] as $key => $label): ?>
        <a href="<?= site_url('admin/course-sessions') . $qs(['when' => $key, 'page' => '']) ?>"
           class="rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest transition <?= $filters['when'] === $key ? 'bg-brand-red text-white' : 'bg-white/5 text-white/60 hover:text-white' ?>">
            <?= esc($label) ?> (<?= (int) ($counts[$key] ?? 0) ?>)
        </a>
    <?php endforeach; ?>
    <a href="<?= site_url('admin/course-sessions/new') . ($filters['course'] > 0 ? '?course=' . $filters['course'] : '') ?>"
       class="btn-brand ml-auto">New date</a>
</div>

<form method="get" action="<?= site_url('admin/course-sessions') ?>" class="mb-6 flex flex-wrap items-end gap-3">
    <input type="hidden" name="when" value="<?= esc($filters['when'], 'attr') ?>">

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
        <span class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-white/40">Mode</span>
        <select name="mode" class="<?= $select ?>">
            <option value="">All modes</option>
            <?php foreach ($modes as $m): ?>
                <option value="<?= esc($m, 'attr') ?>" <?= $filters['mode'] === $m ? 'selected' : '' ?>><?= esc(mode_label($m)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="block">
        <span class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-white/40">Month</span>
        <select name="month" class="<?= $select ?>">
            <option value="">Any month</option>
            <?php foreach ($months as $m): ?>
                <option value="<?= esc($m['month'], 'attr') ?>" <?= $filters['month'] === $m['month'] ? 'selected' : '' ?>>
                    <?= esc(date('F Y', strtotime($m['month'] . '-01'))) ?> (<?= (int) $m['n'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="block">
        <span class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-white/40">Status</span>
        <select name="status" class="<?= $select ?>">
            <option value="">Any status</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <button class="btn-brand">Filter</button>
    <a href="<?= site_url('admin/course-sessions') ?>" class="btn-ghost">Clear</a>
</form>

<div class="relative overflow-x-auto rounded-xl border border-white/10">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
            <tr>
                <th class="px-4 py-3">Course</th>
                <th class="px-4 py-3">Dates</th>
                <th class="hidden px-4 py-3 lg:table-cell">Mode &amp; place</th>
                <th class="px-4 py-3">Seats</th>
                <th class="px-4 py-3">Runs?</th>
                <th class="hidden px-4 py-3 md:table-cell">Price</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($rows as $r):
                // Named seatsTotal, not total: $total is the row count this
                // page is paged against, and shadowing it here printed the last
                // row's seat count as the number of dates found.
                $seatsTotal = (int) $r['seats_total'];
                $sold       = (int) $r['seats_sold'];
                $held       = (int) $r['seats_reserved'];
                $free       = $seatsTotal === 0 ? null : max(0, $seatsTotal - $sold - $held);
                $minToRun   = max(0, (int) $r['min_to_run']);
                $confirmed  = \Modules\Catalog\Models\CourseSessionModel::isConfirmed($r); ?>
                <tr class="align-top hover:bg-white/[0.02]">
                    <td class="px-4 py-3">
                        <a href="<?= site_url('admin/course-sessions/' . $r['id']) ?>" class="font-medium hover:text-brand-red"><?= esc(t_field($r['course_title'])) ?></a>
                        <?php if (! empty($r['instructor_name'])): ?>
                            <div class="text-xs text-white/40"><?= esc($r['instructor_name']) ?></div>
                        <?php endif; ?>
                        <?php if ((int) $r['is_private'] === 1): ?>
                            <div class="text-xs text-amber-300/80">Private — not on the public schedule</div>
                        <?php endif; ?>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3">
                        <div><?= esc(session_dates($r)) ?></div>
                        <?php $times = session_times($r); ?>
                        <?php if ($times !== ''): ?><div class="text-xs text-white/40"><?= esc($times) ?></div><?php endif; ?>
                    </td>

                    <td class="hidden px-4 py-3 lg:table-cell">
                        <div class="text-white/70"><?= esc(mode_label($r['mode'])) ?></div>
                        <div class="text-xs text-white/40"><?= esc($r['venue_name'] ?? '—') ?><?= empty($r['venue_city']) ? '' : ', ' . esc($r['venue_city']) ?></div>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3">
                        <?php if ($seatsTotal === 0): ?>
                            <span class="text-white/60">Unlimited</span>
                        <?php else: ?>
                            <div><span class="font-semibold"><?= $sold ?></span><span class="text-white/40"> of <?= $seatsTotal ?> sold</span></div>
                            <div class="text-xs text-white/40"><?= $held ?> held · <?= $free ?> free</div>
                        <?php endif; ?>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3">
                        <?php if ($minToRun === 0): ?>
                            <span class="text-xs text-white/40">No minimum</span>
                        <?php elseif ($confirmed): ?>
                            <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-300">Reached <?= $minToRun ?></span>
                        <?php else: ?>
                            <span class="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-amber-300"><?= $minToRun - $sold ?> more needed</span>
                        <?php endif; ?>
                    </td>

                    <td class="hidden whitespace-nowrap px-4 py-3 md:table-cell">
                        <?php if (empty($prices[(int) $r['id']])): ?>
                            <span class="text-xs text-brand-red">No price set</span>
                        <?php else: foreach ($prices[(int) $r['id']] as $p): ?>
                            <div class="text-white/75"><?= esc(money((int) $p['price_cents'], $p['currency'])) ?></div>
                        <?php endforeach; endif; ?>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3"><?= $statusChip((string) $r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="7" class="px-4 py-10 text-center text-white/40">No dates match these filters.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 flex items-center justify-between text-xs text-white/40">
    <p><?= number_format($total) ?> date<?= $total === 1 ? '' : 's' ?><?= $pages > 1 ? ' · page ' . $page . ' of ' . $pages : '' ?></p>
    <?php if ($pages > 1): ?>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
                <a href="<?= site_url('admin/course-sessions') . $qs(['page' => $page - 1]) ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Previous</a>
            <?php endif; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= site_url('admin/course-sessions') . $qs(['page' => $page + 1]) ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
