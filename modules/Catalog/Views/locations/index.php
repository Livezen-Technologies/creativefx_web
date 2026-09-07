<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Every place we teach: the classrooms, and the online room.
 *
 * This page is a signpost, not a destination. Somebody who lands here has
 * already decided roughly what they want and is now asking where they would
 * have to be to get it, so the only things a card carries are the ones that
 * answer that: what kind of place it is, one sentence about who goes there, the
 * next date, and a way in. Everything persuasive lives on the city page, which
 * has its own prose and can afford it.
 *
 * The two groups are kept apart deliberately. A virtual room listed among
 * street addresses reads as somewhere you could turn up to, and the difference
 * between "in person" and "live online" is the single most consequential thing
 * a buyer has to get right before they book.
 *
 * @var list<array>  $venues  each with next_date and date_count added
 * @var int          $total   upcoming dates across every venue
 * @var list<array>  $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

// lang() answers a key it cannot find with the key itself, which would print
// "Catalog.locations.eyebrow" at a visitor. The catalogue language file is
// owned elsewhere in this pass, so the two strings this page needs and that
// file does not yet carry are asked for by key with the English below as a
// fallback: the page reads correctly today and picks up the real translation
// the moment the key lands, with no second edit here.
$str = static function (string $key, string $fallback): string {
    $line = lang($key);

    return $line === $key ? $fallback : (string) $line;
};

// Grouped rather than filtered twice in the markup, so the "is there anything
// in this group" test and the loop cannot disagree with each other.
$groups = ['classroom' => [], 'virtual' => []];
foreach ($venues as $venue) {
    $groups[$venue['type'] === 'virtual' ? 'virtual' : 'classroom'][] = $venue;
}
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => $str('Catalog.locations.eyebrow', 'Locations'),
    'heading' => lang('Catalog.locations.title'),
    'intro'   => lang('Catalog.locations.intro'),
    'crumbs'  => $crumbs,
    'aside'   => $total > 0
        ? '<p class="text-sm text-white/60">' . esc(lang('Catalog.schedule.count', [$total])) . '</p>'
        : '',
], ['saveData' => false]) ?>

<div class="container-x space-y-14 py-12">

    <?php if ($venues === []): ?>
        <?php // Nothing is invented to fill the page. The way out is the
              // schedule, which is the question somebody asking "where" was
              // really asking. ?>
        <div class="rounded-2xl border border-line bg-surface p-6">
            <p class="text-white/70"><?= esc($str('Catalog.locations.empty', 'No locations are published yet.')) ?></p>
            <a href="<?= esc(locale_url('schedule')) ?>" class="btn-brand mt-5">
                <?= esc(lang('Catalog.locations.all')) ?>
            </a>
        </div>
    <?php endif; ?>

    <?php // In person first, then the online room. Not because it is the bigger
          // half — it is not — but because somebody reading a page called
          // "where we teach" is looking for a city, and the room that has no
          // city belongs after the ones that do. ?>
    <?php foreach (['classroom' => 'CLASSROOM', 'virtual' => 'LIVE_ONLINE'] as $type => $mode): ?>
        <?php if ($groups[$type] === []) {
            continue;
        } ?>

        <section aria-labelledby="places-<?= esc($type, 'attr') ?>">
            <h2 id="places-<?= esc($type, 'attr') ?>" class="section-title"><?= esc(mode_label($mode)) ?></h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($groups[$type] as $venue): ?>
                    <?php $url = locale_url('locations/' . $venue['slug']); ?>
                    <article class="group relative flex flex-col rounded-2xl border border-line bg-surface p-5 transition hover:border-brand-red/40">
                        <h3 class="text-lg font-semibold leading-snug">
                            <?php // The stretched link makes the whole card the
                                  // target without wrapping it in an anchor —
                                  // which would put the summary, the date and
                                  // the count inside the link text a screen
                                  // reader announces. ?>
                            <?php // The place's name, not its page heading. On a
                                  // signpost the label is "Colombo"; "Adobe and
                                  // AI training in Colombo" is the argument the
                                  // city page makes once you are on it, and as
                                  // a card title it is three cards that all
                                  // start with the same five words. ?>
                            <a href="<?= esc($url) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                                <?= esc($venue['name']) ?>
                            </a>
                        </h3>

                        <?php if ($summary = t_field($venue['summary'] ?? '')): ?>
                            <p class="mt-2 line-clamp-4 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto pt-5">
                            <?php if (! empty($venue['next_date'])): ?>
                                <p class="text-xs text-white/45"><?= esc(lang('Catalog.card.next')) ?></p>
                                <p class="font-semibold">
                                    <?php // A real date, formatted from the
                                          // record rather than described. "Soon"
                                          // is what a site says when it has
                                          // nothing scheduled. ?>
                                    <?= esc((new DateTimeImmutable((string) $venue['next_date']))->format('j M Y')) ?>
                                </p>
                                <p class="mt-1 text-xs text-white/55">
                                    <?= esc(lang('Catalog.schedule.count', [(int) $venue['date_count']])) ?>
                                </p>
                            <?php else: ?>
                                <?php // Honest, and not a dead end: the city
                                      // page still carries the room, the prose
                                      // and the courses that run there. ?>
                                <p class="text-sm text-white/55"><?= esc(lang('Catalog.locations.none')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if ($venues !== []): ?>
        <p>
            <a href="<?= esc(locale_url('schedule')) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Catalog.locations.all')) ?>
            </a>
        </p>
    <?php endif; ?>

    <?php // The route out for the enquiry this page generates most often:
          // somebody whose city is not on it. A private class travels, and
          // saying so here is cheaper than losing them to the absence of a
          // classroom near them. ?>
    <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.course.corporate_heading')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.course.corporate_text')) ?></p>
        <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-brand mt-5">
            <?= esc(lang('Catalog.course.corporate_cta')) ?>
        </a>
    </section>
</div>

<?= $this->endSection() ?>
