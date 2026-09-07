<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One free session.
 *
 * The page answers a narrow question — is this worth an hour of my Tuesday —
 * and then gets out of the way. So the panel carries the two facts that decide
 * it, the date and the clock, and one button; everything persuasive is the body
 * copy, which is also the part a search engine can read.
 *
 * The time is printed in the webinar's own zone with the zone named beside it,
 * and the `datetime` attribute carries the full offset. A free session sold
 * across Colombo, Dubai and London has three clocks around it, and "14:00" on
 * its own is the reason somebody joins at the end.
 *
 * A session that has already run keeps its page. The address may be in a
 * calendar invitation somebody still has, and a 404 there is worse than a page
 * saying it has happened — with the recording where there is one, and a way on
 * to the next date where there is not. What it does not do is pretend: no
 * registration button survives the end of the session.
 *
 * @var array      $webinar  presented row: when_iso, when_date, when_time,
 *                           zone_abbr, zone_name, is_past, and the two URLs
 *                           already checked for an http(s) scheme
 * @var array|null $course   the course it was drawn from, when still published
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

// Registration is offered only while there is something to register for and
// somewhere to do it. Either missing means no button rather than a button that
// goes nowhere.
$canRegister = ! $webinar['is_past'] && $webinar['register_url'] !== null;
$canWatch    = $webinar['is_past'] && $webinar['recording_url'] !== null;
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Catalog.webinars.title'),
    'heading' => t_field($webinar['title']),
    'intro'   => t_field($webinar['summary'] ?? ''),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x grid gap-12 py-12 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">

    <div class="min-w-0 space-y-10">
        <?php if (! empty($webinar['hero_image'])): ?>
            <?php // Decorative: everything it shows is said in the copy below,
                  // and an image described twice is a screen reader reading the
                  // page twice. ?>
            <img src="<?= esc(media_src($webinar['hero_image']), 'attr') ?>" alt=""
                 class="w-full rounded-2xl border border-line object-cover"
                 loading="lazy" width="1280" height="720">
        <?php endif; ?>

        <?php if ($body = rich_text($webinar['body'] ?? '')): ?>
            <div class="prose-site"><?= $body ?></div>
        <?php endif; ?>

        <?php // The course behind the hour. This is the whole commercial point
              // of running a free session, and it is a real card with a real
              // price rather than a line of persuasion. ?>
        <?php if ($course !== null): ?>
            <section aria-labelledby="related">
                <h2 id="related" class="section-title"><?= esc(lang('Catalog.course.related')) ?></h2>
                <div class="mt-5 sm:max-w-md">
                    <?= view('Modules\Catalog\Views\partials\course_card', [
                        'course'  => $course,
                        'compact' => false,
                    ], ['saveData' => false]) ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <?php // Sticky from lg up; at the top of the column on a phone, where a
          // floating box would cover the copy it is meant to be selling. ?>
    <aside class="lg:sticky lg:top-28 lg:self-start" aria-labelledby="when">
        <div class="rounded-2xl border border-line bg-surface p-6">
            <h2 id="when" class="text-sm font-semibold uppercase tracking-[0.2em] text-white/55">
                <?= esc(lang('Catalog.dates.when')) ?>
            </h2>

            <?php if ($webinar['when_iso'] !== null): ?>
                <p class="mt-3 text-lg font-semibold">
                    <time datetime="<?= esc($webinar['when_iso'], 'attr') ?>"><?= esc($webinar['when_date']) ?></time>
                </p>
                <p class="mt-1 text-white/80"><?= esc($webinar['when_time']) ?> <?= esc($webinar['zone_abbr']) ?></p>
                <?php // The IANA name under the abbreviation, because "+0530"
                      // is unambiguous to a machine and meaningless to a reader
                      // deciding whether that is their afternoon or their
                      // morning. ?>
                <p class="mt-1 text-xs text-white/50"><?= esc($webinar['zone_name']) ?></p>
            <?php else: ?>
                <?php // Published without a date. Nothing is invented to fill
                      // the gap — a "coming soon" that names no month is worth
                      // less than the honest absence of one. ?>
                <p class="mt-3 text-white/70"><?= esc(lang('Catalog.webinars.none')) ?></p>
            <?php endif; ?>

            <?php if ($canRegister): ?>
                <a href="<?= esc($webinar['register_url'], 'attr') ?>" rel="noopener" class="btn-brand mt-6 w-full">
                    <?= esc(lang('Catalog.webinars.register')) ?>
                </a>
            <?php elseif ($canWatch): ?>
                <a href="<?= esc($webinar['recording_url'], 'attr') ?>" rel="noopener" class="btn-brand mt-6 w-full">
                    <?= esc(lang('Catalog.webinars.watch')) ?>
                </a>
            <?php endif; ?>

            <?php // Always a way onward, and after the button rather than
                  // instead of it. For a session that has been and gone with no
                  // recording this is the only thing in the panel that can be
                  // acted on, which is exactly when it matters most. ?>
            <a href="<?= esc(locale_url('webinars')) ?>" class="btn-ghost mt-3 w-full">
                <?= esc(lang('Catalog.webinars.upcoming')) ?>
            </a>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
