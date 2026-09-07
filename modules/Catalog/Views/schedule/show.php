<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One date, in full.
 *
 * This page answers a narrower question than the course page does. Somebody
 * here has already chosen the course; they are checking the practicalities
 * before they commit — which days exactly, what hours, where, in what timezone,
 * and is it certain to run. So the day-by-day breakdown and the venue come
 * first, the course description is a summary with a way through to the full
 * page, and the booking panel never leaves the screen.
 *
 * Times are shown twice: in the classroom's own zone and in UTC. A live online
 * class sold across Colombo, Dubai and London has three clocks around it, and a
 * page that prints one of them is a page somebody joins an hour late.
 *
 * @var array  $session  with days, course_slug, venue_*, price_cents, seats_left
 * @var array  $course
 * @var array  $detail   outcomes, includes, and the rest of the course lists
 * @var array|null $venue
 * @var list<array> $days
 * @var string $currency
 * @var bool   $bookable
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$seats = seats_note($session);
// seats_left is null on an unlimited (self-paced) session and 0 on a sold-out
// one, so the comparison has to be strict or every self-paced date reads as
// full.
$full  = ($session['seats_left'] ?? null) === 0;
$zone  = (string) ($session['timezone'] ?: 'Asia/Colombo');

// The seat spinner is capped at what is actually left, so the form cannot ask
// for eight places on a class with three. The hold is re-derived under a lock
// at the checkout regardless; this only moves the disappointment forward to
// where it can still be acted on. Twenty is the ceiling for an unlimited
// session — beyond that it is a group booking and belongs in a quote.
$maxSeats = max(1, min(20, (int) ($session['seats_left'] ?? 20) ?: 20));

/**
 * The class hours in UTC, or an empty string when no daily times are set.
 *
 * Derived from the start date rather than from today, because a zone's offset
 * moves: computing an October class against a July date is how a page ends up
 * an hour out for exactly the half of the year that has daylight saving in it.
 */
