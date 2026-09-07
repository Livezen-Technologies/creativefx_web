<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * A pillar hub: /adobe, and /ai.
 *
 * These are the two pages everything else on the site points at, which makes
 * them the two pages most likely to be built as a wall of links and left at
 * that. A hub of links passes its authority straight through and gives a
 * visitor nothing to read, so this one is arranged as an answer to the four
 * questions somebody arriving from a search actually has, in the order they
 * have them: what is this track, what is in it, what should I take first, and
 * when can I come.
 *
 * Everything on it is derived. The counts are counted, the dates are the real
 * next dates, the "at a glance" panel is four facts read out of the database at
 * render time, and there is no number anywhere that somebody typed and will
 * forget to update.
 *
 * One view serves both pillars. They differ in their copy and in nothing else,
 * and two templates that differ by a heading are two templates that agree today
 * and disagree in six months.
 *
 * @var string      $pillar        adobe | ai
 * @var list<array> $tree          categories with `courses` and a subtree `count`
 * @var list<array> $featured      courses decorated with from_cents and next_date
 * @var list<array> $programmes    bundles with url, course_count and price
 * @var list<array> $dates         sessions with price_cents and seats_left
 * @var int         $dateTotal     every upcoming date in this track, not just those shown
 * @var list<array{label:string,value:string}> $facts
 * @var string      $catalogueUrl  /courses filtered to this pillar
 * @var string      $scheduleUrl   /schedule filtered to this pillar
 * @var string      $currency
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

// The introduction is a list of paragraphs in the language file rather than one
// long string, so a translator can restructure it and the page does not have to
// know how many there are. A translator who flattens it back to a string would
// otherwise be a foreach over a string, which is fatal rather than untidy.
$body = lang('Catalog.pillars.' . $pillar . '_body');
$body = is_array($body) ? $body : array_filter([is_string($body) ? $body : '']);

$pillarLabel = lang('Catalog.pillar.' . $pillar);

// The taxonomy is two deep in practice — the pillar branch, then the
// application — so the grid is drawn from each branch's children. A branch with
// no children is a card in its own right rather than an empty heading, which is
// what the tree looks like before anybody has entered the sub-categories.
$groups = [];
foreach ($tree as $branch) {
    $groups[] = [
        'branch' => $branch,
        'cards'  => $branch['children'] !== [] ? $branch['children'] : [$branch],
    ];
}

// page_head renders this unescaped, so every part of it is escaped here.
$aside = '<a href="' . esc($catalogueUrl, 'attr') . '" class="btn-brand">'
    . esc(lang('Catalog.pillars.explore')) . '</a>';
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Catalog.pillars.eyebrow'),
    'heading' => lang('Catalog.pillars.' . $pillar . '_title'),
    'intro'   => lang('Catalog.pillars.' . $pillar . '_intro'),
    'crumbs'  => $crumbs,
    'aside'   => $aside,
], ['saveData' => false]) ?>

