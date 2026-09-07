<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One city — or the online room.
 *
 * The argument this page has to win is narrow: somebody already wants the
 * training and is deciding whether coming here is practical. So it answers, in
 * the order the question is actually asked, what this place is (the venue's own
 * prose, written per city — a templated location page with the name swapped is
 * the definition of thin content), when anything runs here, how you would get
 * to it, and what tends to be taught here.
 *
 * The address is the part that has to be handled carefully. It is often the
 * copy's own "{to be confirmed}" placeholder rather than a street, and the
 * controller has already turned that into null: a location page with no address
 * block is honest, one printing a placeholder where an address goes looks
 * finished and sends somebody to the wrong building. Nothing here should
 * reintroduce it by reading the raw column.
 *
 * @var array        $venue
 * @var string|null  $address       already vetted; null means there is none to print
 * @var bool         $isOnlineRoom
 * @var list<array>  $sessions      priced, with seats_left and course_slug
 * @var list<array>  $courses       cards, with from_cents and next_date at this venue
 * @var string       $currency
 * @var list<array>  $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);


// The class's own clock, named and offset, because "09:00" means nothing to
// somebody reading this from another country and a schedule that assumes
// otherwise is how a person misses the first morning of a course they paid for.
try {
    $zone      = new DateTimeZone((string) ($venue['timezone'] ?: 'UTC'));
    $zoneLabel = str_replace('_', ' ', $zone->getName()) . ' (UTC' . (new DateTimeImmutable('now', $zone))->format('P') . ')';
} catch (Exception $e) {
    // An unparseable zone is a data problem in one row, not a reason to fail a
    // landing page; the raw value is still more use to a reader than nothing.
    $zoneLabel = (string) $venue['timezone'];
}
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => mode_label($isOnlineRoom || $venue['type'] === 'virtual' ? 'LIVE_ONLINE' : 'CLASSROOM'),
    'heading' => t_field($venue['heading']) ?: (string) $venue['name'],
    'intro'   => t_field($venue['summary'] ?? ''),
    'crumbs'  => $crumbs,
    'aside'   => $sessions !== []
        ? '<p class="text-sm text-white/60">' . esc(lang('Catalog.schedule.count', [count($sessions)])) . '</p>'
        : '',
], ['saveData' => false]) ?>