$utcTimes = '';
if (! empty($session['daily_start']) && ! empty($session['daily_end'])) {
    $tz      = safe_timezone($zone);
    $anchor  = (string) ($session['start_date'] ?: date('Y-m-d'));
    $utc     = new DateTimeZone('UTC');
    $utcTimes = (new DateTimeImmutable($anchor . ' ' . $session['daily_start'], $tz))->setTimezone($utc)->format('H:i')
        . '–' . (new DateTimeImmutable($anchor . ' ' . $session['daily_end'], $tz))->setTimezone($utc)->format('H:i')
        . ' UTC';
}
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => mode_label($session['mode']),
    'heading' => lang('Catalog.session.title', [t_field($course['title']), session_dates($session)]),
    'intro'   => t_field($course['summary']),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x grid gap-12 py-12 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">
    <div class="min-w-0 space-y-12">

        <?php if ($ok = session()->getFlashdata('waitlist_ok')): ?>
            <p role="status" class="rounded-2xl border border-brand-red/40 bg-brand-red/10 px-5 py-4 text-sm"><?= esc($ok) ?></p>
        <?php elseif ($problem = session()->getFlashdata('waitlist_error')): ?>
            <p role="alert" class="rounded-2xl border border-line bg-surface px-5 py-4 text-sm text-brand-red"><?= esc($problem) ?></p>
        <?php endif; ?>

        <?php // ── When ───────────────────────────────────────────────────────
              // The day-by-day rows, when the schedule carries them. A two-day
              // class that runs Friday and the following Friday is a different
              // proposition from one that runs Friday and Saturday, and a
              // start-and-end date alone cannot tell those apart. ?>
        <section aria-labelledby="when">
            <h2 id="when" class="section-title"><?= esc(lang('Catalog.dates.when')) ?></h2>

            <p class="mt-4 text-lg font-medium"><?= esc(session_dates($session)) ?></p>
            <?php if ($times = session_times($session)): ?>
                <p class="mt-1 text-white/70">
                    <?= esc($times) ?>
                    <?php if ($utcTimes !== ''): ?>
                        <span class="text-white/45"> · <?= esc($utcTimes) ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($days !== []): ?>
                <?php // Wrapped even though three columns rarely overflow: the
                      // page body must never be what scrolls sideways, and a
                      // long venue name in a fourth language is exactly the
                      // thing that makes it. ?>
                <div class="relative mt-5 overflow-x-auto rounded-2xl border border-line">
                    <table class="w-full border-collapse text-sm">
                        <caption class="sr-only"><?= esc(lang('Catalog.dates.caption')) ?></caption>
                        <thead>
                            <tr class="bg-surface text-left text-xs uppercase tracking-wider text-white/50">
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.when')) ?></th>
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.mode')) ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <?php foreach ($days as $day): ?>
                                <tr>
                                    <th scope="row" class="px-4 py-3 text-left font-medium">
                                        <?= esc((new DateTimeImmutable((string) $day['day_date']))->format('l j M Y')) ?>
                                    </th>
                                    <td class="px-4 py-3 tabular-nums text-white/70">
                                        <?php if (! empty($day['start_time'])): ?>
                                            <?= esc(substr((string) $day['start_time'], 0, 5)) ?>–<?= esc(substr((string) $day['end_time'], 0, 5)) ?>
                                        <?php else: ?>
                                            <?= esc($times ?: '') ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <a href="<?= esc(locale_url('schedule')) ?>" class="mt-4 inline-block text-sm text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Catalog.locations.all')) ?>
            </a>
        </section>

        <?php // ── Where ──────────────────────────────────────────────────── ?>
        <section aria-labelledby="where">
            <h2 id="where" class="section-title"><?= esc(lang('Catalog.dates.where')) ?></h2>

            <?php if ($venue !== null): ?>
                <div class="mt-5 rounded-2xl border border-line bg-surface p-6">
                    <p class="font-semibold"><?= esc($venue['name']) ?></p>

                    <?php // The address is the map link rather than sitting
                          // beside one. A link whose text is the address says
                          // where it goes without a label to explain it, and it
                          // is what a screen reader announces in a link list —
                          // where four identical "Map" links say nothing.
                          // rel="noopener" because the map opens in its own tab
                          // and would otherwise keep a handle on this one. ?>
                    <?php if ($address = publishable(t_field($venue['address'] ?? ''))): ?>
                        <?php if (! empty($venue['map_url'])): ?>
                            <a href="<?= esc($venue['map_url'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
                               class="mt-2 block whitespace-pre-line text-white/70 underline decoration-line underline-offset-4 hover:text-brand-red">
                                <?= esc($address) ?>
                            </a>
                        <?php else: ?>
                            <p class="mt-2 whitespace-pre-line text-white/70"><?= esc($address) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($directions = rich_text($venue['directions'] ?? '')): ?>
                        <div class="prose-site prose-sm mt-4 text-sm"><?= $directions ?></div>
                    <?php endif; ?>

                    <a href="<?= esc(locale_url('locations/' . $venue['slug'])) ?>" class="mt-4 inline-block text-sm text-brand-red underline decoration-line underline-offset-4">
                        <?= esc($venue['city'] ?: $venue['name']) ?>
                    </a>
                </div>
            <?php else: ?>
                <p class="mt-4 text-white/70">
                    <?= esc($session['mode'] === 'SELF_PACED' ? lang('Catalog.session.any_time') : lang('Catalog.dates.online')) ?>
                </p>
                <?php if ($session['mode'] === 'SELF_PACED'): ?>
                    <p class="mt-2 max-w-2xl text-sm text-white/60"><?= esc(lang('Catalog.panel.self_paced_note')) ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <p class="mt-3 text-sm text-white/50"><?= esc($zone) ?></p>
        </section>

        <?php // ── The course itself, in summary ──────────────────────────────
              // Summary rather than the whole description: the full page is one
              // click away and duplicating it here would put two near-identical
              // documents in the index for the same course. ?>
        <section aria-labelledby="about">
            <h2 id="about" class="section-title"><?= esc(lang('Catalog.course.overview')) ?></h2>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="chip"><?= esc(level_label((int) $course['level'])) ?></span>
                <?php if ($d = duration_label($course)): ?><span class="chip"><?= esc($d) ?></span><?php endif; ?>
                <?php if ($course['software_version']): ?><span class="chip"><?= esc($course['software_version']) ?></span><?php endif; ?>
            </div>

            <?php if ($summary = t_field($course['summary'])): ?>
                <p class="mt-4 max-w-2xl leading-relaxed text-white/75"><?= esc($summary) ?></p>
            <?php endif; ?>

            <?php if ($detail['outcomes'] !== []): ?>
                <h3 class="mt-8 text-sm font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Catalog.course.outcomes')) ?></h3>
                <ul class="mt-3 grid gap-x-8 gap-y-2 sm:grid-cols-2">
                    <?php foreach (array_slice($detail['outcomes'], 0, 6) as $outcome): ?>
                        <li class="flex gap-3 text-sm text-white/80">
                            <svg class="mt-1 h-4 w-4 shrink-0 text-brand-red" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10.5l4 4 8-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span><?= esc(t_field($outcome['text'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($detail['includes'] !== []): ?>
                <h3 class="mt-8 text-sm font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Catalog.course.included')) ?></h3>
                <ul class="mt-3 space-y-2 text-sm text-white/80">
                    <?php foreach ($detail['includes'] as $item): ?>
                        <li class="flex gap-3">
                            <svg class="mt-1 h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/><path d="M6.5 10.2l2.4 2.4 4.6-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span><?= esc(t_field($item['text'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="<?= esc(course_url($course['slug'])) ?>" class="btn-ghost mt-8">
                <?= esc(t_field($course['title'])) ?>
            </a>
        </section>
    </div>

    <?php // ── The booking panel ───────────────────────────────────────────────
          // Sticky from lg up, in normal flow on a phone where a floating box
          // would sit over the day-by-day table it is asking somebody to read. ?>
    <aside class="lg:sticky lg:top-28 lg:self-start">
        <div class="overflow-hidden rounded-3xl border border-line bg-surface shadow-lg shadow-brand-black/5">
            <div class="p-6">
                <?php if ($session['price_cents'] !== null): ?>
                    <p class="flex items-baseline gap-3">
                        <span class="text-3xl font-bold"><?= esc(money((int) $session['price_cents'], $currency)) ?></span>
                        <?php if (! empty($session['compare_at_cents']) && $session['compare_at_cents'] > $session['price_cents']): ?>
                            <span class="text-base text-white/40 line-through"><?= esc(money((int) $session['compare_at_cents'], $currency)) ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="mt-1 text-xs text-white/50"><?= esc(lang('Catalog.panel.per_seat')) ?></p>
                <?php else: ?>
                    <p class="text-white/70"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                <?php endif; ?>

                <dl class="mt-6 space-y-3 border-t border-line pt-5 text-sm">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-white/50"><?= esc(lang('Catalog.dates.mode')) ?></dt>
                        <dd class="text-right font-medium"><?= esc(mode_label($session['mode'])) ?></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-white/50"><?= esc(lang('Catalog.dates.where')) ?></dt>
                        <dd class="text-right font-medium">
                            <?= esc($session['venue_city'] ?? ($session['mode'] === 'CLASSROOM' ? '' : lang('Catalog.dates.online'))) ?>
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-white/50"><?= esc(lang('Catalog.dates.seats')) ?></dt>
                        <dd class="text-right font-medium <?= $seats['urgent'] ? 'text-gold' : '' ?>"><?= esc($seats['text']) ?></dd>
                    </div>
                </dl>

                <?php if (\Modules\Catalog\Models\CourseSessionModel::isConfirmed($session)): ?>
                    <?php // Drawn from the number booked rather than from the
                          // status column, so the promise is made by the data
                          // and not by whether somebody remembered to change a
                          // dropdown. ?>
                    <p class="mt-5 rounded-xl border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm font-medium text-brand-red">
                        <?= esc(lang('Catalog.dates.confirmed')) ?>
                    </p>
                <?php endif; ?>

                <?php if ($bookable && ! $full && $session['price_cents'] !== null): ?>
                    <form method="post" action="<?= esc(locale_url('cart/add')) ?>" class="mt-6">
                        <?= csrf_field() ?>
                        <input type="hidden" name="item_type" value="session">
                        <input type="hidden" name="item_id" value="<?= (int) $session['id'] ?>">
                        <label class="sr-only" for="seat-qty"><?= esc(lang('Catalog.panel.seats')) ?></label>
                        <div class="flex gap-2">
                            <input id="seat-qty" name="qty" type="number" min="1" inputmode="numeric" value="1"
                                   max="<?= $maxSeats ?>" class="field w-20 text-center">
                            <button type="submit" class="btn-brand flex-1"><?= esc(lang('Catalog.panel.book')) ?></button>
                        </div>
                    </form>
                <?php elseif ($session['price_cents'] === null): ?>
                    <a href="<?= esc(locale_url('corporate/request-quote') . '?course=' . rawurlencode((string) $course['slug'])) ?>" class="btn-brand mt-6 w-full">
                        <?= esc(lang('Catalog.panel.request_quote')) ?>
                    </a>
                <?php else: ?>
                    <?php // Full, finished, or withdrawn from sale. All three are
                          // the same thing to the reader — they cannot buy this
                          // date — and all three are worth an address, because
                          // the next running of the course is the thing they
                          // actually want. ?>
                    <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="mt-6 space-y-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                        <label class="sr-only" for="wl-email"><?= esc(lang('Catalog.panel.email')) ?></label>
                        <input id="wl-email" name="email" type="email" required autocomplete="email"
                               class="field" placeholder="<?= esc(lang('Catalog.panel.email'), 'attr') ?>">
                        <button type="submit" class="btn-brand w-full"><?= esc(lang('Catalog.dates.waitlist')) ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <ul class="space-y-1.5 border-t border-line px-6 py-4 text-xs text-white/55">
                <li><?= esc(lang('Catalog.panel.assure_transfer')) ?></li>
                <li><?= esc(lang('Catalog.panel.assure_retake')) ?></li>
                <li><?= esc(lang('Catalog.panel.assure_recording')) ?></li>
            </ul>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
