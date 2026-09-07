<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The whole calendar.
 *
 * A visitor here has stopped asking "what should I learn" and started asking
 * "which week can I go". That is a scheduling question, and scheduling
 * questions are answered by a table read downwards — not by a grid of cards,
 * which forces the eye sideways across every date to compare two of them.
 *
 * So: one table per month, with the month heading stuck to the top of the
 * viewport while its rows scroll past. Somebody four hundred pixels into
 * November should never have to scroll back up to find out that it is November.
 *
 * Every row books in one click, and a full row offers the waitlist in the same
 * column, because the instant somebody learns a class is full is the only
 * instant they will ever be willing to leave an address.
 *
 * @var list<array{key:string,label:string,rows:list<array>}> $months
 * @var int    $total
 * @var int    $page
 * @var int    $perPage
 * @var array  $filters
 * @var string $currency
 * @var list<string> $modes
 * @var list<string> $cities
 * @var list<array{id:int,title:string}> $courses
 * @var list<array{value:string,label:string}> $months_options
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$pages = max(1, (int) ceil($total / max(1, $perPage)));

/**
 * A link to another page of this same filtered view.
 *
 * Only the five named facets are carried. `from` and `to` are derived from
 * `month` by the controller and putting them in the link as well would give one
 * view two addresses — which is the duplicate the canonical exists to stop.
 */
$pageUrl = static function (int $target) use ($filters): string {
    $query = array_filter([
        'mode'   => $filters['mode'] ?? null,
        'pillar' => $filters['pillar'] ?? null,
        'city'   => $filters['city'] ?? null,
        'course' => $filters['course'] ?? null,
        'month'  => $filters['month'] ?? null,
        'page'   => $target > 1 ? $target : null,
    ]);

    return locale_url('schedule') . ($query === [] ? '' : '?' . http_build_query($query));
};

// A course filter turns the empty state from an apology into a form: we know
// exactly which course produced no dates, so we can offer to tell them when one
// is set. Without it there is nothing to attach an address to.
$filteredCourse = (int) ($filters['course'] ?? 0);
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Catalog.course.dates'),
    'heading' => lang('Catalog.schedule.title'),
    'intro'   => lang('Catalog.schedule.intro'),
    'crumbs'  => $crumbs,
    'aside'   => $total > 0
        ? '<p class="text-sm text-white/60">' . esc(lang('Catalog.schedule.count', [$total])) . '</p>'
        : '',
], ['saveData' => false]) ?>

