<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Certificate programmes, and bootcamps.
 *
 * One view for both sections, because they are the same list with a different
 * heading — the type is already decided by the route, so there is nothing on
 * the page to filter and nothing for a facet rail to do.
 *
 * The card leads with the saving rather than the price. A programme's whole
 * argument is that it costs less than the courses inside it, and a buyer who
 * has already seen those courses priced individually needs the difference put
 * in front of them, not the total again. Where the saving cannot be worked out
 * — a course in the programme with no price in this visitor's currency — the
 * card shows the price alone and says nothing, because "save nothing" is worse
 * than silence and a guess is worse than either.
 *
 * @var list<array>  $bundles  each with url, price, saving, course_count, hours
 * @var string       $type     certificate | bootcamp
 * @var string       $section  the URL segment this listing lives at
 * @var string       $other    the other section's segment, for the cross-link
 * @var string       $currency
 * @var list<array>  $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);


// The language keys are grouped by section rather than by type, and `$other`
// is a URL segment, so both are resolved to a key once here instead of being
// re-derived at each of the four places they are read.
$key      = $type === 'bootcamp' ? 'bootcamps' : 'certificates';
$otherKey = $other === 'bootcamps' ? 'bootcamps' : 'certificates';
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'heading' => lang('Catalog.bundles.' . $key . '_title'),
    'intro'   => lang('Catalog.bundles.' . $key . '_intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x py-12">
    <?php if ($bundles === []): ?>
        <?php // Never a blank page. A section with nothing in it yet reads as
              // broken unless it says otherwise, and the way out is the
              // catalogue the programmes are assembled from. ?>
        <div class="rounded-3xl border border-line bg-surface p-8 text-center sm:p-12">
            <p class="text-lg font-semibold"><?= esc(lang('Catalog.bundles.none')) ?></p>
            <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-6"><?= esc(lang('Catalog.courses.all')) ?></a>
        </div>
    <?php else: ?>
        <div class="grid gap-6 md:grid-cols-2">
            <?php foreach ($bundles as $bundle): ?>
                <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition hover:border-brand-red/40">
                    <?php if (! empty($bundle['hero_image'])): ?>
                        <?php // Decorative here: the heading beneath carries the
                              // same information, so an alt text would be read
                              // out twice. ?>
                        <div class="aspect-[16/9] overflow-hidden">
                            <img src="<?= esc(media_src($bundle['hero_image']), 'attr') ?>" alt=""
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                 loading="lazy" width="640" height="360">
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-1 flex-col p-6">
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-white/50">
                            <span class="chip"><?= esc(lang('Catalog.bundles.courses_count', [(int) $bundle['course_count']])) ?></span>
                            <?php if ((int) $bundle['hours'] > 0): ?>
                                <span><?= esc(lang('Catalog.duration.hours', [(int) $bundle['hours']])) ?></span>
                            <?php endif; ?>
                        </p>

                        <h2 class="mt-3 text-xl font-semibold leading-snug">
                            <?php // The stretched link: one interactive element
                                  // per card, so a keyboard reaches the card in
                                  // one tab and a pointer can click anywhere on
                                  // it. Nothing else inside may be a link. ?>
                            <a href="<?= esc($bundle['url']) ?>" class="after:absolute after:inset-0 hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                <?= esc(t_field($bundle['title'])) ?>
                            </a>
                        </h2>

                        <?php if ($subtitle = t_field($bundle['subtitle'])): ?>
                            <p class="mt-2 text-sm font-medium text-brand-red"><?= esc($subtitle) ?></p>
                        <?php endif; ?>

                        <?php if ($summary = t_field($bundle['summary'])): ?>
                            <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto flex flex-wrap items-end justify-between gap-3 pt-6">
                            <div>
                                <?php if ($bundle['price'] !== null): ?>
                                    <p class="text-2xl font-bold"><?= esc(money((int) $bundle['price']['price_cents'], $currency)) ?></p>
                                    <?php if ($bundle['saving'] !== null): ?>
                                        <p class="mt-1.5">
                                            <span class="chip chip-accent"><?= esc(lang('Catalog.bundles.saving', [money((int) $bundle['saving'], $currency)])) ?></span>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php // Not converted from the other currency
                                          // — see PricingService for why nothing
                                          // on this site is priced at runtime. ?>
                                    <p class="text-sm text-white/70"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                                <?php endif; ?>
                            </div>

                            <?php // Not a link: the whole card is already one,
                                  // and a second would put two tab stops on one
                                  // destination. ?>
                            <span class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4" aria-hidden="true">
                                <?= esc(lang('Catalog.bundles.view')) ?>
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php // The other half of the same idea. Somebody who came looking for a
          // certificate programme and wants it over four days rather than four
          // months is one link away, rather than back at the search results. ?>
    <section class="mt-14 rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.bundles.' . $otherKey . '_title')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.bundles.' . $otherKey . '_intro')) ?></p>
        <a href="<?= esc(locale_url($other)) ?>" class="btn-ghost mt-5">
            <?= esc(lang('Catalog.bundles.' . $otherKey . '_title')) ?>
        </a>
    </section>
</div>

<?= $this->endSection() ?>
