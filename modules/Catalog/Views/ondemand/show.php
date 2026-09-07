<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * A course, seen as an on-demand product.
 *
 * Not a second course page. The dated page sells a room and a diary; this one
 * sells a library, and the four things it has to answer that the other cannot
 * are what it is, how much of it there is, what one lesson of it is actually
 * like, and what it costs to start now.
 *
 * The free lesson is the first call to action, above the price, because it is
 * the single biggest conversion lever an on-demand catalogue has: a description
 * can only assert that the teaching is good, whereas five minutes of it settles
 * the question. Anybody who watches one has already made the decision the rest
 * of this page is arguing for.
 *
 * @var array       $course
 * @var array       $detail       outcomes, includes, modules, faqs, modes, related
 * @var array|null  $session      the SELF_PACED session, when one is on sale
 * @var array|null  $price        its published price in $currency, or null
 * @var string      $currency
 * @var list<array> $outline      curriculum modules that have recorded lessons
 * @var int         $lessonCount
 * @var int         $runtimeSec
 * @var array|null  $preview      the free lesson, when one exists
 * @var array<int, list<array>> $assets  exercise files, keyed by lesson id
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$previewUrl = $preview === null
    ? null
    : locale_url('preview/' . $course['slug'] . '/' . $preview['slug']);

// A seat can only be sold when there is a session on sale *and* a price
// published in this visitor's currency. Either missing and the panel asks for
// an address instead, which is the difference between a lead and a bounce.
$bookable = $session !== null && $price !== null;

// The runtime, in whichever unit reads as a fact rather than as a rounding
// error. "About 0.4 hours" is nobody's idea of a useful number; the bare "min"
// matches how the course page already labels a topic's length.
$runtime = '';
if ($runtimeSec >= 3600) {
    $runtime = lang('Catalog.course.curriculum_total', [round($runtimeSec / 3600, 1)]);
} elseif ($runtimeSec > 0) {
    $runtime = (int) round($runtimeSec / 60) . ' min';
}