<div class="container-x py-12">

    <?php // The waitlist posts from here, from the course page and from the
          // session page, and lands back wherever it came from. Rendered before
          // the filters so a confirmation is the first thing on the page rather
          // than something found by scrolling. ?>
    <?php if ($ok = session()->getFlashdata('waitlist_ok')): ?>
        <p role="status" class="mb-8 rounded-2xl border border-brand-red/40 bg-brand-red/10 px-5 py-4 text-sm">
            <?= esc($ok) ?>
        </p>
    <?php elseif ($problem = session()->getFlashdata('waitlist_error')): ?>
        <p role="alert" class="mb-8 rounded-2xl border border-line bg-surface px-5 py-4 text-sm text-brand-red">
            <?= esc($problem) ?>
        </p>
    <?php endif; ?>

    <?php // ── Filters ────────────────────────────────────────────────────────
          // A plain GET form with a real submit button rather than selects that
          // navigate on change: change-to-navigate is unusable with a keyboard,
          // where arrowing through a list of months fires a page load on every
          // option passed. There is deliberately no `page` field, so narrowing
          // the calendar always returns to the first page of the new result —
          // carrying page 4 into a filter that has two pages is an empty page
          // that looks like a broken filter. ?>
    <form method="get" action="<?= esc(locale_url('schedule')) ?>"
          aria-label="<?= esc(lang('Catalog.courses.filters'), 'attr') ?>"
          class="grid gap-4 rounded-2xl border border-line bg-surface p-5 sm:grid-cols-2 lg:grid-cols-6">

        <div>
            <label class="field-label" for="f-month"><?= esc(lang('Catalog.schedule.month')) ?></label>
            <select id="f-month" name="month" class="field">
                <option value=""><?= esc(lang('Catalog.schedule.all')) ?></option>
                <?php foreach ($months_options as $option): ?>
                    <option value="<?= esc($option['value'], 'attr') ?>" <?= ($filters['month'] ?? '') === $option['value'] ? 'selected' : '' ?>>
                        <?= esc($option['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="field-label" for="f-mode"><?= esc(lang('Catalog.courses.mode')) ?></label>
            <select id="f-mode" name="mode" class="field">
                <option value=""><?= esc(lang('Catalog.courses.any')) ?></option>
                <?php foreach ($modes as $m): ?>
                    <option value="<?= esc($m, 'attr') ?>" <?= ($filters['mode'] ?? '') === $m ? 'selected' : '' ?>>
                        <?= esc(mode_label($m)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="field-label" for="f-pillar"><?= esc(lang('Catalog.courses.pillar')) ?></label>
            <select id="f-pillar" name="pillar" class="field">
                <option value=""><?= esc(lang('Catalog.courses.any')) ?></option>
                <?php foreach (['adobe', 'ai', 'design'] as $p): ?>
                    <option value="<?= esc($p, 'attr') ?>" <?= ($filters['pillar'] ?? '') === $p ? 'selected' : '' ?>>
                        <?= esc(lang('Catalog.pillar.' . $p)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php // Only offered when there is more than one city with a classroom
              // in it: a dropdown with a single choice is a control that cannot
              // change anything. ?>
        <?php if (count($cities) > 1): ?>
            <div>
                <label class="field-label" for="f-city"><?= esc(lang('Catalog.schedule.city')) ?></label>
                <select id="f-city" name="city" class="field">
                    <option value=""><?= esc(lang('Catalog.schedule.all')) ?></option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= esc($city, 'attr') ?>" <?= ($filters['city'] ?? '') === $city ? 'selected' : '' ?>>
                            <?= esc($city) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div>
            <label class="field-label" for="f-course"><?= esc(lang('Catalog.courses.title')) ?></label>
            <select id="f-course" name="course" class="field">
                <option value=""><?= esc(lang('Catalog.courses.all')) ?></option>
                <?php foreach ($courses as $option): ?>
                    <option value="<?= (int) $option['id'] ?>" <?= $filteredCourse === $option['id'] ? 'selected' : '' ?>>
                        <?= esc($option['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-end gap-3">
            <button type="submit" class="btn-brand px-5 py-2.5 text-xs"><?= esc(lang('Catalog.courses.filters')) ?></button>
            <?php if ($filters !== []): ?>
                <a href="<?= esc(locale_url('schedule')) ?>" class="text-xs text-white/60 underline decoration-line underline-offset-4 hover:text-brand-red">
                    <?= esc(lang('Catalog.courses.clear')) ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php // ── The calendar ───────────────────────────────────────────────── ?>
    <?php if ($months === []): ?>
        <div class="mt-10 rounded-2xl border border-line bg-surface p-7">
            <?php if ($filteredCourse > 0): ?>
                <?php // We know which course came back empty, so the empty
                      // state can be the one useful thing: a way to be told
                      // when it is scheduled. Names against a course are
                      // frequently the reason a date gets set at all. ?>
                <p class="max-w-2xl text-white/70"><?= esc(lang('Catalog.dates.none')) ?></p>
                <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="mt-5 flex flex-wrap gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="course_id" value="<?= $filteredCourse ?>">
                    <label class="sr-only" for="empty-email"><?= esc(lang('Catalog.panel.email')) ?></label>
                    <input id="empty-email" name="email" type="email" required autocomplete="email"
                           class="field max-w-xs flex-1" placeholder="<?= esc(lang('Catalog.panel.email'), 'attr') ?>">
                    <button type="submit" class="btn-brand"><?= esc(lang('Catalog.panel.tell_me')) ?></button>
                </form>
            <?php elseif ($filters !== []): ?>
                <p class="text-white/70"><?= esc(lang('Catalog.schedule.none')) ?></p>
                <p class="mt-2 text-sm text-white/55"><?= esc(lang('Catalog.courses.none_hint')) ?></p>
                <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost mt-5">
                    <?= esc(lang('Catalog.courses.clear')) ?>
                </a>
            <?php else: ?>
                <?php // Nothing is scheduled at all. Said plainly — a calendar
                      // that invents a date to look busy is a calendar somebody
                      // turns up for. The catalogue is where an interest can
                      // still be registered, so that is where this points. ?>
                <p class="text-white/70"><?= esc(lang('Catalog.webinars.none')) ?></p>
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-5">
                    <?= esc(lang('Catalog.courses.all')) ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($months as $month): ?>
            <section class="mt-10" aria-labelledby="month-<?= esc($month['key'] ?: 'any', 'attr') ?>">
                <?php // Outside the scrolling wrapper below, deliberately: an
                      // ancestor with overflow-x scrolls a sticky child with it
                      // and the heading would slide out of the viewport
                      // sideways. `top-16` is the height of the compact header
                      // bar, which is the state it is in by the time anything
                      // here is sticking. ?>
                <h2 id="month-<?= esc($month['key'] ?: 'any', 'attr') ?>"
                    class="sticky top-16 z-10 -mx-2 bg-brand-black/95 px-2 py-3 text-sm font-semibold uppercase tracking-widest text-white/60 backdrop-blur">
                    <?= esc($month['label']) ?>
                </h2>

                <div class="relative overflow-x-auto rounded-2xl border border-line">
                    <table class="w-full min-w-[58rem] border-collapse text-sm">
                        <caption class="sr-only"><?= esc(lang('Catalog.dates.caption')) ?></caption>
                        <thead>
                            <tr class="bg-surface text-left text-xs uppercase tracking-wider text-white/50">
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.when')) ?></th>
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.courses.title')) ?></th>
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.mode')) ?></th>
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.where')) ?></th>
                                <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.seats')) ?></th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold"><?= esc(lang('Catalog.dates.price')) ?></th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only"><?= esc(lang('Catalog.dates.book')) ?></span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <?php foreach ($month['rows'] as $session):
                                $seats = seats_note($session);
                                // seats_left is null on an unlimited (self-paced)
                                // session and 0 on a sold-out one; only the
                                // second of those closes the Book button, so the
                                // comparison has to be strict.
                                $full  = ($session['seats_left'] ?? null) === 0; ?>
                                <tr class="align-middle">
                                    <td class="px-4 py-3">
                                        <a href="<?= esc(session_url($session)) ?>" class="font-medium underline decoration-line underline-offset-4 hover:text-brand-red">
                                            <?= esc(session_dates($session)) ?>
                                        </a>
                                        <?php if ($times = session_times($session)): ?>
                                            <span class="block text-xs text-white/45"><?= esc($times) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="<?= esc(course_url($session['course_slug'])) ?>" class="font-medium hover:text-brand-red">
                                            <?= esc(t_field($session['course_title'])) ?>
                                        </a>
                                        <?php if ($levelLabel = level_label((int) $session['level'])): ?>
                                            <span class="block text-xs text-white/45"><?= esc($levelLabel) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-white/70"><?= esc(mode_label($session['mode'])) ?></td>
                                    <td class="px-4 py-3 text-white/70">
                                        <?= esc($session['venue_city'] ?? ($session['mode'] === 'CLASSROOM' ? '' : lang('Catalog.dates.online'))) ?>
                                    </td>
                                    <td class="px-4 py-3 <?= $seats['urgent'] ? 'font-semibold text-gold' : 'text-white/60' ?>">
                                        <?= esc($seats['text']) ?>
                                        <?php if (\Modules\Catalog\Models\CourseSessionModel::isConfirmed($session)): ?>
                                            <span class="mt-0.5 block text-xs font-medium text-brand-red"><?= esc(lang('Catalog.dates.confirmed')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">
                                        <?php if ($session['price_cents'] !== null): ?>
                                            <?= esc(money((int) $session['price_cents'], $currency)) ?>
                                        <?php else: ?>
                                            <?php // No price published in this visitor's currency. Said
                                                  // rather than converted — see PricingService — and the
                                                  // row stays, because a date that exists is information
                                                  // even when the price list is incomplete. ?>
                                            <span class="text-xs font-normal text-white/50"><?= esc(lang('Catalog.panel.price_on_request')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <?php if ($session['price_cents'] === null): ?>
                                            <a href="<?= esc(locale_url('corporate/request-quote') . '?course=' . rawurlencode((string) $session['course_slug'])) ?>"
                                               class="btn-ghost px-5 py-2 text-xs">
                                                <?= esc(lang('Catalog.panel.request_quote')) ?>
                                            </a>
                                        <?php elseif ($full): ?>
                                            <?php // A full class is a lead, not a dead end. The address
                                                  // field is on the form rather than behind a second
                                                  // page, because the willingness to leave one lasts
                                                  // about as long as the disappointment does. ?>
                                            <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="flex flex-wrap justify-end gap-2">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                                <label class="sr-only" for="wl-<?= (int) $session['id'] ?>"><?= esc(lang('Catalog.panel.email')) ?></label>
                                                <input id="wl-<?= (int) $session['id'] ?>" name="email" type="email" required autocomplete="email"
                                                       class="field w-40 py-2 text-xs" placeholder="<?= esc(lang('Catalog.panel.email'), 'attr') ?>">
                                                <button type="submit" class="btn-ghost shrink-0 px-4 py-2 text-xs"><?= esc(lang('Catalog.dates.waitlist')) ?></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?= esc(locale_url('cart/add')) ?>" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="item_type" value="session">
                                                <input type="hidden" name="item_id" value="<?= (int) $session['id'] ?>">
                                                <input type="hidden" name="qty" value="1">
                                                <button type="submit" class="btn-brand px-5 py-2 text-xs"><?= esc(lang('Catalog.dates.book')) ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($pages > 1): ?>
            <nav class="mt-10 flex items-center justify-between gap-4 border-t border-line pt-6 text-sm"
                 aria-label="<?= esc(lang('Catalog.schedule.title'), 'attr') ?>">
                <?php if ($page > 1): ?>
                    <a href="<?= esc($pageUrl($page - 1)) ?>" rel="prev" class="btn-ghost px-5 py-2 text-xs"><?= esc(lang('Site.search.prev')) ?></a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <p class="text-white/55"><?= esc(lang('Site.search.page_of', [$page, $pages])) ?></p>

                <?php if ($page < $pages): ?>
                    <a href="<?= esc($pageUrl($page + 1)) ?>" rel="next" class="btn-ghost px-5 py-2 text-xs"><?= esc(lang('Site.search.next')) ?></a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
