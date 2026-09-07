<?php
helper(['url', 'norlanka', 'catalog', 'commerce']);
$this->extend('Modules\Admin\Views\layout');

/**
 * One date: who is coming, who turned up, how to join, and what it costs.
 *
 * This is the operational page rather than a record card. Everything an
 * administrator does on the morning of a class is here — read the register,
 * mark it, copy the joining link, count the room — and the settings form is
 * embedded below rather than living behind an Edit link, because walking
 * between two pages while a learner is on the telephone is how the wrong date
 * gets changed.
 *
 * The seat numbers on this page are read, never written. `seats_sold` and
 * `seats_reserved` belong to InventoryService; `seatsLeft` beside them is
 * recomputed live, since the stored `seats_reserved` is a cache and expired
 * holds may not have been swept yet.
 */

$s         = $row;
$card      = 'rounded-2xl border border-white/10 bg-white/[0.02] p-6';
$input     = 'w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
$heading   = 'text-sm font-semibold uppercase tracking-widest text-white/60';
$cancelled = $s['status'] === 'cancelled';

$seatsTotal = (int) $s['seats_total'];
$sold       = (int) $s['seats_sold'];
$held       = (int) $s['seats_reserved'];
$minToRun   = max(0, (int) $s['min_to_run']);
$confirmed  = \Modules\Catalog\Models\CourseSessionModel::isConfirmed($s);

$learnerName = static fn (array $e): string => trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')) ?: (string) $e['email'];
?>
<?= $this->section('content') ?>

<a href="<?= site_url('admin/course-sessions') ?>" class="text-xs uppercase tracking-widest text-white/50 hover:text-white">&larr; All dates</a>

<?php if ($cancelled): ?>
    <div class="mt-4 rounded-xl border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm text-brand-red">
        <strong>This date is cancelled.</strong>
        <?= empty($s['cancel_reason']) ? '' : esc($s['cancel_reason']) ?>
        Everybody who was booked has been cancelled with it; refunds are issued from Orders.
    </div>
<?php endif; ?>