<div class="container-x space-y-16 py-12 sm:space-y-20">

    <?php // ── The introduction, and the facts beside it ───────────────────── ?>
    <section class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-14">
        <div class="prose-site min-w-0 max-w-none">
            <?php foreach ($body as $paragraph): ?>
                <p><?= esc($paragraph) ?></p>
            <?php endforeach; ?>
        </div>

        <?php if ($facts !== []): ?>
            <?php // Counted, never typed. An entry that could not be derived is
                  // simply absent — a panel of three is honest, and a panel with
                  // an invented fourth is the number a competitor screenshots. ?>
            <aside class="h-fit rounded-2xl border border-line bg-surface p-6" aria-labelledby="glance">
                <h2 id="glance" class="text-xs font-semibold uppercase tracking-widest text-white/50">
                    <?= esc(lang('Catalog.pillars.at_a_glance')) ?>
                </h2>
                <dl class="mt-5 space-y-4">
                    <?php foreach ($facts as $fact): ?>
                        <div class="border-l-2 border-line pl-4">
                            <dt class="text-xs text-white/55"><?= esc($fact['label']) ?></dt>
                            <dd class="mt-0.5 font-semibold leading-snug"><?= esc($fact['value']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </aside>
        <?php endif; ?>
    </section>

    <?php // ── The category grid. The internal linking the SEO plan rests on. ─ ?>
    <section aria-labelledby="categories">
        <h2 id="categories" class="section-title"><?= esc(lang('Catalog.pillars.' . $pillar . '_categories')) ?></h2>

        <?php if ($groups === []): ?>
            <div class="mt-5 rounded-3xl border border-line bg-surface p-8 text-center sm:p-12">
                <p class="mx-auto max-w-md leading-relaxed text-white/70"><?= esc(lang('Catalog.pillars.categories_none')) ?></p>
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-6"><?= esc(lang('Catalog.courses.all')) ?></a>
            </div>
        <?php else: ?>
            <div class="mt-6 space-y-10">
                <?php foreach ($groups as $group): ?>
                    <div>
                        <?php // The branch heading renders even when there is only
                              // one branch. It is a real category with a page of
                              // its own, and dropping it would leave the card
                              // headings two levels below the section with nothing
                              // between them — a hole in the heading outline that
                              // a screen reader reads as a missing section rather
                              // than as a tidier page. ?>
                        <h3 class="text-lg font-semibold">
                            <a href="<?= esc(locale_url('courses/' . $group['branch']['slug'])) ?>" class="hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                <?= esc(t_field($group['branch']['name'])) ?>
                            </a>
                            <span class="ml-2 text-sm font-normal text-white/50"><?= esc(lang('Catalog.pillars.count', [(int) $group['branch']['count']])) ?></span>
                        </h3>

                        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($group['cards'] as $card): ?>
                                <?php
                                // The node's own courses. On a leaf — which is
                                // where every course actually sits — these are the
                                // whole of its count; on a branch standing in as a
                                // card because it has no children yet, the two are
                                // equal by definition.
                                $shown = array_slice($card['courses'], 0, 6);
                                ?>
                                <article class="flex flex-col rounded-2xl border border-line bg-surface p-5 transition hover:border-brand-red/40">
                                    <div class="flex items-start justify-between gap-3">
                                        <h4 class="text-base font-semibold leading-snug">
                                            <a href="<?= esc(locale_url('courses/' . $card['slug'])) ?>" class="hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                                <?= esc(t_field($card['name'])) ?>
                                            </a>
                                        </h4>
                                        <span class="chip shrink-0"><?= esc(lang('Catalog.pillars.count', [(int) $card['count']])) ?></span>
                                    </div>

                                    <?php if ($summary = t_field($card['summary'] ?? '')): ?>
                                        <p class="mt-2 text-sm leading-relaxed text-white/60"><?= esc($summary) ?></p>
                                    <?php endif; ?>

                                    <?php if ($shown !== []): ?>
                                        <?php // The courses themselves, named and
                                              // linked. This is what makes the hub
                                              // worth crawling: a grid of category
                                              // names tells a search engine nothing
                                              // the category pages do not already
                                              // say, and tells a reader nothing at
                                              // all about whether their course is
                                              // in there. ?>
                                        <ul class="mt-4 space-y-1.5 text-sm">
                                            <?php foreach ($shown as $course): ?>
                                                <li>
                                                    <a href="<?= esc(course_url($course['slug'])) ?>" class="text-white/75 underline decoration-line underline-offset-4 transition hover:text-brand-red">
                                                        <?= esc(t_field($course['title'])) ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <?php if ((int) $card['count'] > count($shown)): ?>
                                        <a href="<?= esc(locale_url('courses/' . $card['slug'])) ?>" class="mt-auto pt-4 text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                            <?= esc(lang('Catalog.pillars.all_courses', [t_field($card['name'])])) ?>
                                        </a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php // ── Where people start ──────────────────────────────────────────── ?>
    <section aria-labelledby="featured">
        <h2 id="featured" class="section-title"><?= esc(lang('Catalog.pillars.featured')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/65"><?= esc(lang('Catalog.pillars.featured_intro')) ?></p>

        <?php if ($featured === []): ?>
            <p class="mt-5 text-white/60"><?= esc(lang('Catalog.pillars.courses_none')) ?></p>
        <?php else: ?>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($featured as $course): ?>
                    <?= view('Modules\Catalog\Views\partials\course_card', [
                        'course' => $course + ['currency' => $currency],
                    ], ['saveData' => false]) ?>
                <?php endforeach; ?>
            </div>

            <a href="<?= esc($catalogueUrl) ?>" class="btn-ghost mt-8">
                <?= esc(lang('Catalog.pillars.all_courses', [$pillarLabel])) ?>
            </a>
        <?php endif; ?>
    </section>

    <?php // ── Certification. Adobe only, because only Adobe awards one. ───── ?>
    <?php if ($pillar === 'adobe'): ?>
        <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9" aria-labelledby="certification">
            <h2 id="certification" class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.pillars.cert_card')) ?></h2>
            <?php // Stated on the hub as well as on the hub it links to. This is
                  // the one claim on the site somebody could act on and lose
                  // money over, so it is not left to the page after this one. ?>
            <p class="mt-3 max-w-3xl leading-relaxed text-white/70"><?= esc(lang('Catalog.pillars.cert_card_text')) ?></p>
            <a href="<?= esc(locale_url('adobe/certification')) ?>" class="btn-brand mt-5">
                <?= esc(lang('Catalog.pillars.cert_link')) ?>
            </a>
        </section>
    <?php endif; ?>

    <?php // ── Programmes drawing on this track ────────────────────────────── ?>
    <section aria-labelledby="programmes">
        <h2 id="programmes" class="section-title"><?= esc(lang('Catalog.pillars.programmes')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/65"><?= esc(lang('Catalog.pillars.programmes_intro')) ?></p>

        <?php if ($programmes === []): ?>
            <p class="mt-5 max-w-2xl text-white/60"><?= esc(lang('Catalog.pillars.programmes_none')) ?></p>
        <?php else: ?>
            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <?php foreach ($programmes as $programme): ?>
                    <article class="group relative flex flex-col rounded-2xl border border-line bg-surface p-6 transition hover:border-brand-red/40">
                        <p class="flex flex-wrap items-center gap-2 text-xs text-white/50">
                            <span class="chip"><?= esc(lang('Catalog.pillars.programme_courses', [(int) $programme['course_count']])) ?></span>
                        </p>

                        <h3 class="mt-3 text-lg font-semibold leading-snug">
                            <?php // The stretched link: one tab stop per card, and
                                  // the whole card is the target for a pointer.
                                  // Nothing else inside may be a link. ?>
                            <a href="<?= esc($programme['url']) ?>" class="after:absolute after:inset-0 hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                <?= esc(t_field($programme['title'])) ?>
                            </a>
                        </h3>

                        <?php if ($summary = t_field($programme['summary'])): ?>
                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto pt-5">
                            <?php if ($programme['price'] !== null): ?>
                                <p class="text-xl font-bold"><?= esc(money((int) $programme['price']['price_cents'], $currency)) ?></p>
                            <?php else: ?>
                                <?php // Not converted from the other currency: see
                                      // PricingService for why nothing on this site
                                      // is priced at runtime. ?>
                                <p class="text-sm text-white/70"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php // ── The next dates. What turns a hub into a booking. ────────────── ?>
    <section aria-labelledby="dates">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 id="dates" class="section-title"><?= esc(lang('Catalog.pillars.dates')) ?></h2>
            <?php if ($dateTotal > count($dates)): ?>
                <a href="<?= esc($scheduleUrl) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                    <?= esc(lang('Catalog.schedule.count', [$dateTotal])) ?>
                </a>
            <?php endif; ?>
        </div>
        <p class="mt-3 max-w-2xl text-white/65"><?= esc(lang('Catalog.pillars.dates_intro')) ?></p>

        <?php if ($dates === []): ?>
            <?php // Never a dead end. "No dates available" loses a visitor on a
                  // training site more reliably than any other sentence, so the
                  // empty state says why and offers the way a date gets made. ?>
            <div class="mt-5 rounded-2xl border border-line bg-surface p-6">
                <p class="max-w-2xl leading-relaxed text-white/70"><?= esc(lang('Catalog.pillars.dates_none')) ?></p>
                <a href="<?= esc($catalogueUrl) ?>" class="btn-ghost mt-5"><?= esc(lang('Catalog.pillars.all_courses', [$pillarLabel])) ?></a>
            </div>
        <?php else: ?>
            <?php // The table is wider than a phone, so it scrolls inside its own
                  // box. The page body never scrolls sideways. ?>
            <div class="relative mt-6 overflow-x-auto rounded-2xl border border-line">
                <table class="w-full min-w-[52rem] border-collapse text-sm">
                    <caption class="sr-only"><?= esc(lang('Catalog.dates.caption')) ?></caption>
                    <thead>
                        <tr class="bg-surface text-left text-xs uppercase tracking-wider text-white/50">
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.when')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.courses.title')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.mode')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Catalog.dates.seats')) ?></th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold"><?= esc(lang('Catalog.dates.price')) ?></th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only"><?= esc(lang('Catalog.dates.book')) ?></span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <?php foreach ($dates as $session):
                            $seats = seats_note($session);
                            // seats_left is null on an unlimited (self-paced)
                            // session and 0 on a sold-out one. Only the second
                            // closes the button, so the comparison is strict. ?>
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
                                <td class="px-4 py-3 text-white/70">
                                    <?= esc(mode_label($session['mode'])) ?>
                                    <span class="block text-xs text-white/45">
                                        <?= esc($session['venue_city'] ?? ($session['mode'] === 'CLASSROOM' ? '' : lang('Catalog.dates.online'))) ?>
                                    </span>
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
                                        <span class="text-xs font-normal text-white/50"><?= esc(lang('Catalog.panel.price_on_request')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <?php // The order of these three branches is
                                          // the order they have to be tested in. A
                                          // date with no price in this visitor's
                                          // currency cannot be added to a basket
                                          // at all, whether or not it has seats,
                                          // so it is asked first. ?>
                                    <?php if ($session['price_cents'] === null): ?>
                                        <a href="<?= esc(locale_url('corporate/request-quote') . '?course=' . rawurlencode((string) $session['course_slug'])) ?>"
                                           class="btn-ghost px-5 py-2 text-xs">
                                            <?= esc(lang('Catalog.panel.request_quote')) ?>
                                        </a>
                                    <?php elseif (($session['seats_left'] ?? null) === 0): ?>
                                        <?php // A full class is a lead rather than
                                              // a dead end, and the address field
                                              // is on the form rather than behind a
                                              // second page: the willingness to
                                              // leave one lasts about as long as
                                              // the disappointment does. The
                                              // waitlist controller redirects to
                                              // the session's own page afterwards,
                                              // not back here, which is where the
                                              // confirmation belongs. ?>
                                        <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="flex flex-wrap justify-end gap-2">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                            <label class="sr-only" for="pw-<?= (int) $session['id'] ?>"><?= esc(lang('Catalog.panel.email')) ?></label>
                                            <input id="pw-<?= (int) $session['id'] ?>" name="email" type="email" required autocomplete="email"
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

            <a href="<?= esc($scheduleUrl) ?>" class="mt-6 inline-block text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Catalog.pillars.schedule_all')) ?>
            </a>
        <?php endif; ?>
    </section>

    <?php // ── The two ways out: buy a seat, or ask for a team quote ───────── ?>
    <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.pillars.team_heading')) ?></h2>
        <p class="mt-3 max-w-2xl leading-relaxed text-white/70"><?= esc(lang('Catalog.pillars.team_text')) ?></p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-brand"><?= esc(lang('Catalog.pillars.team_cta')) ?></a>
            <a href="<?= esc($catalogueUrl) ?>" class="btn-ghost"><?= esc(lang('Catalog.pillars.all_courses', [$pillarLabel])) ?></a>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
