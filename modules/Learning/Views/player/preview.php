<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One lesson, in full, free, to anybody.
 *
 * This page does two jobs at once and the layout is the compromise between
 * them.
 *
 * **It sells.** A description of a self-paced course asserts that the teaching
 * is good; a lesson demonstrates it. That is why the panel here offers the
 * course rather than the lesson, and why the outline underneath shows what is
 * behind the paywall by name — naming what is in the box is selling, and it is
 * the honest half of selling. Nothing is invented: the counts, the running time
 * and the price all come from records, and when the course has no price in this
 * visitor's currency the page says so rather than converting one.
 *
 * **It is read by a search engine.** The transcript is the only part of a video
 * course a crawler can index, so it is page text — full, in the body, above the
 * fold's worth of prose rather than tucked into an accordion. This address is
 * canonical for itself and links back to the course it came from.
 *
 * Nothing on this page is gated and nothing on it needs an account, which is
 * the whole point: a login wall in front of a free sample is a free sample
 * nobody takes.
 *
 * @var array       $course
 * @var array       $lesson
 * @var list<array> $outline    modules with lessons, empty ones already dropped
 * @var int         $lessonCount
 * @var int         $runtimeSec
 * @var array       $video      state ∈ none|ready|missing|unconfigured
 * @var array|null  $price      cheapest self-paced price in this currency
 * @var string      $currency
 * @var bool        $owned      this visitor already holds an enrolment
 * @var list<array> $crumbs
 * @var string      $schema
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$clock = static function (int $seconds): string {
    if ($seconds <= 0) {
        return '';
    }
    $minutes = max(1, (int) round($seconds / 60));

    return $minutes < 60
        ? lang('Learning.clock.min', [$minutes])
        : lang('Learning.clock.hour', [intdiv($minutes, 60), str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT)]);
};

