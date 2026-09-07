<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * /adobe/certification — the Adobe Certified Professional preparation hub.
 *
 * This is the most carefully bounded page on the site, and the boundary is
 * three sentences long: **Adobe** writes the exam objectives, **Certiport**
 * administers the exam, **Adobe** awards the credential. MyLearnPlus does none
 * of those three things. Everything here is written inside that boundary, and
 * anything that reads as softer than it — "get certified with us", "certified
 * training", a pass rate — is a claim somebody could act on, pay for, and later
 * discover was untrue.
 *
 * The disclaimer is therefore the first thing under the heading rather than a
 * line in the footer. A disclaimer placed after the buying decision is a
 * disclaimer written for the lawyer; this one is written for the person about
 * to spend money on a voucher, so it sits where they cannot miss it and before
 * anything on the page has tried to sell them a course.
 *
 * The exam grid is honest in the other direction too. Ten exams are listed
 * because ten exist, not because ten are taught here: the ones with no course
 * behind them say so plainly. Naming an exam we cannot prepare anybody for is
 * worth more than the enquiry it costs, because the alternative is somebody
 * buying an adjacent course and finding out in the test centre.
 *
 * No pairings are printed for the Specialty Credentials. Adobe decides which
 * two professional certifications earn each one and changes the combinations;
 * last year's pairing next to this year's price is how somebody buys two
 * vouchers for a credential they will not be awarded.
 *
 * @var list<array{app:string, match:string, courses:list<array>}> $exams
 * @var int          $aligned      exam/course pairs found; zero means an honest empty grid
 * @var list<string> $specialty    the four Specialty Credentials, by name
 * @var list<array>  $programmes   Adobe programmes, with url, course_count and price
 * @var list<array{question:string, answer:string}> $faqs
 * @var string       $adobeUrl     Adobe's certification pages, or '' when unconfigured
 * @var string       $catalogueUrl /courses filtered to the Adobe pillar
 * @var string       $currency
 * @var list<array>  $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$body  = lang('Catalog.pillars.cert_body');
$body  = is_array($body) ? $body : [];
$steps = lang('Catalog.pillars.cert_steps');
$steps = is_array($steps) ? $steps : [];

// Counted rather than written into the copy. "Eight of the ten" is true today
// and false the week a Dreamweaver course is published, and nobody would think
// to come back and change a sentence in a language file.
$covered = count(array_filter($exams, static fn (array $exam): bool => $exam['courses'] !== []));

$aside = '<a href="' . esc($catalogueUrl, 'attr') . '" class="btn-brand">'
    . esc(lang('Catalog.pillars.all_courses', [lang('Catalog.pillar.adobe')])) . '</a>';
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Catalog.pillars.cert_eyebrow'),
    'heading' => lang('Catalog.pillars.cert_title'),
    'intro'   => lang('Catalog.pillars.cert_intro'),
    'crumbs'  => $crumbs,
    'aside'   => $aside,
], ['saveData' => false]) ?>

