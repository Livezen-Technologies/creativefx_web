<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The course page — the one page on this site that has to sell.
 *
 * The structure follows the order a buyer actually decides in: what is it, what
 * will I be able to do, is it for me, when can I come, what happens in the room,
 * who teaches it, what do I get, what does it prepare me for, what did other
 * people think, and the questions I would otherwise have to email to ask.
 *
 * The sticky panel is the point of the page. It carries the mode switch — live
 * online, in person, self-paced — and the price and the call to action change
 * with it, because "how much is it" has three different answers and making
 * somebody hunt for theirs is where a booking is lost.
 *
 * @var array  $course
 * @var array  $detail   outcomes, prerequisites, audiences, includes, modules, faqs, modes, instructors, related
 * @var array  $byMode   sessions grouped by delivery mode, each priced
 * @var array  $prices   the cheapest price per mode
 * @var list<string> $modes
 * @var string $currency
 * @var list<array> $reviews
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$defaultMode = in_array($course['default_mode'], $modes, true) ? $course['default_mode'] : ($modes[0] ?? 'LIVE_ONLINE');
$totalMin    = 0;
foreach ($detail['modules'] as $module) {
    foreach ($module['topics'] ?? [] as $topic) {
        $totalMin += (int) $topic['duration_min'];
    }
}
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
                    <span class="chip"><?= esc(level_label((int) $course['level'])) ?></span>
                    <?php if ($d = duration_label($course)): ?><span class="chip"><?= esc($d) ?></span><?php endif; ?>
                    <?php if ($course['software_version']): ?>
                        <?php // The single most common objection to a software
                              // course is that it teaches last year's release.
                              // Answering it in the first thing somebody reads
                              // is worth a badge. ?>
                        <span class="chip"><?= esc($course['software_version']) ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-5xl"><?= esc(t_field($course['title'])) ?></h1>

                <?php if ($subtitle = t_field($course['subtitle'])): ?>
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-white/70"><?= esc($subtitle) ?></p>
                <?php endif; ?>

                <?php if ((int) $course['rating_count'] > 0): ?>
                    <p class="mt-5 flex items-center gap-2 text-sm text-white/70">
                        <span class="font-semibold text-gold"><?= esc(number_format((float) $course['rating_avg'], 1)) ?></span>
                        <span aria-hidden="true">★</span>
                        <a href="#reviews" class="underline decoration-line underline-offset-4 hover:text-brand-red">
                            <?= esc(lang('Catalog.course.rating_count', [(int) $course['rating_count']])) ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <?php // The panel. Sticky from lg up; at the top of the page on a
                  // phone, where a floating box would cover the content it is
                  // meant to be selling. ?>
            <?= view('Modules\Catalog\Views\partials\booking_panel', [
                'course'      => $course,
                'byMode'      => $byMode,
                'prices'      => $prices,
                'modes'       => $modes,
                'defaultMode' => $defaultMode,
                'currency'    => $currency,
            ], ['saveData' => false]) ?>
        </div>
    </div>
</section>

