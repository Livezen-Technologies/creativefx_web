<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The free sessions, in two lists.
 *
 * They are two lists because they are two different offers. What is coming up
 * is a date somebody has to be free for and can register for now; what has
 * already run is a recording they can watch at midnight, and the only thing the
 * two have in common is the subject. Merged into one grid sorted by date, half
 * the cards cannot be acted on and the reader has to work out which half by
 * reading the dates.
 *
 * Nothing that has finished without a recording appears here at all. That is
 * the whole of the "past" rule: a recording is an asset that keeps earning, and
 * a date with nothing behind it is a page that advertises a school which does
 * not record its sessions.
 *
 * The two grids share one block of card markup, driven by the table below,
 * rather than being written out twice. A card in this codebase would normally
 * be a partial in `Views/partials/`; this page does not own that directory this
 * week, and two divergent copies of the same card is the worse outcome.
 *
 * @var list<array> $upcoming  presented rows, soonest first
 * @var list<array> $past      presented rows with recordings, most recent first
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$sections = [
    [
        'id'    => 'upcoming',
        'title' => lang('Catalog.webinars.upcoming'),
        'rows'  => $upcoming,
        // What pressing the card leads to. Named per section because it is the
        // one thing that genuinely differs between a date and a recording.
        'cue'   => lang('Catalog.webinars.register'),
        // Only the upcoming list explains itself when it is empty. An absent
        // recordings section says nothing; an empty one under a heading reads
        // as a fault on a site that simply has not run a webinar yet.
        'empty' => true,
    ],
    [
        'id'    => 'past',
        'title' => lang('Catalog.webinars.past'),
        'rows'  => $past,
        'cue'   => lang('Catalog.webinars.watch'),
        'empty' => false,
    ],
];
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'heading' => lang('Catalog.webinars.title'),
    'intro'   => lang('Catalog.webinars.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x space-y-14 py-12">

    <?php foreach ($sections as $section): ?>
        <?php if ($section['rows'] === [] && ! $section['empty']) {
            continue;
        } ?>

        <section aria-labelledby="<?= esc($section['id'], 'attr') ?>">
            <h2 id="<?= esc($section['id'], 'attr') ?>" class="section-title"><?= esc($section['title']) ?></h2>

            <?php if ($section['rows'] === []): ?>
                <?php // Honest rather than blank. Somebody who came for a free
                      // hour and found none scheduled still wants to learn this,
                      // so the way out is the catalogue rather than an apology. ?>
                <div class="mt-5 max-w-2xl rounded-2xl border border-line bg-surface p-6">
                    <p class="text-white/70"><?= esc(lang('Catalog.webinars.none')) ?></p>
                    <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-5"><?= esc(lang('Catalog.courses.all')) ?></a>
                </div>
            <?php else: ?>
                <div class="mt-5 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($section['rows'] as $webinar): ?>
                        <?php $url = locale_url('webinars/' . $webinar['slug']); ?>
                        <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition hover:border-brand-red/40">
                            <?php if (! empty($webinar['hero_image'])): ?>
                                <?php // Decorative: the heading below is the real
                                      // link, so this one is out of the tab order
                                      // and hidden from a screen reader rather
                                      // than announced as a second, identical
                                      // destination. ?>
                                <a href="<?= esc($url) ?>" class="block aspect-[16/9] overflow-hidden" tabindex="-1" aria-hidden="true">
                                    <img src="<?= esc(media_src($webinar['hero_image']), 'attr') ?>" alt=""
                                         class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                         loading="lazy" width="640" height="360">
                                </a>
                            <?php endif; ?>

                            <div class="flex flex-1 flex-col p-5">
                                <?php if ($webinar['when_iso'] !== null): ?>
                                    <?php // The machine-readable value carries the
                                          // offset; the visible one carries the
                                          // zone, because "14:00" on its own is
                                          // the reason people join an hour late. ?>
                                    <p class="text-xs text-white/55">
                                        <time datetime="<?= esc($webinar['when_iso'], 'attr') ?>">
                                            <?= esc($webinar['when_short']) ?>, <?= esc($webinar['when_time']) ?>
                                        </time>
                                        <?= esc($webinar['zone_abbr']) ?>
                                    </p>
                                <?php endif; ?>

                                <h3 class="mt-2 text-lg font-semibold leading-snug">
                                    <?php // Stretched over the card, so the whole
                                          // card is the target without wrapping
                                          // the summary and the date inside the
                                          // link text a screen reader announces. ?>
                                    <a href="<?= esc($url) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                                        <?= esc(t_field($webinar['title'])) ?>
                                    </a>
                                </h3>

                                <?php if ($summary = t_field($webinar['summary'] ?? '')): ?>
                                    <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                                <?php endif; ?>

                                <p class="mt-auto pt-5 text-sm font-medium text-brand-red"><?= esc($section['cue']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php // Where a free hour leads. The resources are the same audience one
          // step earlier; the schedule is the same audience one step later. ?>
    <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.resources.title')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.resources.intro')) ?></p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="<?= esc(locale_url('resources')) ?>" class="btn-brand"><?= esc(lang('Catalog.resources.title')) ?></a>
            <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Catalog.schedule.title')) ?></a>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
