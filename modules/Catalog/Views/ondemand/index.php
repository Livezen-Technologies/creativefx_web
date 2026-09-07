<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The self-paced library.
 *
 * The cards are written here rather than through `partials/course_card`, and
 * that is a deliberate divergence rather than an oversight. The shared card
 * answers a dated-course question — what does it cost and when is the next one
 * — and links to /course/{slug}. This grid answers a different one: how much of
 * it is there, and can I watch a piece of it now. Its link goes to the on-demand
 * page, and the free lesson is a second target inside the card, which the shared
 * card's stretched link cannot hold.
 *
 * @var list<array>  $courses  each with price_cents, lesson_count, preview_slug
 * @var string       $currency
 * @var list<array>  $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => mode_label('SELF_PACED'),
    'heading' => lang('Catalog.ondemand.title'),
    'intro'   => lang('Catalog.ondemand.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x py-12">
    <?php if ($courses === []): ?>
        <?php // Nothing is invented to fill the shelf. The wording is the one
              // the language file already carries for a library that has not
              // been recorded, and the way out is the full catalogue rather
              // than a dead end. ?>
        <div class="mx-auto max-w-2xl rounded-3xl border border-line bg-surface p-8 text-center sm:p-12">
            <p class="text-lg leading-relaxed text-white/70"><?= esc(lang('Catalog.ondemand.none')) ?></p>
            <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-6"><?= esc(lang('Catalog.courses.all')) ?></a>
        </div>
    <?php else: ?>
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($courses as $course):
                $url     = locale_url('on-demand/' . $course['slug']);
                $lessons = (int) $course['lesson_count'];
                $preview = $course['preview_slug'];
            ?>
                <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition hover:border-brand-red/40">
                    <?php if (! empty($course['hero_image'])): ?>
                        <?php // Decorative: the heading below is the real link,
                              // so this one is taken out of the tab order and
                              // hidden from a screen reader rather than being
                              // announced as a second, identical destination. ?>
                        <a href="<?= esc($url) ?>" class="block aspect-[16/9] overflow-hidden" tabindex="-1" aria-hidden="true">
                            <img src="<?= esc(media_src($course['hero_image']), 'attr') ?>" alt=""
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                 loading="lazy" width="640" height="360">
                        </a>
                    <?php endif; ?>

                    <div class="flex flex-1 flex-col p-5">
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="chip"><?= esc(level_label((int) $course['level'])) ?></span>
                            <?php // Only when lessons have actually been
                                  // recorded. "0 lessons" on a course that is
                                  // genuinely for sale reads as a broken page,
                                  // and the honest answer is to say nothing. ?>
                            <?php if ($lessons > 0): ?>
                                <span class="chip"><?= esc(lang('Catalog.ondemand.lessons', [$lessons])) ?></span>
                            <?php endif; ?>
                        </p>

                        <h2 class="mt-3 text-lg font-semibold leading-snug">
                            <a href="<?= esc($url) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                                <?= esc(t_field($course['title'])) ?>
                            </a>
                        </h2>

                        <?php if ($summary = t_field($course['summary'] ?? '')): ?>
                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                        <?php endif; ?>

                        <?php if ($preview !== null): ?>
                            <?php // The free lesson, and the reason this grid
                                  // exists in this shape. `relative z-10` is
                                  // load-bearing: the heading's stretched link
                                  // covers the whole card, and without a stacking
                                  // context of its own this link is unclickable
                                  // while still looking perfectly fine. ?>
                            <p class="relative z-10 mt-4">
                                <a href="<?= esc(locale_url('preview/' . $course['slug'] . '/' . $preview)) ?>"
                                   class="chip chip-accent gap-1.5 font-semibold transition hover:bg-brand-red/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 4.5v11l9-5.5-9-5.5z"/></svg>
                                    <?= esc(lang('Catalog.ondemand.preview')) ?>
                                    <?php // The same label on every card in the
                                          // grid; the course title is what makes
                                          // each one distinguishable in a screen
                                          // reader's list of links. ?>
                                    <span class="sr-only">— <?= esc(t_field($course['title'])) ?></span>
                                </a>
                            </p>
                        <?php endif; ?>

                        <div class="mt-auto pt-5">
                            <?php if ($course['price_cents'] !== null): ?>
                                <p class="font-semibold"><?= esc(money((int) $course['price_cents'], $currency)) ?></p>
                            <?php else: ?>
                                <?php // No published price in this visitor's
                                      // currency. Said plainly rather than
                                      // converted — see PricingService — and the
                                      // card stays in the grid, because the
                                      // library is still what it is. ?>
                                <p class="text-sm text-white/60"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