$courseUrl = locale_url('on-demand/' . $course['slug']);
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Learning.preview.eyebrow'),
    'heading' => t_field($lesson['title']),
    'intro'   => lang('Learning.preview.intro'),
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-14 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-14">

    <div class="min-w-0 space-y-12">

        <?php // ── The lesson ─────────────────────────────────────────────── ?>
        <?php if ($video['state'] === 'ready'): ?>
            <div>
                <div class="overflow-hidden rounded-2xl border border-line bg-brand-black">
                    <?php // No autoplay. A video that starts talking the moment
                          // a page loads is the reason people leave a page, and
                          // on a metered connection it is somebody's money. ?>
                    <video class="block aspect-video w-full" controls playsinline preload="metadata">
                        <source src="<?= esc($video['src'], 'attr') ?>">
                        <?= esc(lang('Learning.lesson.video_no_js')) ?>
                    </video>
                </div>
                <?php if ((int) $lesson['duration_sec'] > 0): ?>
                    <p class="mt-3 text-xs text-white/55 tabular-nums"><?= esc($clock((int) $lesson['duration_sec'])) ?></p>
                <?php endif; ?>
            </div>

        <?php elseif ($video['state'] !== 'none'): ?>
            <?php // The same honest panel the paid player shows. A preview that
                  // renders a dead frame does the opposite of its job: it is the
                  // first thing a buyer sees of the school's production. ?>
            <section class="rounded-2xl border border-line bg-surface p-7">
                <h2 class="text-lg font-semibold">
                    <?= esc($video['state'] === 'unconfigured'
                        ? lang('Learning.lesson.video_unconfigured_heading')
                        : lang('Learning.lesson.video_missing_heading')) ?>
                </h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/70">
                    <?= esc($video['state'] === 'unconfigured'
                        ? lang('Learning.lesson.video_unconfigured_body')
                        : lang('Learning.lesson.video_missing_body')) ?>
                </p>
            </section>
        <?php endif; ?>

        <?php // ── Notes ──────────────────────────────────────────────────── ?>
        <?php if ($body = rich_text($lesson['body'])): ?>
            <section aria-labelledby="notes">
                <h2 id="notes" class="section-title"><?= esc(lang('Learning.preview.notes')) ?></h2>
                <div class="prose-site mt-5"><?= $body ?></div>
            </section>
        <?php endif; ?>

        <?php // ── The transcript, as page text ───────────────────────────── ?>
        <?php if ($transcript = rich_text($lesson['transcript'])): ?>
            <section aria-labelledby="transcript">
                <h2 id="transcript" class="section-title"><?= esc(lang('Learning.preview.transcript')) ?></h2>
                <p class="mt-2 text-sm text-white/55"><?= esc(lang('Learning.preview.transcript_note')) ?></p>
                <div class="prose-site mt-5 max-w-none"><?= $transcript ?></div>
            </section>
        <?php endif; ?>

        <?php // ── What else is in the course ─────────────────────────────── ?>
        <section aria-labelledby="inside">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="inside" class="section-title"><?= esc(lang('Learning.preview.inside_heading')) ?></h2>
                <?php if ($lessonCount > 0): ?>
                    <p class="text-sm text-white/55">
                        <?= esc($lessonCount === 1
                            ? lang('Learning.player.lessons_one')
                            : lang('Learning.player.lessons_many', [$lessonCount])) ?>
                        <?php if ($runtimeSec > 0): ?>
                            · <?= esc(lang('Learning.player.runtime', [$clock($runtimeSec)])) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($outline === []): ?>
                <p class="mt-4 text-white/60"><?= esc(lang('Learning.preview.inside_none')) ?></p>
            <?php else: ?>
                <div class="mt-5 space-y-6">
                    <?php foreach ($outline as $module): ?>
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-white/50">
                                <?= esc(t_field($module['title'] ?? '') ?: lang('Learning.player.module_untitled')) ?>
                            </h3>
                            <ul class="mt-3 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                                <?php foreach ($module['lessons'] as $row): ?>
                                    <?php $isFree = (int) $row['is_preview'] === 1; ?>
                                    <li class="flex items-center gap-3 px-5 py-3.5 text-sm">
                                        <?php // Named but not linked, unless it is
                                              // free too. Titles sell the course;
                                              // a link that leads to a sign-in
                                              // wall does not. ?>
                                        <span class="min-w-0 flex-1 <?= $isFree ? '' : 'text-white/70' ?>">
                                            <?php if ($isFree && (int) $row['id'] !== (int) $lesson['id']): ?>
                                                <a href="<?= esc(locale_url('preview/' . $course['slug'] . '/' . $row['slug'])) ?>"
                                                   class="font-medium text-brand-red underline decoration-line underline-offset-4">
                                                    <?= esc(t_field($row['title'])) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= esc(t_field($row['title'])) ?>
                                            <?php endif; ?>
                                        </span>

                                        <?php if ((int) $row['id'] === (int) $lesson['id']): ?>
                                            <span class="chip chip-accent shrink-0"><?= esc(lang('Learning.preview.this_lesson')) ?></span>
                                        <?php elseif ($isFree): ?>
                                            <span class="chip shrink-0"><?= esc(lang('Learning.player.badge_preview')) ?></span>
                                        <?php elseif ((int) $row['duration_sec'] > 0): ?>
                                            <span class="shrink-0 text-xs text-white/45 tabular-nums"><?= esc($clock((int) $row['duration_sec'])) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php // ── The call to action, at the end of the reading ──────────── ?>
        <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
            <?php if ($owned): ?>
                <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Learning.preview.owned_heading')) ?></h2>
                <a href="<?= esc(locale_url('learn/' . $course['slug'])) ?>" class="btn-brand mt-5">
                    <?= esc(lang('Learning.preview.owned_cta')) ?>
                </a>
            <?php else: ?>
                <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Learning.preview.buy_heading')) ?></h2>
                <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Learning.preview.buy_body')) ?></p>
                <div class="mt-6 flex flex-wrap items-center gap-4">
                    <a href="<?= esc($courseUrl) ?>" class="btn-brand"><?= esc(lang('Learning.preview.buy_cta')) ?></a>
                    <?php if ($price !== null): ?>
                        <p class="text-sm text-white/70">
                            <?= esc(lang('Learning.preview.buy_price', [money((int) $price['price_cents'], $currency)])) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php // ── The panel ──────────────────────────────────────────────────── ?>
    <aside class="lg:sticky lg:top-28 lg:self-start">
        <div class="overflow-hidden rounded-3xl border border-line bg-surface">
            <div class="border-b border-line p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red">
                    <?= esc(lang('Learning.preview.heading')) ?>
                </p>
                <h2 class="mt-3 text-lg font-bold leading-snug">
                    <a href="<?= esc($courseUrl) ?>" class="transition hover:text-brand-red"><?= esc(t_field($course['title'])) ?></a>
                </h2>
                <?php if ($summary = t_field($course['summary'])): ?>
                    <p class="mt-3 text-sm leading-relaxed text-white/70"><?= esc($summary) ?></p>
                <?php endif; ?>
            </div>

            <div class="p-6">
                <?php if ($owned): ?>
                    <p class="text-sm text-white/70"><?= esc(lang('Learning.preview.owned_heading')) ?></p>
                    <a href="<?= esc(locale_url('learn/' . $course['slug'])) ?>" class="btn-brand mt-4 w-full">
                        <?= esc(lang('Learning.preview.owned_cta')) ?>
                    </a>

                <?php elseif ($price !== null): ?>
                    <p class="flex items-baseline gap-3">
                        <span class="text-3xl font-bold"><?= esc(money((int) $price['price_cents'], $currency)) ?></span>
                        <?php if (! empty($price['compare_at_cents']) && (int) $price['compare_at_cents'] > (int) $price['price_cents']): ?>
                            <span class="text-base text-white/40 line-through"><?= esc(money((int) $price['compare_at_cents'], $currency)) ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="mt-1 text-xs text-white/50"><?= esc(mode_label('SELF_PACED')) ?></p>
                    <a href="<?= esc($courseUrl) ?>" class="btn-brand mt-5 w-full"><?= esc(lang('Learning.preview.buy_cta')) ?></a>

                <?php else: ?>
                    <?php // Not converted from the other currency. See
                          // PricingService: prices are published per market and
                          // a runtime conversion is a number nobody agreed to
                          // charge. ?>
                    <p class="text-sm text-white/70"><?= esc(lang('Learning.preview.buy_no_price')) ?></p>
                    <a href="<?= esc($courseUrl) ?>" class="btn-ghost mt-5 w-full"><?= esc(lang('Learning.preview.buy_cta')) ?></a>
                <?php endif; ?>

                <?php if ($lessonCount > 0): ?>
                    <ul class="mt-5 space-y-2 border-t border-line pt-5 text-sm text-white/70">
                        <li class="flex items-center gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7 4.5v11l9-5.5-9-5.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                            <?= esc($lessonCount === 1
                                ? lang('Learning.player.lessons_one')
                                : lang('Learning.player.lessons_many', [$lessonCount])) ?>
                        </li>
                        <?php if ($runtimeSec > 0): ?>
                            <li class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/><path d="M10 6v4.3l2.8 1.7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                <?= esc(lang('Learning.player.runtime', [$clock($runtimeSec)])) ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