<div class="container-x space-y-14 py-12">

    <?php // ── The city's own prose ─────────────────────────────────────────
          // Deliberately not wrapped in a labelled <section>: the only heading
          // it could carry is the h1 already above it, and an sr-only copy of
          // that reads the page title twice to a screen reader for no gain.
          // Held to max-w-3xl because a measure the width of the dates table is
          // unreadable, and this is the half of the page that has to be read. ?>
    <?php if ($body = rich_text($venue['body'] ?? '')): ?>
        <div class="prose-site max-w-3xl"><?= $body ?></div>
    <?php endif; ?>

    <?php // ── What runs here ─────────────────────────────────────────────── ?>
    <section id="dates" aria-labelledby="dates-title">
        <h2 id="dates-title" class="section-title"><?= esc(lang('Catalog.locations.upcoming')) ?></h2>

        <?php if ($sessions === []): ?>
            <?php // No invented "coming soon". The two ways forward are the
                  // whole calendar, where another city or the online room
                  // almost certainly has the same course, and the catalogue. ?>
            <div class="mt-5 rounded-2xl border border-line bg-surface p-6">
                <p class="text-white/70"><?= esc(lang('Catalog.locations.none')) ?></p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="<?= esc(locale_url('schedule')) ?>" class="btn-brand"><?= esc(lang('Catalog.locations.all')) ?></a>
                    <a href="<?= esc(locale_url('courses')) ?>" class="btn-ghost"><?= esc(lang('Catalog.courses.all')) ?></a>
                </div>
            </div>
        <?php else: ?>
            <?php // The table scrolls inside its own box rather than widening
                  // the document: six columns cannot fit a phone, and a page
                  // body that scrolls sideways loses the reader's place in the
                  // prose above it. ?>
            <div class="relative mt-5 overflow-x-auto rounded-2xl border border-line">
                <table class="w-full min-w-[46rem] border-collapse text-sm">
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
                        <?php foreach ($sessions as $session):
                            $seats = seats_note($session);
                            // seats_left is null for an unlimited session, which
                            // is not the same as nought and must not read as
                            // full — hence the identity test.
                            $full = ($session['seats_left'] ?? null) === 0; ?>
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
                                    <a href="<?= esc(course_url((string) $session['course_slug'])) ?>" class="text-white/80 underline decoration-line underline-offset-4 hover:text-brand-red">
                                        <?= esc(t_field($session['course_title'])) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-white/70"><?= esc(mode_label($session['mode'])) ?></td>
                                <td class="px-4 py-3 <?= $seats['urgent'] ? 'font-semibold text-gold' : 'text-white/60' ?>">
                                    <?= esc($seats['text']) ?>
                                    <?php if (\Modules\Catalog\Models\CourseSessionModel::isConfirmed($session)): ?>
                                        <?php // The single most useful thing a
                                              // dated course can say: it removes
                                              // the fear of booking travel for a
                                              // class that gets cancelled. ?>
                                        <span class="mt-0.5 block text-xs font-medium text-brand-red"><?= esc(lang('Catalog.dates.confirmed')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">
                                    <?php if ($session['price_cents'] !== null): ?>
                                        <?= esc(money((int) $session['price_cents'], $currency)) ?>
                                    <?php else: ?>
                                        <?php // Not priced in this visitor's
                                              // currency. Said rather than
                                              // converted, and the row stays: a
                                              // date that exists is information
                                              // even when a price list is not
                                              // finished. ?>
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
                                        <?php // A full class is a lead rather
                                              // than a dead end, and the moment
                                              // somebody learns they cannot have
                                              // the seat is the only moment they
                                              // will leave an address. ?>
                                        <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                            <input type="hidden" name="course_id" value="<?= (int) $session['course_id'] ?>">
                                            <button type="submit" class="btn-ghost text-xs"><?= esc(lang('Catalog.dates.waitlist')) ?></button>
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

            <a href="<?= esc(locale_url('schedule')) ?>" class="mt-4 inline-block text-sm text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Catalog.locations.all')) ?>
            </a>
        <?php endif; ?>
    </section>

    <?php // ── How to find it ─────────────────────────────────────────────── ?>
    <section aria-labelledby="finding">
        <h2 id="finding" class="section-title"><?= esc(lang('Catalog.dates.where')) ?></h2>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div class="rounded-2xl border border-line bg-surface p-6">
                <p class="font-semibold"><?= esc($venue['name']) ?></p>

                <?php if ($isOnlineRoom || $venue['type'] === 'virtual'): ?>
                    <p class="mt-2 text-white/70">
                        <?= esc(lang('Catalog.locations.online_note')) ?>
                    </p>
                <?php elseif ($address !== null): ?>
                    <?php // The address is the map link rather than sitting
                          // beside one: a link whose text is the address says
                          // where it goes without a label, and it is what a
                          // screen reader announces in a list of links — where
                          // four identical "Map" links say nothing.
                          // rel="noopener" because the map opens in its own tab
                          // and would otherwise keep a handle on this one. ?>
                    <?php if (! empty($venue['map_url'])): ?>
                        <a href="<?= esc($venue['map_url'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
                           class="mt-2 block whitespace-pre-line text-white/70 underline decoration-line underline-offset-4 hover:text-brand-red">
                            <?= esc($address) ?>
                        </a>
                    <?php else: ?>
                        <p class="mt-2 whitespace-pre-line text-white/70"><?= esc($address) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <?php // No street to print. Saying what actually happens is
                          // more use to a reader than a placeholder pretending
                          // to be an address, and it is true: the room, the
                          // parking and the building access go out with the
                          // joining instructions for each date. ?>
                    <p class="mt-2 text-white/70">
                        <?= esc(lang('Catalog.locations.address_note')) ?>
                    </p>
                    <?php if (! empty($venue['map_url'])): ?>
                        <a href="<?= esc($venue['map_url'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
                           class="mt-3 inline-block text-sm text-brand-red underline decoration-line underline-offset-4">
                            <?= esc(lang('Catalog.locations.map')) ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($directions = rich_text($venue['directions'] ?? '')): ?>
                    <div class="prose-site prose-sm mt-4 text-sm"><?= $directions ?></div>
                <?php endif; ?>
            </div>

            <?php // The two practical facts that are true of every date here,
                  // so they are stated once rather than repeated in every row
                  // of the table above. ?>
            <dl class="rounded-2xl border border-line bg-surface p-6 text-sm">
                <dt class="text-white/50"><?= esc(lang('Catalog.locations.timezone')) ?></dt>
                <dd class="mt-1 font-medium text-white/80"><?= esc($zoneLabel) ?></dd>

                <?php if (! $isOnlineRoom && $venue['type'] === 'classroom' && (int) $venue['capacity'] > 0): ?>
                    <dt class="mt-4 text-white/50"><?= esc(lang('Catalog.locations.capacity')) ?></dt>
                    <dd class="mt-1 font-medium text-white/80">
                        <?= esc(lang('Catalog.locations.capacity_note', [(int) $venue['capacity']])) ?>
                    </dd>
                <?php endif; ?>
            </dl>
        </div>
    </section>

    <?php // ── What tends to be taught here ───────────────────────────────── ?>
    <?php if ($courses !== []): ?>
        <section aria-labelledby="courses-here">
            <h2 id="courses-here" class="section-title"><?= esc(lang('Catalog.locations.courses_here')) ?></h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($courses as $course): ?>
                    <?php // Prices and next dates on these cards are scoped to
                          // this venue by the controller, so a card here never
                          // quotes a date that runs somewhere else. ?>
                    <?= view('Modules\Catalog\Views\partials\course_card', ['course' => $course], ['saveData' => false]) ?>
                <?php endforeach; ?>
            </div>

            <a href="<?= esc(locale_url('courses')) ?>" class="mt-5 inline-block text-sm text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Catalog.courses.all')) ?>
            </a>
        </section>
    <?php endif; ?>

    <?php // The enquiry a city page generates more than any other: a team that
          // would rather we came to them. Saying so here costs nothing and is
          // the difference between an enquiry and a closed tab. ?>
    <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.course.corporate_heading')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.course.corporate_text')) ?></p>
        <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-brand mt-5">
            <?= esc(lang('Catalog.course.corporate_cta')) ?>
        </a>
    </section>
</div>

<?= $this->endSection() ?>