<div class="container-x space-y-16 py-12 sm:space-y-20">

    <?php // ── The disclaimer. First, and unmissable. ──────────────────────── ?>
    <section class="rounded-3xl border border-gold/40 bg-surface p-7 sm:p-9" aria-labelledby="disclaimer">
        <div class="flex items-start gap-4">
            <svg class="mt-0.5 h-6 w-6 shrink-0 text-gold" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.6"/>
                <path d="M12 7.5v5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="12" cy="16.25" r="1.05" fill="currentColor"/>
            </svg>
            <div class="min-w-0">
                <h2 id="disclaimer" class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.pillars.cert_disclaimer_heading')) ?></h2>
                <p class="mt-3 max-w-3xl leading-relaxed text-white/80"><?= esc(lang('Catalog.pillars.cert_disclaimer')) ?></p>
            </div>
        </div>
    </section>

    <?php // ── How the certification works, and how you sit it ─────────────── ?>
    <section class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-14" aria-labelledby="how">
        <div class="min-w-0">
            <h2 id="how" class="section-title"><?= esc(lang('Catalog.pillars.cert_how')) ?></h2>
            <div class="prose-site mt-5 max-w-none">
                <?php foreach ($body as $paragraph): ?>
                    <p><?= esc($paragraph) ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($steps !== []): ?>
            <?php // An ordered list because the order is the information: the
                  // voucher is bought before the exam is booked, and the
                  // credential arrives from Adobe rather than from us at the end
                  // of a sequence this school only appears at the start of. ?>
            <aside class="h-fit rounded-2xl border border-line bg-surface p-6" aria-labelledby="steps">
                <h3 id="steps" class="text-xs font-semibold uppercase tracking-widest text-white/50">
                    <?= esc(lang('Catalog.pillars.cert_steps_heading')) ?>
                </h3>
                <ol class="mt-5 space-y-4">
                    <?php foreach ($steps as $i => $step): ?>
                        <li class="flex gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-line text-xs font-semibold tabular-nums text-brand-red" aria-hidden="true"><?= (int) $i + 1 ?></span>
                            <span class="text-sm leading-relaxed text-white/75"><?= esc($step) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </aside>
        <?php endif; ?>
    </section>

    <?php // ── The exams, and what prepares you for each ───────────────────── ?>
    <section aria-labelledby="exams">
        <h2 id="exams" class="section-title"><?= esc(lang('Catalog.pillars.cert_exams')) ?></h2>
        <p class="mt-3 max-w-3xl leading-relaxed text-white/65"><?= esc(lang('Catalog.pillars.cert_exams_intro')) ?></p>

        <?php if ($aligned === 0): ?>
            <?php // No course on the site has said which exam it prepares for.
                  // Ten "we do not run one" cards would read as a broken page
                  // rather than as an empty one, so the grid is replaced by the
                  // one sentence that is actually true. ?>
            <p class="mt-5 max-w-3xl leading-relaxed text-white/60"><?= esc(lang('Catalog.pillars.cert_none')) ?></p>
        <?php else: ?>
            <p class="mt-2 text-sm text-white/50"><?= esc(lang('Catalog.pillars.cert_covered', [$covered, count($exams)])) ?></p>

            <?php // In Adobe's own product order rather than sorted by whether we
                  // teach it. A reader is looking for one application, they will
                  // find it in a list of ten either way, and reordering the list
                  // to put our courses first is the page arguing with itself
                  // about what it is for. ?>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($exams as $exam): ?>
                    <article class="flex flex-col rounded-2xl border border-line bg-surface p-5">
                        <h3 class="text-base font-semibold leading-snug"><?= esc($exam['app']) ?></h3>
                        <p class="mt-1 text-xs uppercase tracking-wider text-white/45"><?= esc(lang('Catalog.pillars.cert_exam_sub')) ?></p>

                        <?php if ($exam['courses'] === []): ?>
                            <p class="mt-4 text-sm leading-relaxed text-white/55"><?= esc(lang('Catalog.pillars.cert_exam_none')) ?></p>
                        <?php else: ?>
                            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-white/45"><?= esc(lang('Catalog.pillars.cert_exam_courses')) ?></p>
                            <ul class="mt-2 space-y-2 text-sm">
                                <?php foreach ($exam['courses'] as $course): ?>
                                    <li>
                                        <a href="<?= esc(course_url($course['slug'])) ?>" class="font-medium underline decoration-line underline-offset-4 transition hover:text-brand-red">
                                            <?= esc(t_field($course['title'])) ?>
                                        </a>
                                        <?php // The alignment sentence the course
                                              // itself carries, verbatim. It is the
                                              // claim the course page makes, and
                                              // restating it in different words
                                              // here would be two answers to one
                                              // question. ?>
                                        <span class="mt-0.5 block text-xs leading-relaxed text-white/50">
                                            <?= esc(t_field($course['certification_alignment'])) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php // ── Specialty Credentials ───────────────────────────────────────── ?>
    <section aria-labelledby="specialty">
        <h2 id="specialty" class="section-title"><?= esc(lang('Catalog.pillars.cert_specialty')) ?></h2>
        <p class="mt-3 max-w-3xl leading-relaxed text-white/65"><?= esc(lang('Catalog.pillars.cert_specialty_intro')) ?></p>

        <ul class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($specialty as $credential): ?>
                <li class="rounded-2xl border border-line bg-surface p-5">
                    <p class="font-semibold leading-snug"><?= esc($credential) ?></p>
                    <p class="mt-2 text-xs leading-relaxed text-white/50"><?= esc(lang('Catalog.pillars.cert_specialty_rule')) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php // Named without their pairings on purpose — see the file docblock.
              // The link is emitted only when somebody has configured the
              // address, because a hard-coded external URL is one nobody can fix
              // without a deployment on the day Adobe moves the page, and a
              // wrong link matters more here than anywhere else on the site. ?>
        <p class="mt-6 max-w-3xl leading-relaxed text-white/70">
            <?= esc(lang('Catalog.pillars.cert_specialty_note')) ?>
            <?php if ($adobeUrl !== ''): ?>
                <a href="<?= esc($adobeUrl, 'attr') ?>" rel="noopener nofollow" target="_blank"
                   class="font-medium text-brand-red underline decoration-line underline-offset-4">
                    <?= esc(lang('Catalog.pillars.cert_adobe_link')) ?>
                </a>
            <?php endif; ?>
        </p>
    </section>

    <?php // ── Programmes that prepare for several exams at once ───────────── ?>
    <?php if ($programmes !== []): ?>
        <section aria-labelledby="programmes">
            <h2 id="programmes" class="section-title"><?= esc(lang('Catalog.pillars.cert_programmes')) ?></h2>
            <p class="mt-3 max-w-2xl text-white/65"><?= esc(lang('Catalog.pillars.cert_programmes_intro')) ?></p>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <?php foreach ($programmes as $programme): ?>
                    <article class="group relative flex flex-col rounded-2xl border border-line bg-surface p-6 transition hover:border-brand-red/40">
                        <p class="flex flex-wrap items-center gap-2 text-xs text-white/50">
                            <span class="chip"><?= esc(lang('Catalog.pillars.programme_courses', [(int) $programme['course_count']])) ?></span>
                        </p>

                        <h3 class="mt-3 text-lg font-semibold leading-snug">
                            <?php // The stretched link: one tab stop per card, and
                                  // the whole card is a pointer target. Nothing
                                  // else inside may be a link. ?>
                            <a href="<?= esc($programme['url']) ?>" class="after:absolute after:inset-0 hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                <?= esc(t_field($programme['title'])) ?>
                            </a>
                        </h3>

                        <?php if ($summary = t_field($programme['summary'])): ?>
                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto pt-5">
                            <?php if ($programme['price'] !== null): ?>
                                <p class="text-xl font-bold"><?= esc(money((int) $programme['price']['price_cents'], $currency)) ?></p>
                            <?php else: ?>
                                <p class="text-sm text-white/70"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php // ── The questions, marked up as FAQPage in the head ─────────────── ?>
    <?php if ($faqs !== []): ?>
        <section aria-labelledby="faq">
            <h2 id="faq" class="section-title"><?= esc(lang('Catalog.pillars.cert_faq')) ?></h2>

            <?php // details/summary rather than an Alpine accordion: it opens
                  // without JavaScript, it is keyboard-operable for free, and a
                  // search engine reads the closed content — which matters here,
                  // because these answers are the ones the structured data in the
                  // head is promising. ?>
            <div class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line">
                <?php foreach ($faqs as $faq): ?>
                    <details class="group bg-surface">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-medium">
                            <span><?= esc($faq['question']) ?></span>
                            <svg class="h-4 w-4 shrink-0 text-white/40 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        <div class="px-5 pb-5 text-sm leading-relaxed text-white/75"><?= esc($faq['answer']) ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php // ── The way on ──────────────────────────────────────────────────── ?>
    <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
        <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.pillars.team_heading')) ?></h2>
        <p class="mt-3 max-w-2xl leading-relaxed text-white/70"><?= esc(lang('Catalog.pillars.team_text')) ?></p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="<?= esc($catalogueUrl) ?>" class="btn-brand"><?= esc(lang('Catalog.pillars.all_courses', [lang('Catalog.pillar.adobe')])) ?></a>
            <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-ghost"><?= esc(lang('Catalog.pillars.team_cta')) ?></a>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
