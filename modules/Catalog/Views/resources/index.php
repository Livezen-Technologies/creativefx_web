<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The free shelf.
 *
 * This page is read by people who have no intention of buying anything today,
 * and it has to be worth their time on that basis alone — the moment it reads
 * as a wall of email-harvesting forms it stops being shared, stops being linked
 * to, and stops doing the one job it has.
 *
 * So the card leads with what the thing is and what is in it, and says nothing
 * about the exchange. The gate, where there is one, is on the resource's own
 * page, after somebody has decided they want it.
 *
 * The one thing a card does disclose is when a resource has not been written
 * yet. That belongs here rather than only on the page behind it: somebody who
 * clicks expecting a PDF and finds a form has been wasted, and a listing that
 * hides the state is the reason they clicked.
 *
 * @var list<array> $resources  each with has_file added
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'heading' => lang('Catalog.resources.title'),
    'intro'   => lang('Catalog.resources.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x space-y-12 py-12">

    <?php if ($resources === []): ?>
        <?php // Nothing is invented to fill the shelf, and the way out is the
              // catalogue rather than a dead end: somebody who came looking for
              // a free sheet on Photoshop is somebody who wants to learn
              // Photoshop. ?>
        <div class="mx-auto max-w-2xl rounded-3xl border border-line bg-surface p-8 text-center sm:p-12">
            <p class="text-lg leading-relaxed text-white/70"><?= esc(lang('Catalog.resources.none')) ?></p>
            <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-6"><?= esc(lang('Catalog.courses.all')) ?></a>
        </div>
    <?php else: ?>
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($resources as $resource): ?>
                <?php $url = locale_url('resources/' . $resource['slug']); ?>
                <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition hover:border-brand-red/40">
                    <?php if (! empty($resource['hero_image'])): ?>
                        <?php // Decorative. The heading below is the real link,
                              // so this one is out of the tab order and hidden
                              // from a screen reader rather than announced as a
                              // second, identical destination. ?>
                        <a href="<?= esc($url) ?>" class="block aspect-[16/9] overflow-hidden" tabindex="-1" aria-hidden="true">
                            <img src="<?= esc(media_src($resource['hero_image']), 'attr') ?>" alt=""
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                 loading="lazy" width="640" height="360">
                        </a>
                    <?php endif; ?>

                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="text-lg font-semibold leading-snug">
                            <?php // The stretched link makes the whole card the
                                  // target without wrapping the summary and the
                                  // note inside the link text, which a screen
                                  // reader would announce as one long sentence. ?>
                            <a href="<?= esc($url) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                                <?= esc(t_field($resource['title'])) ?>
                            </a>
                        </h2>

                        <?php if ($summary = t_field($resource['summary'] ?? '')): ?>
                            <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto pt-5 text-sm">
                            <?php if (! empty($resource['has_file'])): ?>
                                <span class="font-medium text-brand-red"><?= esc(lang('Catalog.resources.download')) ?></span>
                            <?php else: ?>
                                <?php // Said in full rather than as a badge. The
                                      // sentence is the honest one the language
                                      // file already carries for this state, and
                                      // it sets the expectation before the click
                                      // rather than after it. ?>
                                <span class="text-xs leading-relaxed text-white/55"><?= esc(lang('Catalog.resources.pending')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php // Where somebody who liked one of these goes next. The webinars are
          // the same audience one step further in — free, live, and the first
          // time they meet the person who would teach the paid course. ?>
    <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.webinars.title')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.webinars.intro')) ?></p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="<?= esc(locale_url('webinars')) ?>" class="btn-brand"><?= esc(lang('Catalog.webinars.upcoming')) ?></a>
            <a href="<?= esc(locale_url('courses')) ?>" class="btn-ghost"><?= esc(lang('Catalog.courses.all')) ?></a>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
