<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One profile.
 *
 * At launch every one of these describes a faculty rather than a person, and
 * the page is written so that being honest about it costs nothing. The
 * disclosure is the first block of the body — a note somebody has to hunt for
 * is a note that has not been made — and everything under it is true whether
 * the profile names a person or a team: the standard held before anyone takes
 * a class, how a cohort is staffed, and the courses actually taught.
 *
 * No `Person` JSON-LD is emitted for a placeholder. That is settled in the
 * controller, by `Schema::person()` returning nothing for one, and nothing here
 * re-tests the flag to decide it: a second test is a second place for the two
 * to disagree, and the one that would then be wrong is the one search engines
 * read.
 *
 * The photograph is the other thing to leave alone. There is none at launch,
 * and a silhouette in a circle where a face goes is the same claim made
 * quietly, so the block simply does not render.
 *
 * @var array          $instructor
 * @var list<string>   $credentials  already decoded and filtered to sentences
 * @var list<array{label:string,url:string}> $links  already filtered to http(s)
 * @var list<array>    $courses      cards, with from_cents and next_date
 * @var string         $currency
 * @var list<array>    $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);


$isPlaceholder = ! empty($instructor['is_placeholder']);
$bio           = rich_text($instructor['bio'] ?? '');
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Catalog.instructors.eyebrow'),
    'heading' => (string) $instructor['name'],
    'intro'   => t_field($instructor['headline'] ?? ''),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x space-y-14 py-12">

    <?php // ── The disclosure ───────────────────────────────────────────────
          // First, bordered, and in the reading column rather than beside it.
          // The alternative — a grey line under the biography — is the shape a
          // disclaimer takes when somebody would rather it were not read. ?>
    <?php if ($isPlaceholder): ?>
        <section aria-labelledby="profile-note" class="max-w-3xl rounded-2xl border border-gold/40 bg-surface p-5 sm:p-6">
            <h2 id="profile-note" class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">
                <?= esc(lang('Catalog.instructors.placeholder_heading')) ?>
            </h2>
            <p class="mt-3 leading-relaxed text-white/75"><?= esc(lang('Catalog.instructors.placeholder')) ?></p>
        </section>
    <?php endif; ?>

    <?php // ── The biography ────────────────────────────────────────────────
          // Not wrapped in a labelled section: the only heading it could carry
          // is the h1 already above it, and an sr-only copy of that reads the
          // page title twice to a screen reader for no gain. The measure is
          // held to max-w-3xl because this is the half of the page that has to
          // be read rather than scanned. ?>
    <?php if ($bio !== '' || ! empty($instructor['photo'])): ?>
        <?php if (! empty($instructor['photo'])): ?>
            <div class="grid max-w-4xl gap-8 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-start">
                <img src="<?= esc(media_src($instructor['photo']), 'attr') ?>"
                     alt="<?= esc($instructor['name'], 'attr') ?>"
                     class="w-full max-w-[180px] rounded-2xl border border-line object-cover"
                     width="360" height="360">
                <div class="prose-site min-w-0"><?= $bio ?></div>
            </div>
        <?php else: ?>
            <div class="prose-site max-w-3xl"><?= $bio ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php // ── The standard ─────────────────────────────────────────────────
          // The heading changes with the kind of profile, and this is the one
          // place on the page where it has to. On a faculty profile the list is
          // a bar every instructor clears before they take a class; on a named
          // person's it is what that person holds. Calling the first "their
          // credentials" would attribute a qualification to nobody in
          // particular, which is precisely the fabrication the flag exists to
          // prevent. ?>
    <?php if ($credentials !== []): ?>
        <section aria-labelledby="standard">
            <h2 id="standard" class="section-title">
                <?= esc($isPlaceholder
                    ? lang('Catalog.instructors.standard')
                    : lang('Catalog.instructors.credentials')) ?>
            </h2>
            <ul class="mt-5 grid max-w-4xl gap-x-8 gap-y-3 sm:grid-cols-2">
                <?php foreach ($credentials as $credential): ?>
                    <li class="flex gap-3 text-white/80">
                        <svg class="mt-1 h-4 w-4 shrink-0 text-brand-red" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10.5l4 4 8-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span><?= esc($credential) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php // ── Elsewhere ────────────────────────────────────────────────────
          // Only ever populated for a real person with a portfolio to point at,
          // and already filtered to http(s) in the controller. rel carries
          // noopener with the new tab, because a target="_blank" without it
          // hands the opened page a handle on this one. ?>
    <?php if ($links !== []): ?>
        <section aria-labelledby="links">
            <h2 id="links" class="section-title"><?= esc(lang('Catalog.instructors.links')) ?></h2>
            <ul class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <?php foreach ($links as $link): ?>
                    <li>
                        <a href="<?= esc($link['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
                           class="font-medium text-brand-red underline decoration-line underline-offset-4">
                            <?= esc($link['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php // ── What they teach ──────────────────────────────────────────────
          // The point of the page, commercially: somebody reading a profile is
          // deciding whether to book, and every card below carries a real price
          // and a real next date rather than sending them back to the
          // catalogue to look one up. ?>
    <section aria-labelledby="teaches">
        <h2 id="teaches" class="section-title"><?= esc(lang('Catalog.instructors.teaches')) ?></h2>

        <?php if ($courses === []): ?>
            <?php // Honest and not a dead end. A profile attached to nothing
                  // published is a gap in the school's own data, and the way
                  // out for the reader is the catalogue. ?>
            <div class="mt-5 max-w-3xl rounded-2xl border border-line bg-surface p-6">
                <p class="text-white/70"><?= esc(lang('Catalog.instructors.teaches_empty')) ?></p>
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-5"><?= esc(lang('Catalog.courses.all')) ?></a>
            </div>
        <?php else: ?>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($courses as $course): ?>
                    <?= view('Modules\Catalog\Views\partials\course_card', [
                        'course' => $course + ['currency' => $currency],
                    ], ['saveData' => false]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <p>
        <a href="<?= esc(locale_url('instructors')) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
            <?= esc(lang('Catalog.instructors.all')) ?>
        </a>
    </p>
</div>

<?= $this->endSection() ?>
