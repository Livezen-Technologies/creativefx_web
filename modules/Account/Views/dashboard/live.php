<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The joining page for one booked class.
 *
 * The rule this page follows is the same one the joining email follows: **every
 * question somebody has at 08:50 on the morning of the course is answered
 * before they have to scroll.** When, in whose clock, where, how to get in, and
 * what they were meant to install. Everything else — the materials, the
 * recording, the request to move dates — comes after that.
 *
 * Two things here are load-bearing.
 *
 * **Two clocks, always.** A live class sold into Colombo, Dubai and London has
 * three of them, and a page that prints one is a page somebody joins an hour
 * late. The class's own zone is on the left because that is the time the
 * instructor will say out loud; the learner's is beside it. When a conversion
 * lands on a different calendar day the date is printed with it, because "07:30"
 * with no date is the single easiest way to miss a class by twenty-four hours.
 * With no timezone on the profile the second clock is UTC and the page says so,
 * rather than guessing from a browser header and being quietly wrong for anybody
 * who is travelling.
 *
 * **The joining link is decrypted by the controller, and only for somebody who
 * holds an enrolment on this session.** It is a key to a paid classroom. The
 * ciphertext never reaches this template, and a signed-in visitor who is not
 * booked never reaches this page at all.
 *
 * @var array       $enrolment
 * @var array       $session     with days (each carrying `join_url`), course_*, venue_*
 * @var string      $joinUrl     decrypted, or '' when there is none to show
 * @var bool        $online
 * @var list<array> $prepare     course prerequisites
 * @var list<array> $materials   downloadable assets across the course's lessons
 * @var bool        $hasLessons
 * @var list<array> $alternatives
 * @var bool        $transferFree
 * @var int|null    $transferFee minor units, or null when none is published
 * @var string      $currency
 * @var array|null  $pending     an undecided reschedule request, if there is one
 * @var string|null $learnerTz
 * @var string      $current
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$classZoneName = (string) ($session['timezone'] ?: 'Asia/Colombo');
$classZone     = safe_timezone($classZoneName);
$secondZone    = safe_timezone($learnerTz, 'UTC');

// Printing the same time twice is noise, so the second column only appears when
// it says something different.
$showSecond = ($learnerTz ?? 'UTC') !== $classZoneName;

$today     = date('Y-m-d');
$cancelled = (string) $session['status'] === 'cancelled';
$ended     = ! empty($session['end_date']) && $session['end_date'] < $today;
$movable   = ! $cancelled && ! $ended && (string) $enrolment['status'] === 'active';

/**
 * One time in the second zone, with the date when the conversion crosses
 * midnight.
 *
 * Derived from the class's own date rather than from today, because an offset
 * moves: converting an October class against a July date is how a page ends up
 * an hour out for exactly the half of the year that has daylight saving in it.
 *
 * @return array{time:string, date:?string, abbr:string}|null
 */
$convert = static function (?string $date, ?string $time) use ($classZone, $secondZone): ?array {
    if (empty($date) || empty($time)) {
        return null;
    }

    $source = new DateTimeImmutable($date . ' ' . $time, $classZone);
    $there  = $source->setTimezone($secondZone);

    return [
        'time' => $there->format('H:i'),
        'date' => $there->format('Y-m-d') === $source->format('Y-m-d') ? null : $there->format('D j M'),
        'abbr' => $there->format('T'),
    ];
};

// The per-day rows, when the class has them. A session with no session_days is
// shown as one span rather than as a guessed day-per-calendar-day: a two-week
// course does not run on the Sunday in the middle, and inventing that row would
// be worse than not having one.
$days = [];
foreach ($session['days'] as $day) {
    if (empty($day['day_date'])) {
        continue;
    }
    $days[] = [
        'date'  => (string) $day['day_date'],
        'start' => (string) ($day['start_time'] ?: $session['daily_start']),
        'end'   => (string) ($day['end_time'] ?: $session['daily_end']),
        'join'  => (string) ($day['join_url'] ?? ''),
    ];
}

