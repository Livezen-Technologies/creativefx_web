<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The corporate landing page.
 *
 * Written for a manager with a capability gap and a budget cycle, not for
 * somebody browsing a catalogue. So it answers the four questions that actually
 * decide whether an enquiry is sent — what a private cohort is, how it can be
 * delivered, what happens after you ask, and what you get — and then asks for
 * the enquiry. There is no price and no basket anywhere on it, because a
 * company buying training for twelve people wants a conversation and an
 * invoice, and a price without a scope is a number they cannot use.
 *
 * The editorial band in the middle is the CMS page with slug `corporate` when
 * an editor has written one, and the language file's prose when they have not.
 * Everything else on the page is structured — three delivery options, four
 * steps, a list of what is included — and stays in the language file, because
 * those are product facts rather than copy and a block editor is the wrong
 * tool for a list that has to stay parallel.
 *
 * **Nothing is claimed that is not true.** There are no client logos and no
 * case studies here, and none are seeded, because the school has no clients to
 * name yet. The two places they would sit are marked below and render nothing
 * at all — an empty carousel or a row of grey placeholder rectangles tells a
 * corporate buyer exactly as much as an invented one, and costs the same
 * credibility when they ask.
 *
 * @var string       $heading
 * @var string       $intro
 * @var list<array{type:string, content:array}> $blocks  CMS blocks, hero removed
 * @var list<array>  $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$formUrl = locale_url('corporate/request-quote');

/**
 * The three ways a private cohort is delivered.
 *
 * Kept as data rather than three copies of the same markup so the cards cannot
 * drift apart, and so adding a fourth option later is one row.
 */
$delivery = [
    ['key' => 'onsite',  'title' => 'Commerce.corporate.delivery.onsite_title',  'text' => 'Commerce.corporate.delivery.onsite_text'],
    ['key' => 'virtual', 'title' => 'Commerce.corporate.delivery.virtual_title', 'text' => 'Commerce.corporate.delivery.virtual_text'],
    ['key' => 'hybrid',  'title' => 'Commerce.corporate.delivery.hybrid_title',  'text' => 'Commerce.corporate.delivery.hybrid_text'],
];

/** How a quote actually happens, in the order it happens. */
$steps = [
    ['title' => 'Commerce.corporate.process.step1_title', 'text' => 'Commerce.corporate.process.step1_text'],
    ['title' => 'Commerce.corporate.process.step2_title', 'text' => 'Commerce.corporate.process.step2_text'],
    ['title' => 'Commerce.corporate.process.step3_title', 'text' => 'Commerce.corporate.process.step3_text'],
    ['title' => 'Commerce.corporate.process.step4_title', 'text' => 'Commerce.corporate.process.step4_text'],
];

/** What every private cohort carries, whichever course it runs. */
$includes = [
    'Commerce.corporate.includes.tailored',
    'Commerce.corporate.includes.materials',
    'Commerce.corporate.includes.instructor',
    'Commerce.corporate.includes.recording',
    'Commerce.corporate.includes.attendance',
    'Commerce.corporate.includes.certificates',
    'Commerce.corporate.includes.invoice',
    'Commerce.corporate.includes.followup',
];
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Commerce.corporate.eyebrow'),
    'heading' => $heading,
    'intro'   => $intro,
    // Pre-escaped HTML by contract with the partial. The call to action is in
    // the band rather than only at the foot of the page because a buyer who
    // already knows what they want should not have to read four sections to
    // find the form.
    'aside'   => '<a href="' . esc($formUrl, 'attr') . '" class="btn-brand">' . esc(lang('Commerce.corporate.cta')) . '</a>',
], ['saveData' => false]) ?>

<?php // ── The editorial band ─────────────────────────────────────────────── ?>
<?php if ($blocks !== []): ?>
    <?php
    /**
     * The CMS page, rendered block by block.
     *
     * The missing-partial case is decided before rendering rather than caught
     * afterwards, for the reason the main CMS renderer gives at length: a bare
     * `catch (\Throwable)` around a block swallows a real fatal inside a valid
     * block and quietly renders the same payload as rich text, so the page
     * comes back looking almost right with nothing in the log.
     */
    foreach ($blocks as $block):
        $partial = 'Modules\Site\Views\cms\blocks\\' . $block['type'];

        if (! is_file(ROOTPATH . 'modules/Site/Views/cms/blocks/' . $block['type'] . '.php')) {
            log_message('warning', 'CMS block type "{type}" has no partial on the corporate page; rendered as rich text.', ['type' => $block['type']]);
            echo view('Modules\Site\Views\cms\blocks\richtext', ['content' => $block['content']], ['saveData' => false]);

            continue;
        }

        try {
            echo view($partial, ['content' => $block['content']], ['saveData' => false]);
        } catch (\Throwable $e) {
            log_message('error', 'CMS block "{type}" failed on the corporate page: {msg}', ['type' => $block['type'], 'msg' => $e->getMessage()]);

            if (ENVIRONMENT !== 'production') {
                throw $e;
            }

            echo view('Modules\Site\Views\cms\blocks\richtext', ['content' => $block['content']], ['saveData' => false]);
        }
    endforeach;
    ?>
