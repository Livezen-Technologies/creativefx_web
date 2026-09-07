<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Who teaches here.
 *
 * A faculty index is a trust page, and the way trust pages fail is by being
 * generous. Four stock portraits and four invented biographies would read
 * better than what is below and would be a fabrication somebody could act on:
 * a person choosing a course because of a face. So each card says plainly what
 * kind of profile it is, and the note above the grid explains it once, in the
 * open, rather than in small print underneath.
 *
 * The cards therefore carry only what is true today — the track, the standard
 * held to teach it, and how many published courses it covers — and the profile
 * behind each one carries the argument.
 *
 * @var list<array> $faculty         each with course_count and excerpt added
 * @var bool        $anyPlaceholder  at least one profile describes a team
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Catalog.instructors.eyebrow'),
    'heading' => lang('Catalog.instructors.title'),
    'intro'   => lang('Catalog.instructors.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x space-y-12 py-12">

    <?php // ── The disclosure, before the grid, not after it ────────────────
          // Said once here and again in full on each profile it applies to.
          // A reader who scans the cards and stops has still been told; a
          // reader who opens one is told again where the claim is being made. ?>
    <?php if ($anyPlaceholder): ?>
        <div class="max-w-3xl rounded-2xl border border-gold/40 bg-surface p-5">
            <p class="text-sm leading-relaxed text-white/75"><?= esc(lang('Catalog.instructors.placeholder')) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($faculty === []): ?>
        <?php // Nothing is invented to fill the page, and the way out is the
              // catalogue: somebody asking who teaches is deciding whether to
              // book, and the courses carry the dates that decision needs. ?>
        <div class="max-w-3xl rounded-2xl border border-line bg-surface p-6">
            <p class="text-white/70"><?= esc(lang('Catalog.instructors.none')) ?></p>
            <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-5"><?= esc(lang('Catalog.courses.all')) ?></a>
        </div>
    <?php else: ?>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($faculty as $member): ?>
                <?php $url = locale_url('instructors/' . $member['slug']); ?>
                <article class="group relative flex flex-col rounded-2xl border border-line bg-surface p-5 transition hover:border-brand-red/40">
                    <?php // Only when there is one. An avatar placeholder — a
                          // grey silhouette in a circle — is a photograph of
                          // nobody in the place a photograph of somebody goes,
                          // which is the same claim in a quieter voice. ?>
                    <?php if (! empty($member['photo'])): ?>
                        <img src="<?= esc(media_src($member['photo']), 'attr') ?>" alt=""
                             class="mb-4 h-20 w-20 rounded-2xl object-cover" loading="lazy" width="160" height="160">
                    <?php endif; ?>

                    <?php if (! empty($member['is_placeholder'])): ?>
                        <p class="mb-3">
                            <span class="chip"><?= esc(lang('Catalog.instructors.badge')) ?></span>
                        </p>
                    <?php endif; ?>

                    <h2 class="text-lg font-semibold leading-snug">
                        <?php // The stretched link makes the whole card the
                              // target without wrapping it in an anchor, which
                              // would put the headline, the excerpt and the
                              // course count inside the link text a screen
                              // reader announces as one run-on sentence. ?>
                        <a href="<?= esc($url) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                            <?= esc($member['name']) ?>
                        </a>
                    </h2>

                    <?php if ($headline = t_field($member['headline'] ?? '')): ?>
                        <p class="mt-1 text-sm text-brand-red"><?= esc($headline) ?></p>
                    <?php endif; ?>

                    <?php if (! empty($member['excerpt'])): ?>
                        <p class="mt-3 text-sm leading-relaxed text-white/65"><?= esc($member['excerpt']) ?></p>
                    <?php endif; ?>

                    <div class="mt-auto pt-5 text-xs text-white/55">
                        <?php // A count, not a claim. Zero is stated rather
                              // than hidden: a faculty attached to nothing
                              // published is a gap the school should see on its
                              // own site rather than discover from a visitor. ?>
                        <?php if ((int) $member['course_count'] > 0): ?>
                            <?php $n = (int) $member['course_count']; ?>
                            <?= esc(lang($n === 1 ? 'Catalog.instructors.teaches_count_one' : 'Catalog.instructors.teaches_count', [$n])) ?>
                        <?php else: ?>
                            <?= esc(lang('Catalog.instructors.teaches_none')) ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php // The enquiry this page generates most often is a company asking who
          // would turn up if they bought a private course. The corporate route
          // answers it with a conversation, which is the right answer, and it
          // is the same band the course and location pages carry. ?>
    <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.course.corporate_heading')) ?></h2>
        <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.course.corporate_text')) ?></p>
        <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-brand mt-5">
            <?= esc(lang('Catalog.course.corporate_cta')) ?>
        </a>
    </section>
</div>

<?= $this->endSection() ?>