// The other ways the same course can be taken. Somebody who came looking for
// self-paced and would rather have a room is a booking, not a bounce, and the
// dated page is one link away.
//
// array_intersect against MODES rather than a filter over the stored rows: it
// validates and orders in one step, since it keeps the order of its first
// argument, which is the order the site presents the four modes in everywhere
// else. Left to the table's own order the chips would come out in whatever
// sequence an editor happened to tick the boxes.
$otherModes = array_values(array_filter(
    array_intersect(\Modules\Catalog\Models\CourseSessionModel::MODES, $detail['modes']),
    static fn (string $mode): bool => $mode !== 'SELF_PACED'
));
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php // ── Above the fold ─────────────────────────────────────────────────── ?>
<section class="relative overflow-hidden border-b border-line pb-10 pt-32 sm:pt-36">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-40"></div>
    <div class="container-x">
        <?= view('Modules\Site\Views\partials\breadcrumbs', ['crumbs' => $crumbs], ['saveData' => false]) ?>

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="chip chip-accent"><?= esc(mode_label('SELF_PACED')) ?></span>
                    <span class="chip"><?= esc(level_label((int) $course['level'])) ?></span>
                    <?php // Lessons and runtime only once they exist. A count of
                          // zero beside a price is a page that looks broken, and
                          // silence is the honest alternative. ?>
                    <?php if ($lessonCount > 0): ?>
                        <span class="chip"><?= esc(lang('Catalog.ondemand.lessons', [$lessonCount])) ?></span>
                    <?php endif; ?>
                    <?php if ($course['software_version']): ?>
                        <?php // The commonest objection to a software course is
                              // that it teaches last year's release, and on a
                              // recorded course it is the objection that matters
                              // most, because nothing about it is live. ?>
                        <span class="chip"><?= esc($course['software_version']) ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-5xl"><?= esc(t_field($course['title'])) ?></h1>

                <?php if ($subtitle = t_field($course['subtitle'])): ?>
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-white/70"><?= esc($subtitle) ?></p>
                <?php endif; ?>
            </div>

            <?php // ── The panel ──────────────────────────────────────────── ?>
            <aside class="lg:sticky lg:top-28 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-line bg-surface shadow-lg shadow-brand-black/5">

                    <?php if ($previewUrl !== null): ?>
                        <?php // First, above the price, on its own tinted ground.
                              // The order is the argument: watch a lesson, then
                              // decide what it is worth. ?>
                        <div class="border-b border-line bg-brand-red/10 p-6">
                            <a href="<?= esc($previewUrl) ?>" class="btn-brand w-full gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 4.5v11l9-5.5-9-5.5z"/></svg>
                                <?= esc(lang('Catalog.ondemand.preview')) ?>
                            </a>
                            <p class="mt-2 text-center text-xs text-white/60"><?= esc(t_field($preview['title'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="p-6">
                        <?php if ($price !== null): ?>
                            <p class="flex items-baseline gap-3">
                                <span class="text-3xl font-bold"><?= esc(money($price['price_cents'], $currency)) ?></span>
                                <?php if (! empty($price['compare_at_cents']) && $price['compare_at_cents'] > $price['price_cents']): ?>
                                    <span class="text-base text-white/40 line-through"><?= esc(money($price['compare_at_cents'], $currency)) ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="mt-1 text-xs text-white/50"><?= esc(lang('Catalog.panel.per_seat')) ?></p>
                        <?php else: ?>
                            <?php // No published price in this currency. Said
                                  // plainly rather than converted from the other
                                  // one — see PricingService for why nothing on
                                  // this site is converted at runtime. ?>
                            <p class="text-white/70"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                        <?php endif; ?>

                        <p class="mt-5 text-sm text-white/70"><?= esc(lang('Catalog.panel.self_paced_note')) ?></p>

                        <?php if ($runtime !== '' || $lessonCount > 0): ?>
                            <p class="mt-3 text-sm text-white/55">
                                <?php if ($lessonCount > 0): ?><?= esc(lang('Catalog.ondemand.lessons', [$lessonCount])) ?><?php endif; ?>
                                <?php if ($lessonCount > 0 && $runtime !== ''): ?><span aria-hidden="true"> · </span><?php endif; ?>
                                <?php if ($runtime !== ''): ?><?= esc($runtime) ?><?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($bookable): ?>
                            <?php // Straight into the cart against the self-paced
                                  // session, so the price quoted above and the
                                  // price taken at checkout are the same row. ?>
                            <form method="post" action="<?= esc(locale_url('cart/add')) ?>" class="mt-6">
                                <?= csrf_field() ?>
                                <input type="hidden" name="item_type" value="session">
                                <input type="hidden" name="item_id" value="<?= (int) $session['id'] ?>">
                                <label class="sr-only" for="od-qty"><?= esc(lang('Catalog.panel.seats')) ?></label>
                                <div class="flex gap-2">
                                    <input id="od-qty" name="qty" type="number" min="1" max="20" value="1"
                                           class="field w-20 text-center" inputmode="numeric">
                                    <button type="submit" class="btn-brand flex-1"><?= esc(lang('Catalog.panel.book')) ?></button>
                                </div>
                            </form>
                        <?php else: ?>
                            <?php // Withdrawn from sale, or not priced for this
                                  // market. A visitor who leaves here is gone; a
                                  // visitor who asks is a lead, and is often the
                                  // reason the recording gets finished. ?>
                            <form method="post" action="<?= esc(locale_url('waitlist')) ?>" class="mt-6 space-y-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                                <input type="hidden" name="mode" value="SELF_PACED">
                                <label class="sr-only" for="od-email"><?= esc(lang('Catalog.panel.email')) ?></label>
                                <input id="od-email" name="email" type="email" required autocomplete="email"
                                       class="field" placeholder="<?= esc(lang('Catalog.panel.email'), 'attr') ?>">
                                <button type="submit" class="btn-brand w-full"><?= esc(lang('Catalog.panel.tell_me')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($otherModes !== []): ?>
                        <div class="border-t border-line px-6 py-4">
                            <p class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Catalog.courses.mode')) ?></p>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                <?php foreach ($otherModes as $mode): ?>
                                    <li>
                                        <?php // A private class is a quote, never a
                                              // checkout; everything else is a date
                                              // in the table on the course page. ?>
                                        <a href="<?= esc($mode === 'PRIVATE'
                                                ? locale_url('corporate/request-quote') . '?course=' . rawurlencode($course['slug'])
                                                : course_url($course['slug']) . '#dates') ?>"
                                           class="chip transition hover:bg-brand-red/10 hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                            <?= esc(mode_label($mode)) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</section>

<div class="container-x grid gap-14 py-14 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">
    <div class="min-w-0 space-y-14">

        <?php // ── What it is ─────────────────────────────────────────────── ?>
        <?php if ($body = rich_text($course['description'])): ?>
            <section aria-labelledby="overview">
                <h2 id="overview" class="section-title"><?= esc(lang('Catalog.course.overview')) ?></h2>
                <div class="prose-site mt-5"><?= $body ?></div>
            </section>
        <?php endif; ?>

        <?php // ── What you'll be able to do ──────────────────────────────── ?>
        <?php if ($detail['outcomes'] !== []): ?>
            <section aria-labelledby="outcomes">
                <h2 id="outcomes" class="section-title"><?= esc(lang('Catalog.course.outcomes')) ?></h2>
                <ul class="mt-5 grid gap-x-8 gap-y-3 sm:grid-cols-2">
                    <?php foreach ($detail['outcomes'] as $outcome): ?>
                        <li class="flex gap-3 text-white/80">
                            <svg class="mt-1 h-4 w-4 shrink-0 text-brand-red" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10.5l4 4 8-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span><?= esc(t_field($outcome['text'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php // ── The outline ────────────────────────────────────────────── ?>
        <section id="outline" aria-labelledby="outline-title">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="outline-title" class="section-title"><?= esc(lang('Catalog.course.curriculum')) ?></h2>
                <?php if ($runtime !== ''): ?>
                    <p class="text-sm text-white/55"><?= esc($runtime) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($outline !== []): ?>
                <?php // Details/summary rather than an Alpine accordion: it opens
                      // without JavaScript, it is keyboard-operable for free, and
                      // a search engine reads the closed content. ?>
                <div class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line">
                    <?php foreach ($outline as $i => $module): ?>
                        <details class="group bg-surface" <?= $i === 0 ? 'open' : '' ?>>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold">
                                <?php // A lesson attached to no curriculum module
                                      // still has to appear — see
                                      // LessonModel::outline() — and arrives in a
                                      // bucket with no title of its own. ?>
                                <span><?= esc(t_field($module['title'] ?? '') ?: lang('Catalog.course.curriculum')) ?></span>
                                <span class="flex shrink-0 items-center gap-3">
                                    <span class="text-xs font-normal text-white/45"><?= esc(lang('Catalog.ondemand.lessons', [count($module['lessons'])])) ?></span>
                                    <svg class="h-4 w-4 text-white/40 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </summary>
                            <div class="px-5 pb-5">
                                <?php if ($summary = t_field($module['summary'] ?? '')): ?>
                                    <p class="mb-3 text-sm text-white/60"><?= esc($summary) ?></p>
                                <?php endif; ?>
                                <ul class="space-y-3 text-sm">
                                    <?php foreach ($module['lessons'] as $lesson):
                                        $files = $assets[(int) $lesson['id']] ?? []; ?>
                                        <li class="border-b border-line/60 pb-3 last:border-0 last:pb-0">
                                            <div class="flex items-baseline justify-between gap-4">
                                                <span class="text-white/80"><?= esc(t_field($lesson['title'])) ?></span>
                                                <?php if ((int) $lesson['duration_sec'] > 0): ?>
                                                    <span class="shrink-0 text-white/45 tabular-nums"><?= max(1, (int) round((int) $lesson['duration_sec'] / 60)) ?> min</span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (! empty($lesson['is_preview'])): ?>
                                                <?php // The free lesson, in the place
                                                      // it belongs as well as at the
                                                      // top of the panel. Somebody who
                                                      // has read this far is deciding,
                                                      // and this is where they decide. ?>
                                                <p class="mt-2">
                                                    <a href="<?= esc(locale_url('preview/' . $course['slug'] . '/' . $lesson['slug'])) ?>"
                                                       class="chip chip-accent gap-1.5 font-semibold transition hover:bg-brand-red/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 4.5v11l9-5.5-9-5.5z"/></svg>
                                                        <?= esc(lang('Catalog.ondemand.preview')) ?>
                                                        <span class="sr-only">— <?= esc(t_field($lesson['title'])) ?></span>
                                                    </a>
                                                </p>
                                            <?php endif; ?>

                                            <?php if ($files !== []): ?>
                                                <?php // Named, not linked. The files
                                                      // themselves sit behind the
                                                      // enrolment check in the player;
                                                      // saying what is in the box is
                                                      // selling, handing it over before
                                                      // it is bought is not. ?>
                                                <ul class="mt-2 space-y-1">
                                                    <?php foreach ($files as $file): ?>
                                                        <li class="flex items-baseline gap-2 text-xs text-white/55">
                                                            <svg class="h-3.5 w-3.5 shrink-0 translate-y-0.5 text-white/40" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 3v10m0 0l-3.5-3.5M10 13l3.5-3.5M4 16h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            <span><?= esc($file['label'] ?: basename((string) $file['path'])) ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php // Nothing has been recorded yet, which at launch is the
                      // truth for every course. Said out loud rather than papered
                      // over with an accordion that opens onto nothing.
                      //
                      // The syllabus below it is not a substitute pretending to be
                      // the lessons: it is the course's own curriculum, the same
                      // one the classroom runs to and the one the recordings will
                      // follow, and it is the honest answer to "what is in it"
                      // while the video is still being made. ?>
                <p class="mt-5 max-w-2xl leading-relaxed text-white/65"><?= esc(lang('Catalog.ondemand.none')) ?></p>

                <?php if ($detail['modules'] !== []): ?>
                    <div class="mt-6 divide-y divide-line overflow-hidden rounded-2xl border border-line">
                        <?php foreach ($detail['modules'] as $i => $module): ?>
                            <details class="group bg-surface" <?= $i === 0 ? 'open' : '' ?>>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold">
                                    <span><?= esc(t_field($module['title'])) ?></span>
                                    <svg class="h-4 w-4 shrink-0 text-white/40 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </summary>
                                <div class="px-5 pb-5">
                                    <?php if ($summary = t_field($module['summary'] ?? '')): ?>
                                        <p class="mb-3 text-sm text-white/60"><?= esc($summary) ?></p>
                                    <?php endif; ?>
                                    <ul class="space-y-2 text-sm">
                                        <?php foreach ($module['topics'] ?? [] as $topic): ?>
                                            <li class="flex items-baseline justify-between gap-4 border-b border-line/60 pb-2 last:border-0">
                                                <span class="text-white/80"><?= esc(t_field($topic['title'])) ?></span>
                                                <?php if ((int) $topic['duration_min'] > 0): ?>
                                                    <span class="shrink-0 text-white/45 tabular-nums"><?= (int) $topic['duration_min'] ?> min</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php // ── What comes with it, and what it prepares you for ───────── ?>
        <?php if ($detail['includes'] !== [] || t_field($course['certification_alignment'])): ?>
            <section class="grid gap-10 sm:grid-cols-2">
                <?php if ($detail['includes'] !== []): ?>
                    <div>
                        <h2 class="section-title"><?= esc(lang('Catalog.course.included')) ?></h2>
                        <?php // The exercise files and the certificate are named
                              // here, in the course's own content, rather than
                              // asserted again in this template. One place says
                              // what a buyer gets, so the page and the course
                              // cannot come to disagree about it. ?>
                        <ul class="mt-4 space-y-2.5 text-white/80">
                            <?php foreach ($detail['includes'] as $item): ?>
                                <li class="flex gap-3">
                                    <svg class="mt-1 h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/><path d="M6.5 10.2l2.4 2.4 4.6-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span><?= esc(t_field($item['text'])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($alignment = t_field($course['certification_alignment'])): ?>
                    <div>
                        <h2 class="section-title"><?= esc(lang('Catalog.course.certification')) ?></h2>
                        <div class="mt-4 rounded-2xl border border-line bg-surface p-5">
                            <p class="text-white/80"><?= esc($alignment) ?></p>
                            <?php // Said plainly, because the alternative is a
                                  // buyer believing the school issues the Adobe
                                  // credential itself. It does not, the exam is
                                  // booked and sat elsewhere, and no course can
                                  // promise a pass. ?>
                            <p class="mt-3 text-sm text-white/55"><?= esc(lang('Catalog.course.certification_note')) ?></p>
                            <a href="<?= esc(locale_url('adobe/certification')) ?>" class="mt-3 inline-block text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                <?= esc(lang('Catalog.course.certification_hub')) ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php // ── The team route out ─────────────────────────────────────── ?>
        <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
            <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.course.corporate_heading')) ?></h2>
            <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.course.corporate_text')) ?></p>
            <a href="<?= esc(locale_url('corporate/request-quote') . '?course=' . rawurlencode($course['slug'])) ?>" class="btn-brand mt-5">
                <?= esc(lang('Catalog.course.corporate_cta')) ?>
            </a>
        </section>
    </div>

    <?php // The right column is empty below the fold on purpose: the panel above
          // is position: sticky and travels down this column, so anything placed
          // here would collide with it. ?>
    <div aria-hidden="true"></div>
</div>

<?= $this->endSection() ?>