<div class="container-x grid gap-14 py-14 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">
    <div class="min-w-0 space-y-14">

        <?php // ── Overview ───────────────────────────────────────────────── ?>
        <?php if ($body = rich_text($course['description'])): ?>
            <section aria-labelledby="overview">
                <h2 id="overview" class="section-title"><?= esc(lang('Catalog.course.overview')) ?></h2>
                <div class="prose-site mt-5"><?= $body ?></div>
            </section>
        <?php endif; ?>

        <?php // ── What you'll learn ──────────────────────────────────────── ?>
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

        <?php // ── Who it's for, and what you need first ──────────────────── ?>
        <?php if ($detail['audiences'] !== [] || $detail['prerequisites'] !== []): ?>
            <section class="grid gap-10 sm:grid-cols-2">
                <?php if ($detail['audiences'] !== []): ?>
                    <div>
                        <h2 class="section-title"><?= esc(lang('Catalog.course.audience')) ?></h2>
                        <ul class="mt-4 space-y-2 text-white/75">
                            <?php foreach ($detail['audiences'] as $item): ?>
                                <li class="border-l-2 border-line pl-4"><?= esc(t_field($item['text'])) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if ($detail['prerequisites'] !== []): ?>
                    <div>
                        <h2 class="section-title"><?= esc(lang('Catalog.course.prerequisites')) ?></h2>
                        <ul class="mt-4 space-y-2 text-white/75">
                            <?php foreach ($detail['prerequisites'] as $item): ?>
                                <li class="border-l-2 border-line pl-4"><?= esc(t_field($item['text'])) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php // ── The dates. The conversion engine of the page. ──────────── ?>
        <section id="dates" aria-labelledby="dates-title">
            <h2 id="dates-title" class="section-title"><?= esc(lang('Catalog.course.dates')) ?></h2>
            <?= view('Modules\Catalog\Views\partials\dates_table', [
                'byMode'   => $byMode,
                'modes'    => $modes,
                'currency' => $currency,
                'course'   => $course,
            ], ['saveData' => false]) ?>
        </section>

        <?php // ── Curriculum ─────────────────────────────────────────────── ?>
        <?php if ($detail['modules'] !== []): ?>
            <section aria-labelledby="curriculum">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="curriculum" class="section-title"><?= esc(lang('Catalog.course.curriculum')) ?></h2>
                    <?php if ($totalMin > 0): ?>
                        <p class="text-sm text-white/55"><?= esc(lang('Catalog.course.curriculum_total', [round($totalMin / 60, 1)])) ?></p>
                    <?php endif; ?>
                </div>

                <?php // Details/summary rather than an Alpine accordion: it
                      // opens without JavaScript, it is keyboard-operable for
                      // free, and a search engine reads the closed content. ?>
                <div class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line">
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
            </section>
        <?php endif; ?>

        <?php // ── Who teaches it ─────────────────────────────────────────── ?>
        <?php if ($detail['instructors'] !== []): ?>
            <section aria-labelledby="instructors">
                <h2 id="instructors" class="section-title"><?= esc(lang('Catalog.course.instructor')) ?></h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <?php foreach ($detail['instructors'] as $instructor): ?>
                        <article class="rounded-2xl border border-line bg-surface p-5">
                            <h3 class="font-semibold"><?= esc($instructor['name']) ?></h3>
                            <?php if ($headline = t_field($instructor['headline'])): ?>
                                <p class="mt-1 text-sm text-brand-red"><?= esc($headline) ?></p>
                            <?php endif; ?>
                            <div class="prose-site prose-sm mt-3 text-sm"><?= rich_text($instructor['bio']) ?></div>
                            <a href="<?= esc(locale_url('instructors/' . $instructor['slug'])) ?>" class="mt-3 inline-block text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                <?= esc(lang('Catalog.course.instructor_more')) ?>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php // ── What's included, and what it prepares you for ──────────── ?>
        <?php if ($detail['includes'] !== [] || t_field($course['certification_alignment'])): ?>
            <section class="grid gap-10 sm:grid-cols-2">
                <?php if ($detail['includes'] !== []): ?>
                    <div>
                        <h2 class="section-title"><?= esc(lang('Catalog.course.included')) ?></h2>
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
                                  // certification itself. It does not, and the
                                  // exam is booked and sat elsewhere. ?>
                            <p class="mt-3 text-sm text-white/55"><?= esc(lang('Catalog.course.certification_note')) ?></p>
                            <a href="<?= esc(locale_url('adobe/certification')) ?>" class="mt-3 inline-block text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                <?= esc(lang('Catalog.course.certification_hub')) ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php // ── Reviews. Real ones, or an honest empty state. ──────────── ?>
        <section id="reviews" aria-labelledby="reviews-title">
            <h2 id="reviews-title" class="section-title"><?= esc(lang('Catalog.course.reviews')) ?></h2>
            <?php if ($reviews === []): ?>
                <?php // Nothing is invented here. A new school with no reviews
                      // that shows five glowing ones is lying to somebody about
                      // to spend real money. ?>
                <p class="mt-4 max-w-2xl text-white/60"><?= esc(lang('Catalog.course.reviews_none')) ?></p>
            <?php else: ?>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <?php foreach ($reviews as $review): ?>
                        <blockquote class="rounded-2xl border border-line bg-surface p-5">
                            <p class="text-gold" aria-label="<?= esc(lang('Catalog.course.rating_of', [(int) $review['rating']]), 'attr') ?>">
                                <?= stars((int) $review['rating']) ?>
                            </p>
                            <?php if ($review['title']): ?><p class="mt-2 font-semibold"><?= esc($review['title']) ?></p><?php endif; ?>
                            <?php // nl2br over esc, never the other way round: escaping the <br> the
                              // other order produces is how a "fix" for line breaks turns into
                              // markup printed at the reader. A learner writes in paragraphs and
                              // esc() alone ran them into one block. ?>
                        <p class="mt-2 text-sm leading-relaxed text-white/75"><?= nl2br(esc($review['body'])) ?></p>
                            <footer class="mt-3 text-xs text-white/50">
                                <?= esc($review['author_name'] ?: lang('Catalog.course.review_anon')) ?><?php if ($review['author_role']): ?>, <?= esc($review['author_role']) ?><?php endif; ?>
                            </footer>
                        </blockquote>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php // ── FAQ. Marked up as FAQPage in the head. ─────────────────── ?>
        <?php if ($detail['faqs'] !== []): ?>
            <section aria-labelledby="faq">
                <h2 id="faq" class="section-title"><?= esc(lang('Catalog.course.faq')) ?></h2>
                <div class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line">
                    <?php foreach ($detail['faqs'] as $faq): ?>
                        <details class="group bg-surface">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-medium">
                                <span><?= esc(t_field($faq['question'])) ?></span>
                                <svg class="h-4 w-4 shrink-0 text-white/40 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </summary>
                            <div class="prose-site prose-sm px-5 pb-5 text-sm"><?= rich_text($faq['answer']) ?></div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php // ── Related, and the corporate route out ───────────────────── ?>
        <?php if ($detail['related'] !== []): ?>
            <section aria-labelledby="related">
                <h2 id="related" class="section-title"><?= esc(lang('Catalog.course.related')) ?></h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($detail['related'] as $related): ?>
                        <?= view('Modules\Catalog\Views\partials\course_card', ['course' => $related + ['currency' => $currency], 'compact' => true], ['saveData' => false]) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
            <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.course.corporate_heading')) ?></h2>
            <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.course.corporate_text')) ?></p>
            <a href="<?= esc(locale_url('corporate/request-quote') . '?course=' . rawurlencode($course['slug'])) ?>" class="btn-brand mt-5">
                <?= esc(lang('Catalog.course.corporate_cta')) ?>
            </a>
        </section>
    </div>

    <?php // The right column is empty below the fold on purpose: the panel
          // above is position: sticky and travels down this column, so anything
          // placed here would collide with it. ?>
    <div aria-hidden="true"></div>
</div>

<?= $this->endSection() ?>