<?php else: ?>
    <?php // No `corporate` page has been created yet. The page has to work on
          // the day it launches, so the same ground is covered from the
          // language file — and an editor who later writes the CMS page
          // replaces this without a deployment. ?>
    <section class="border-b border-line py-16" aria-labelledby="what">
        <div class="container-x">
            <div class="max-w-3xl">
                <h2 id="what" class="section-title"><?= esc(lang('Commerce.corporate.what.title')) ?></h2>
                <p class="mt-5 text-lg leading-relaxed text-white/75"><?= esc(lang('Commerce.corporate.what.body')) ?></p>
                <p class="mt-4 leading-relaxed text-white/60"><?= esc(lang('Commerce.corporate.what.body_2')) ?></p>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php // ── How it can be delivered ────────────────────────────────────────── ?>
<section class="border-b border-line py-16" aria-labelledby="delivery">
    <div class="container-x">
        <h2 id="delivery" class="section-title"><?= esc(lang('Commerce.corporate.delivery.title')) ?></h2>
        <p class="mt-4 max-w-2xl text-white/60"><?= esc(lang('Commerce.corporate.delivery.intro')) ?></p>

        <div class="mt-8 grid gap-5 md:grid-cols-3">
            <?php foreach ($delivery as $option): ?>
                <article class="rounded-2xl border border-line bg-surface p-6">
                    <h3 class="text-lg font-semibold text-brand-red"><?= esc(lang($option['title'])) ?></h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/70"><?= esc(lang($option['text'])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="mt-6 text-sm text-white/55">
            <?= esc(lang('Commerce.corporate.delivery.note')) ?>
        </p>
    </div>
</section>

<?php // ── How a quote actually happens ───────────────────────────────────── ?>
<section class="border-b border-line py-16" aria-labelledby="process">
    <div class="container-x">
        <h2 id="process" class="section-title"><?= esc(lang('Commerce.corporate.process.title')) ?></h2>
        <p class="mt-4 max-w-2xl text-white/60"><?= esc(lang('Commerce.corporate.process.intro')) ?></p>

        <?php // An ordered list rather than a grid of divs: these are steps in
              // sequence, a screen reader should announce them as "1 of 4", and
              // the numbering must survive a narrow screen where they stack. ?>
        <ol class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($steps as $i => $step): ?>
                <li class="rounded-2xl border border-line bg-surface p-6">
                    <p class="text-3xl font-bold text-brand-red" aria-hidden="true"><?= esc(sprintf('%02d', $i + 1)) ?></p>
                    <h3 class="mt-2 font-semibold"><?= esc(lang($step['title'])) ?></h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/65"><?= esc(lang($step['text'])) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>

        <p class="mt-6 max-w-2xl text-sm text-white/55"><?= esc(lang('Commerce.corporate.process.note')) ?></p>
    </div>
</section>

<?php // ── What a private cohort includes ─────────────────────────────────── ?>
<section class="border-b border-line py-16" aria-labelledby="includes">
    <div class="container-x">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-14">
            <div>
                <h2 id="includes" class="section-title"><?= esc(lang('Commerce.corporate.includes.title')) ?></h2>
                <p class="mt-4 text-white/60"><?= esc(lang('Commerce.corporate.includes.intro')) ?></p>

                <?php // Said plainly, in the one place a corporate buyer is most
                      // likely to assume otherwise: the school prepares people
                      // for the Adobe Certified Professional exam, which Adobe
                      // awards through Certiport. It does not award it, and it
                      // cannot promise a pass. ?>
                <p class="mt-4 text-sm leading-relaxed text-white/55"><?= esc(lang('Commerce.corporate.includes.certification_note')) ?></p>

                <a href="<?= esc(locale_url('courses')) ?>" class="btn-ghost mt-6">
                    <?= esc(lang('Commerce.corporate.browse_catalogue')) ?>
                </a>
            </div>

            <ul class="grid gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-1 lg:gap-y-3.5">
                <?php foreach ($includes as $key): ?>
                    <li class="flex gap-3 text-white/80">
                        <svg class="mt-1 h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/><path d="M6.5 10.2l2.4 2.4 4.6-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span><?= esc(lang($key)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<?php // ── Client logos and case studies would sit here ───────────────────── ?>
<?php // They render nothing, deliberately, and there is no empty state either.
      // The school has no clients it may name and no completed programme to
      // write up. A logo wall of placeholder rectangles, a carousel with one
      // invented testimonial, or a heading followed by "coming soon" all cost
      // the same credibility with the one audience that will check. When there
      // are real references, this is where they belong: a `feature_cards` or
      // `metrics` block on the `corporate` CMS page renders above, with no
      // change to this file. ?>

<?php // ── The route to the form ──────────────────────────────────────────── ?>
<section class="py-16" aria-labelledby="request">
    <div class="container-x">
        <div class="rounded-3xl border border-line bg-surface p-7 sm:p-10">
            <h2 id="request" class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Commerce.corporate.final.title')) ?></h2>
            <p class="mt-4 max-w-2xl leading-relaxed text-white/70"><?= esc(lang('Commerce.corporate.final.text')) ?></p>

            <div class="mt-7 flex flex-wrap items-center gap-4">
                <a href="<?= esc($formUrl) ?>" class="btn-brand"><?= esc(lang('Commerce.corporate.cta')) ?></a>

                <?php // Only when an address has actually been set. An invented
                      // one is worse than none, and a `mailto:` pointing
                      // nowhere is a dead end a buyer discovers after writing
                      // their enquiry. ?>
                <?php if ($mail = setting('email', '', 'contact')): ?>
                    <a href="mailto:<?= esc($mail, 'attr') ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                        <?= esc(lang('Commerce.corporate.final.email', [$mail])) ?>
                    </a>
                <?php endif; ?>
            </div>

            <p class="mt-6 text-sm text-white/55"><?= esc(lang('Commerce.corporate.final.reply')) ?></p>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