$noticeDays = \Modules\Learning\Models\RescheduleRequestModel::FREE_NOTICE_BUSINESS_DAYS;
$venueLines = array_filter([
    (string) ($session['venue_name'] ?? ''),
    t_field($session['venue_address'] ?? ''),
    trim(((string) ($session['venue_city'] ?? '')) . ' ' . ((string) ($session['venue_country'] ?? ''))),
]);
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => mode_label((string) $session['mode']),
    'heading' => t_field($session['course_title']),
    'intro'   => session_dates($session),
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-14 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-14">

    <?= view('Modules\Account\Views\partials\account_nav', ['current' => $current], ['saveData' => false]) ?>

    <div class="min-w-0 space-y-12">

        <?php if ($msg = session()->getFlashdata('notice')): ?>
            <p role="status" class="rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <?php // ── The class was called off ───────────────────────────────── ?>
        <?php if ($cancelled): ?>
            <section role="alert" aria-labelledby="cancelled" class="rounded-3xl border border-brand-red bg-brand-red/10 p-6 sm:p-7">
                <h2 id="cancelled" class="text-lg font-bold"><?= esc(lang('Account.live.cancelled_heading')) ?></h2>
                <p class="mt-3 text-white/80"><?= esc(lang('Account.live.cancelled_body')) ?></p>
                <?php if (! empty($session['cancel_reason'])): ?>
                    <p class="mt-3 text-sm text-white/70"><?= esc($session['cancel_reason']) ?></p>
                <?php endif; ?>
                <a href="<?= esc(course_url((string) $session['course_slug'])) ?>" class="btn-brand mt-5">
                    <?= esc(lang('Account.live.cancelled_action')) ?>
                </a>
            </section>
        <?php endif; ?>

        <?php // ── When ───────────────────────────────────────────────────── ?>
        <section aria-labelledby="when">
            <h2 id="when" class="section-title"><?= esc(lang('Account.live.when')) ?></h2>

            <?php if ($days !== []): ?>
                <div class="relative mt-5 overflow-x-auto rounded-2xl border border-line">
                    <table class="w-full min-w-[32rem] border-collapse text-sm">
                        <caption class="sr-only"><?= esc(lang('Account.live.when_caption')) ?></caption>
                        <thead>
                            <tr class="border-b border-line bg-surface text-left text-xs uppercase tracking-wider text-white/55">
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.live.col_day')) ?></th>
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.live.col_class_time', [$classZoneName])) ?></th>
                                <?php if ($showSecond): ?>
                                    <th scope="col" class="px-4 py-3 font-semibold">
                                        <?= esc($learnerTz !== null
                                            ? lang('Account.live.col_your_time', [$learnerTz])
                                            : lang('Account.live.col_utc')) ?>
                                    </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <?php foreach ($days as $i => $day): ?>
                                <?php
                                $from = $convert($day['date'], $day['start']);
                                $to   = $convert($day['date'], $day['end']);
                                ?>
                                <tr>
                                    <th scope="row" class="px-4 py-3 text-left font-medium">
                                        <?= esc(lang('Account.live.day_n', [$i + 1])) ?>
                                        <span class="block text-xs font-normal text-white/60">
                                            <?= esc((new DateTimeImmutable($day['date']))->format('D j M Y')) ?>
                                        </span>
                                    </th>
                                    <td class="px-4 py-3 tabular-nums text-white/80">
                                        <?php if ($day['start'] !== '' && $day['end'] !== ''): ?>
                                            <?= esc(substr($day['start'], 0, 5)) ?>–<?= esc(substr($day['end'], 0, 5)) ?>
                                        <?php else: ?>
                                            <span class="text-white/45"><?= esc(lang('Account.live.times_tbc')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($showSecond): ?>
                                        <td class="px-4 py-3 tabular-nums text-white/80">
                                            <?php if ($from !== null && $to !== null): ?>
                                                <?= esc($from['time']) ?>–<?= esc($to['time']) ?> <span class="text-white/50"><?= esc($from['abbr']) ?></span>
                                                <?php // The date, printed only when the conversion
                                                      // lands on a different day. Without it a learner
                                                      // in Los Angeles reads "20:30" and misses the
                                                      // class by twenty-four hours. ?>
                                                <?php if ($from['date'] !== null): ?>
                                                    <span class="block text-xs text-gold"><?= esc($from['date']) ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-white/45">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="mt-5 rounded-2xl border border-line bg-surface p-5">
                    <p class="text-lg font-semibold"><?= esc(session_dates($session)) ?></p>
                    <?php if ($times = session_times($session)): ?>
                        <p class="mt-1 text-white/70"><?= esc($times) ?></p>
                    <?php endif; ?>
                    <?php
                    $from = $convert($session['start_date'] ?? null, $session['daily_start'] ?? null);
                    $to   = $convert($session['start_date'] ?? null, $session['daily_end'] ?? null);
                    ?>
                    <?php if ($showSecond && $from !== null && $to !== null): ?>
                        <p class="mt-1 text-white/70">
                            <?= esc($from['time']) ?>–<?= esc($to['time']) ?> <?= esc($from['abbr']) ?>
                            <?= esc($learnerTz !== null ? lang('Account.live.in_your_time') : lang('Account.live.in_utc')) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($learnerTz === null): ?>
                <?php // Asked rather than assumed. A browser header would give a
                      // zone, and it would be the wrong one for anybody reading
                      // this in an airport. ?>
                <p class="mt-3 text-sm text-white/55">
                    <?= esc(lang('Account.live.set_timezone')) ?>
                    <a href="<?= esc(locale_url('account/profile')) ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4">
                        <?= esc(lang('Account.live.set_timezone_action')) ?>
                    </a>
                </p>
            <?php endif; ?>
        </section>

        <?php // ── How to get in, or where to go ──────────────────────────── ?>
        <section aria-labelledby="where">
            <h2 id="where" class="section-title"><?= esc(lang('Account.live.where')) ?></h2>

            <div class="mt-5 rounded-2xl border border-line bg-surface p-5 sm:p-6">
                <?php if ($online && ! $cancelled): ?>
                    <?php if ($joinUrl !== ''): ?>
                        <p class="text-white/75"><?= esc(lang('Account.live.join_intro')) ?></p>
                        <a href="<?= esc($joinUrl, 'attr') ?>" class="btn-brand mt-4" rel="noopener noreferrer" target="_blank">
                            <?= esc(lang('Account.live.join_now')) ?>
                        </a>
                        <?php if (! empty($session['meeting_provider'])): ?>
                            <p class="mt-3 text-sm text-white/55"><?= esc(lang('Account.live.provider', [$session['meeting_provider']])) ?></p>
                        <?php endif; ?>
                        <p class="mt-3 text-sm text-white/55"><?= esc(lang('Account.live.join_note')) ?></p>
                    <?php else: ?>
                        <?php // No link configured, or the key that encrypted it
                              // has changed. Either way it cannot be shown, and
                              // saying so beats an empty button. ?>
                        <p class="text-white/75"><?= esc(lang('Account.live.join_pending')) ?></p>
                    <?php endif; ?>

                    <?php // A per-day link, when the class uses a different room
                          // each day. Only rendered where one actually differs
                          // from the session's own. ?>
                    <?php $perDay = array_values(array_filter($days, static fn (array $d): bool => $d['join'] !== '' && $d['join'] !== $joinUrl)); ?>
                    <?php if ($perDay !== []): ?>
                        <ul class="mt-5 space-y-2 border-t border-line pt-4 text-sm">
                            <?php foreach ($perDay as $day): ?>
                                <li>
                                    <span class="text-white/60"><?= esc((new DateTimeImmutable($day['date']))->format('D j M')) ?>:</span>
                                    <a href="<?= esc($day['join'], 'attr') ?>" rel="noopener noreferrer" target="_blank" class="font-medium text-brand-red underline decoration-line underline-offset-4">
                                        <?= esc(lang('Account.live.join_this_day')) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                <?php elseif ($venueLines !== []): ?>
                    <address class="not-italic leading-relaxed text-white/80">
                        <?php foreach ($venueLines as $line): ?>
                            <?= esc(trim($line)) ?><br>
                        <?php endforeach; ?>
                    </address>
                    <?php if (! empty($session['map_url'])): ?>
                        <a href="<?= esc($session['map_url'], 'attr') ?>" rel="noopener noreferrer" target="_blank" class="mt-4 inline-block font-medium text-brand-red underline decoration-line underline-offset-4">
                            <?= esc(lang('Account.live.directions')) ?>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-white/75"><?= esc(lang('Account.live.where_pending')) ?></p>
                <?php endif; ?>
            </div>
        </section>

        <?php // ── What to have ready ─────────────────────────────────────── ?>
        <?php if ($prepare !== [] || ! empty($session['notes'])): ?>
            <section aria-labelledby="prepare">
                <h2 id="prepare" class="section-title"><?= esc(lang('Account.live.prepare')) ?></h2>

                <?php if ($prepare !== []): ?>
                    <ul class="mt-5 space-y-2.5 text-white/80">
                        <?php foreach ($prepare as $item): ?>
                            <li class="flex gap-3">
                                <svg class="mt-1 h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/><path d="M6.5 10.2l2.4 2.4 4.6-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span><?= esc(t_field($item['text'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (! empty($session['notes'])): ?>
                    <p class="mt-4 rounded-xl border border-line bg-surface px-4 py-3 text-sm leading-relaxed text-white/75">
                        <?= nl2br(esc($session['notes'])) ?>
                    </p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php // ── Materials and the recording ────────────────────────────── ?>
        <section aria-labelledby="materials">
            <h2 id="materials" class="section-title"><?= esc(lang('Account.live.materials')) ?></h2>

            <?php if ($materials !== []): ?>
                <ul class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line">
                    <?php foreach ($materials as $asset): ?>
                        <li class="flex flex-wrap items-center justify-between gap-3 bg-surface px-5 py-3.5">
                            <span class="min-w-0">
                                <span class="block truncate font-medium"><?= esc($asset['label'] ?: lang('Account.live.material_untitled')) ?></span>
                                <span class="text-xs uppercase tracking-wider text-white/50"><?= esc($asset['kind']) ?></span>
                            </span>
                            <?php // Through the player's asset route, which
                                  // checks the enrolment again on its way to a
                                  // file that is not in the web root. ?>
                            <a href="<?= esc(locale_url('learn/' . $session['course_slug'] . '/asset/' . (int) $asset['id'])) ?>"
                               class="shrink-0 text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                <?= esc(lang('Account.live.download')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="mt-4 max-w-2xl text-white/60"><?= esc(lang('Account.live.materials_none')) ?></p>
            <?php endif; ?>

            <?php if ($hasLessons): ?>
                <a href="<?= esc(locale_url('learn/' . $session['course_slug'])) ?>" class="btn-ghost mt-5">
                    <?= esc($ended ? lang('Account.live.watch_recording') : lang('Account.live.open_library')) ?>
                </a>
            <?php elseif ($ended): ?>
                <?php // The class is over and nothing has been published yet.
                      // Said as a fact rather than as a promise with a date on
                      // it, because the date is not this page's to give. ?>
                <p class="mt-4 text-sm text-white/55"><?= esc(lang('Account.live.recording_pending')) ?></p>
            <?php endif; ?>
        </section>

        <?php // ── Moving to another date ─────────────────────────────────── ?>
        <?php if ($pending !== null): ?>
            <section aria-labelledby="transfer" class="rounded-3xl border border-line bg-surface p-6 sm:p-7">
                <h2 id="transfer" class="text-lg font-bold"><?= esc(lang('Account.transfer.pending_heading')) ?></h2>
                <p class="mt-3 text-white/75"><?= esc(lang('Account.transfer.pending_body')) ?></p>
                <?php if ((int) $pending['fee_cents'] > 0): ?>
                    <p class="mt-3 text-sm text-white/60">
                        <?= esc(lang('Account.transfer.pending_fee', [money((int) $pending['fee_cents'], (string) ($pending['currency'] ?: $currency))])) ?>
                    </p>
                <?php endif; ?>
            </section>

        <?php elseif ($movable): ?>
            <section aria-labelledby="transfer">
                <h2 id="transfer" class="section-title"><?= esc(lang('Account.transfer.heading')) ?></h2>

                <?php // The policy, stated before the form rather than after it.
                      // Somebody deciding whether to ask needs to know what it
                      // will cost them, and the answer depends on the date they
                      // are leaving, not the one they are asking for. ?>
                <p class="mt-3 max-w-2xl text-white/70">
                    <?php if ($transferFree): ?>
                        <?= esc(lang('Account.transfer.free_note', [$noticeDays])) ?>
                    <?php elseif ($transferFee !== null): ?>
                        <?= esc(lang('Account.transfer.fee_note', [$noticeDays, money($transferFee, $currency)])) ?>
                    <?php else: ?>
                        <?= esc(lang('Account.transfer.fee_note_tbc', [$noticeDays])) ?>
                    <?php endif; ?>
                </p>

                <details class="group mt-5 overflow-hidden rounded-2xl border border-line bg-surface">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold">
                        <span><?= esc(lang('Account.transfer.open_form')) ?></span>
                        <svg class="h-4 w-4 shrink-0 text-white/40 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>

                    <form method="post" action="<?= esc(locale_url('account/transfer')) ?>" class="space-y-5 border-t border-line px-5 py-5">
                        <?= csrf_field() ?>
                        <input type="hidden" name="enrolment_id" value="<?= (int) $enrolment['id'] ?>">

                        <label class="block">
                            <span class="field-label"><?= esc(lang('Account.transfer.to_date')) ?></span>
                            <select name="to_session_id" class="field" id="to_session_id">
                                <?php // "Any later date" first and selected, so
                                      // somebody who only knows they cannot make
                                      // this one can still ask. Forcing a choice
                                      // here loses the request and gains nothing. ?>
                                <option value=""><?= esc(lang('Account.transfer.to_date_any')) ?></option>
                                <?php foreach ($alternatives as $alternative): ?>
                                    <option value="<?= (int) $alternative['id'] ?>">
                                        <?= esc(session_dates($alternative)) ?> — <?= esc(mode_label((string) $alternative['mode'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($alternatives === []): ?>
                                <span class="mt-1.5 block text-xs text-white/55"><?= esc(lang('Account.transfer.no_alternatives')) ?></span>
                            <?php endif; ?>
                        </label>

                        <label class="block">
                            <span class="field-label"><?= esc(lang('Account.transfer.reason')) ?></span>
                            <textarea name="reason" rows="3" maxlength="1000" class="field"
                                      placeholder="<?= esc(lang('Account.transfer.reason_hint'), 'attr') ?>"></textarea>
                        </label>

                        <button type="submit" class="btn-brand"><?= esc(lang('Account.transfer.submit')) ?></button>

                        <p class="text-xs text-white/50"><?= esc(lang('Account.transfer.submit_note')) ?></p>
                    </form>
                </details>
            </section>
        <?php endif; ?>

        <div>
            <a href="<?= esc(locale_url('account/courses')) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Account.live.back_to_courses')) ?>
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