<!-- What this date is, in one line each -->
<div class="mt-4 <?= $card ?>">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold"><?= esc(t_field($s['course_title'] ?? '')) ?></h2>
            <p class="mt-1 text-sm text-white/60">
                <?= esc(session_dates($s)) ?>
                <?php $times = session_times($s); ?>
                <?= $times === '' ? '' : ' · ' . esc($times) ?>
                · <?= esc(mode_label($s['mode'])) ?>
                <?= empty($s['venue_name']) ? '' : ' · ' . esc($s['venue_name']) . (empty($s['venue_city']) ? '' : ', ' . esc($s['venue_city'])) ?>
            </p>
            <p class="mt-1 text-xs text-white/40">
                Status <span class="capitalize text-white/70"><?= esc($s['status']) ?></span>
                <?= (int) $s['is_private'] === 1 ? ' · private, off the public schedule' : '' ?>
                <?= empty($s['instructor_name']) ? '' : ' · ' . esc($s['instructor_name']) ?>
            </p>
        </div>
        <?php if (! empty($s['course_slug'])): ?>
            <a href="<?= esc(session_url($s), 'attr') ?>" target="_blank" rel="noopener"
               class="rounded-lg border border-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-widest hover:border-white">View public page</a>
        <?php endif; ?>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <!-- Roster and register -->
    <div class="space-y-6 lg:col-span-2">
        <div class="<?= $card ?>">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 class="<?= $heading ?>">Who is coming (<?= count($roster) ?>)</h3>
                <?php if ($roster !== []): ?>
                    <?php /* BCC rather than To: the register is not a mailing list the room may read. */ ?>
                    <a href="mailto:?bcc=<?= esc(implode(',', array_column($roster, 'email')), 'attr') ?>&amp;subject=<?= rawurlencode(t_field($s['course_title'] ?? '') . ' — ' . session_dates($s)) ?>"
                       class="rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/70 hover:border-white/40">Email everybody</a>
                <?php endif; ?>
            </div>

            <?php if ($roster === []): ?>
                <p class="py-6 text-center text-sm text-white/40">Nobody is booked on this date yet.</p>
            <?php else: ?>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-widest text-white/40">
                            <tr>
                                <th class="pb-2 pr-4">Learner</th>
                                <th class="hidden pb-2 pr-4 sm:table-cell">Company</th>
                                <th class="hidden pb-2 pr-4 md:table-cell">Booked</th>
                                <th class="pb-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($roster as $e): ?>
                                <tr>
                                    <td class="py-2 pr-4">
                                        <div class="font-medium"><?= esc($learnerName($e)) ?></div>
                                        <div class="text-xs text-white/45"><?= esc($e['email']) ?><?= empty($e['phone']) ? '' : ' · ' . esc($e['phone']) ?></div>
                                    </td>
                                    <td class="hidden py-2 pr-4 text-white/60 sm:table-cell"><?= esc($e['company'] ?? '') ?></td>
                                    <td class="hidden py-2 pr-4 text-white/45 md:table-cell"><?= empty($e['enrolled_at']) ? '' : esc(date('j M Y', strtotime((string) $e['enrolled_at']))) ?></td>
                                    <td class="py-2"><span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold capitalize"><?= esc($e['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- The register, one day at a time -->
        <div id="register" class="<?= $card ?>">
            <h3 class="<?= $heading ?>">Attendance register</h3>

            <?php if ($dayRows === []): ?>
                <p class="mt-4 text-sm text-white/40">This date has no day rows, so there is nothing to mark. Add them below and save.</p>
            <?php elseif ($roster === []): ?>
                <p class="mt-4 text-sm text-white/40">The register opens once somebody is booked.</p>
            <?php else: ?>
                <p class="mt-2 text-xs text-white/40">
                    Marked per day, not per class: a company paying for four staff on a two-day course wants to know who
                    was there on day two, and a certificate is issued on the percentage of days attended rather than on
                    somebody's recollection.
                </p>

                <div x-data="{ day: <?= (int) $dayRows[0]['id'] ?> }" class="mt-4">
                    <div class="mb-4 flex flex-wrap gap-2">
                        <?php foreach ($dayRows as $d): ?>
                            <button type="button" @click="day = <?= (int) $d['id'] ?>"
                                    :class="day === <?= (int) $d['id'] ?> ? 'bg-brand-red text-white' : 'bg-white/5 text-white/60 hover:text-white'"
                                    class="rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest transition">
                                <?= esc(date('j M', strtotime((string) $d['day_date']))) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($dayRows as $d): ?>
                        <form method="post" action="<?= site_url('admin/course-sessions/' . $s['id'] . '/attendance') ?>"
                              x-show="day === <?= (int) $d['id'] ?>" x-cloak>
                            <?= csrf_field() ?>
                            <input type="hidden" name="day_id" value="<?= (int) $d['id'] ?>">

                            <table class="w-full text-sm">
                                <thead class="text-left text-xs uppercase tracking-widest text-white/40">
                                    <tr>
                                        <th class="pb-2 pr-4">Learner</th>
                                        <th class="pb-2 pr-4">Register</th>
                                        <th class="pb-2">Minutes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($roster as $e):
                                        $mark = $marks[(int) $e['id']][(int) $d['id']] ?? null; ?>
                                        <tr>
                                            <td class="py-2 pr-4"><?= esc($learnerName($e)) ?></td>
                                            <td class="py-2 pr-4">
                                                <select name="status[<?= (int) $e['id'] ?>]" class="<?= $input ?>">
                                                    <?php /* Blank is "not marked yet" and is skipped by the controller,
                                                             so opening a day and saving it does not silently record
                                                             everybody as present. */ ?>
                                                    <option value="">— Not marked —</option>
                                                    <?php foreach ($attendanceStatuses as $a): ?>
                                                        <option value="<?= esc($a, 'attr') ?>" <?= ($mark['status'] ?? '') === $a ? 'selected' : '' ?>><?= esc(ucfirst($a)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="py-2">
                                                <input type="number" min="0" name="minutes[<?= (int) $e['id'] ?>]"
                                                       value="<?= esc($mark['minutes'] ?? '') ?>" placeholder="—" class="<?= $input ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <button class="btn-brand mt-4">Save the register for <?= esc(date('j M Y', strtotime((string) $d['day_date']))) ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- The numbers, the link, and the one action that cannot be undone -->
    <div class="space-y-6">
        <div class="<?= $card ?>">
            <h3 class="<?= $heading ?>">Seats</h3>
            <?php if ($seatsTotal === 0): ?>
                <p class="mt-3 text-sm text-white/60">Unlimited — this date has no seat limit.</p>
            <?php else: ?>
                <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl border border-white/10 bg-black/20 p-3">
                        <p class="text-2xl font-bold"><?= $sold ?></p>
                        <p class="text-[10px] uppercase tracking-widest text-white/40">Sold</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-black/20 p-3">
                        <p class="text-2xl font-bold"><?= $held ?></p>
                        <p class="text-[10px] uppercase tracking-widest text-white/40">Held</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-black/20 p-3">
                        <p class="text-2xl font-bold"><?= $seatsLeft === null ? '∞' : (int) $seatsLeft ?></p>
                        <p class="text-[10px] uppercase tracking-widest text-white/40">Free</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-white/40">
                    Out of <?= $seatsTotal ?>. "Free" is recomputed now from the holds that have not expired, so it can
                    be a seat or two ahead of "held", which is a cache the sweeper tidies up.
                </p>
            <?php endif; ?>

            <div class="mt-4 rounded-xl border <?= $confirmed ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-300' ?> px-3 py-2 text-sm">
                <?php if ($minToRun === 0): ?>
                    <span class="text-white/60">No minimum set — this date runs whoever books.</span>
                <?php elseif ($confirmed): ?>
                    Confirmed to run: <?= $sold ?> of the <?= $minToRun ?> needed.
                <?php else: ?>
                    <?= $minToRun - $sold ?> more booking<?= $minToRun - $sold === 1 ? '' : 's' ?> before this date is confirmed.
                <?php endif; ?>
            </div>
        </div>

        <div class="<?= $card ?>">
            <h3 class="<?= $heading ?>">Price</h3>
            <?php if ($prices === []): ?>
                <p class="mt-3 text-sm text-brand-red">No price in any currency, so nobody can book this date.</p>
            <?php else: ?>
                <dl class="mt-3 space-y-1 text-sm">
                    <?php foreach ($prices as $code => $p): ?>
                        <div class="flex justify-between">
                            <dt class="text-white/50"><?= esc($code) ?></dt>
                            <dd class="font-medium">
                                <?= esc(money((int) $p['price_cents'], (string) $code)) ?>
                                <?php if (! empty($p['compare_at_cents'])): ?>
                                    <span class="ml-1 text-xs text-white/35 line-through"><?= esc(money((int) $p['compare_at_cents'], (string) $code)) ?></span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>

        <div class="<?= $card ?>">
            <h3 class="<?= $heading ?>">Joining link</h3>
            <?php if ($joiningUrl === ''): ?>
                <p class="mt-3 text-sm text-white/40">No link set for this date yet.</p>
            <?php else: ?>
                <p class="mt-3 break-all rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-xs text-white/75"><?= esc($joiningUrl) ?></p>
                <a href="<?= esc($joiningUrl, 'attr') ?>" target="_blank" rel="noopener"
                   class="mt-2 inline-block text-xs font-semibold text-brand-red hover:underline">Open the room</a>
            <?php endif; ?>

            <?php $perDay = array_filter($dayRows, static fn (array $d): bool => ($d['meeting_url'] ?? '') !== ''); ?>
            <?php if ($perDay !== []): ?>
                <h4 class="mt-4 text-xs uppercase tracking-widest text-white/40">Days with their own link</h4>
                <ul class="mt-2 space-y-1 text-xs">
                    <?php foreach ($perDay as $d): ?>
                        <li class="break-all text-white/60">
                            <span class="text-white/40"><?= esc(date('j M', strtotime((string) $d['day_date']))) ?>:</span>
                            <?= esc($d['meeting_url']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p class="mt-3 text-xs text-white/40">Held encrypted in the database and decrypted for this page only.</p>
        </div>

        <?php if (! $cancelled): ?>
            <!-- One action, not a checklist. EnrolmentService cancels the date,
                 cancels every active enrolment on it and hands the seats back
                 inside one transaction, which is what the cancellation policy
                 promises the learner. -->
            <div class="rounded-2xl border border-brand-red/40 bg-brand-red/10 p-6">
                <h3 class="<?= $heading ?>">Cancel this date</h3>
                <form method="post" action="<?= site_url('admin/course-sessions/' . $s['id'] . '/cancel') ?>"
                      onsubmit="return confirm(<?= esc(json_encode(sprintf(
                          'Cancel this date? %s will be cancelled and will need refunding from Orders. This cannot be undone.',
                          count($roster) === 0 ? 'Nobody is booked' : count($roster) . ' booked learner(s)'
                      ), JSON_UNESCAPED_UNICODE), 'attr') ?>)">
                    <?= csrf_field() ?>
                    <label class="mt-3 block text-xs uppercase tracking-widest text-white/40">Reason</label>
                    <textarea name="reason" rows="3" required maxlength="255" class="<?= $input ?> mt-1"
                              placeholder="Below the minimum to run; instructor unwell; venue unavailable…"></textarea>
                    <p class="mt-1 text-xs text-white/40">Shown to everybody who was booked, and kept on the date.</p>

                    <button class="mt-4 w-full rounded-lg bg-brand-red px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-brand-red-dark">
                        <?= count($roster) === 0
                            ? 'Cancel this date — nobody is booked'
                            : 'Cancel this date — ' . count($roster) . ' ' . (count($roster) === 1 ? 'person is' : 'people are') . ' booked' ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<h2 class="mb-4 mt-10 text-lg font-semibold">Settings</h2>
<?= view('Modules\Admin\Views\sessions\form', [
    'embed'       => true,
    'row'         => $row,
    'courses'     => $courses,
    'venues'      => $venues,
    'instructors' => $instructors,
    'currencies'  => $currencies,
    'prices'      => $prices,
    'dayRows'     => $dayRows,
    'modes'       => $modes,
    'statuses'    => $statuses,
    'locales'     => $locales,
    'joiningUrl'  => $joiningUrl,
], ['saveData' => false]) ?>

<?= $this->endSection() ?>
