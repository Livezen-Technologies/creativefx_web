<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One programme — a certificate track or a bootcamp — and the page that sells it.
 *
 * A bundle page answers a different question from a course page. Nobody arrives
 * here asking "when does it run": they arrive asking "what is in it, and is it
 * cheaper than buying those separately". So the order is what it is, what is in
 * it and in which order, what you come out able to do, what comes with it, the
 * questions, and the price with the saving stated as a figure rather than as an
 * adjective.
 *
 * The panel carries the one thing a course panel does not have to say: **a
 * programme is not a date**. It holds no seats, it books no class, and the
 * learner picks the dates for each course afterwards from their account. Said
 * at the button, because the alternative is somebody paying and then hunting
 * for the confirmation of a date that was never part of what they bought.
 *
 * @var array       $bundle
 * @var string      $section  certificates | bootcamps
 * @var list<array> $courses  in programme order, each with is_required and from_cents
 * @var array       $detail   outcomes, includes, faqs
 * @var array|null  $price    price_cents and compare_at_cents, or null in this currency
 * @var int|null    $saving   minor units, or null when it cannot be worked out
 * @var int         $hours    taught hours across the programme
 * @var string      $currency
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);


// What the same courses would have cost one at a time. Derived from the saving
// rather than from `compare_at_cents` so that the two figures on the page
// cannot contradict each other: the saving is the difference between these two
// numbers by construction, and a separately-entered "was" price would not be.
$separately = $price !== null && $saving !== null ? (int) $price['price_cents'] + $saving : null;
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php // ── Above the fold ─────────────────────────────────────────────────── ?>
<section class="relative overflow-hidden border-b border-line pb-10 pt-32 sm:pt-36">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-40"></div>
    <div class="container-x">
        <?= view('Modules\Site\Views\partials\breadcrumbs', ['crumbs' => $crumbs], ['saveData' => false]) ?>

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="chip"><?= esc(lang('Catalog.bundles.courses_count', [count($courses)])) ?></span>
                    <?php if ($hours > 0): ?>
                        <span class="chip"><?= esc(lang('Catalog.duration.hours', [$hours])) ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-5xl"><?= esc(t_field($bundle['title'])) ?></h1>

                <?php if ($subtitle = t_field($bundle['subtitle'])): ?>
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-white/70"><?= esc($subtitle) ?></p>
                <?php endif; ?>
            </div>

            <?php // ── The panel ──────────────────────────────────────────── ?>
            <aside class="lg:sticky lg:top-28 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-line bg-surface shadow-lg shadow-brand-black/5">
                    <div class="p-6">
                        <?php if ($price !== null): ?>
                            <p class="flex flex-wrap items-baseline gap-3">
                                <span class="text-3xl font-bold"><?= esc(money((int) $price['price_cents'], $currency)) ?></span>
                                <?php if ($separately !== null): ?>
                                    <?php // The comparison a buyer would
                                          // otherwise do with a calculator, done
                                          // for them and labelled so it cannot be
                                          // mistaken for a discount off a list
                                          // price nobody ever paid. ?>
                                    <span class="text-base text-white/40 line-through"><?= esc(money($separately, $currency)) ?></span>
                                <?php endif; ?>
                            </p>
                            <?php if ($separately !== null): ?>
                                <p class="mt-1 text-xs text-white/45"><?= esc(lang('Catalog.bundles.separately')) ?></p>
                            <?php endif; ?>

                            <?php if ($saving !== null): ?>
                                <?php // The whole commercial point of a bundle,
                                      // so it gets the accent chip rather than a
                                      // line of small print. It is absent — not
                                      // shown as zero — whenever the sum cannot
                                      // be worked out in this currency. ?>
                                <p class="mt-3">
                                    <span class="chip chip-accent"><?= esc(lang('Catalog.bundles.saving', [money($saving, $currency)])) ?></span>
                                </p>
                            <?php endif; ?>

                            <p class="mt-3 text-xs text-white/50"><?= esc(lang('Catalog.panel.per_seat')) ?></p>

                            <form method="post" action="<?= esc(locale_url('cart/add')) ?>" class="mt-6">
                                <?= csrf_field() ?>
                                <input type="hidden" name="item_type" value="bundle">
                                <input type="hidden" name="item_id" value="<?= (int) $bundle['id'] ?>">
                                <label class="sr-only" for="bundle-qty"><?= esc(lang('Catalog.bundles.people')) ?></label>
                                <div class="flex gap-2">
                                    <input id="bundle-qty" name="qty" type="number" min="1" max="20" value="1"
                                           class="field w-20 text-center" inputmode="numeric">
                                    <button type="submit" class="btn-brand flex-1"><?= esc(lang('Catalog.bundles.buy')) ?></button>
                                </div>
                            </form>

                            <?php // Directly under the button, because this is
                                  // the single thing about a programme that
                                  // differs from every other purchase on the
                                  // site and the worst moment to discover it is
                                  // after paying. ?>
                            <p class="mt-3 text-sm leading-relaxed text-white/60">
                                <?= esc(lang('Catalog.bundles.no_seats')) ?>
                            </p>
                        <?php else: ?>
                            <?php // No published price in this currency. Said
                                  // plainly rather than converted from the other
                                  // — see PricingService for why nothing on this
                                  // site is priced at runtime — and with a route
                                  // onward, because somebody who wanted this
                                  // programme should not leave over a currency. ?>
                            <p class="text-white/70"><?= esc(lang('Catalog.panel.price_on_request')) ?></p>
                            <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-brand mt-6 w-full">
                                <?= esc(lang('Catalog.panel.request_quote')) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php // The three objections a buyer raises last. They are
                          // true of every course inside the programme, which is
                          // what makes them true of the programme. ?>
                    <ul class="space-y-1.5 border-t border-line px-6 py-4 text-xs text-white/55">
                        <li><?= esc(lang('Catalog.panel.assure_transfer')) ?></li>
                        <li><?= esc(lang('Catalog.panel.assure_retake')) ?></li>
                        <li><?= esc(lang('Catalog.panel.assure_recording')) ?></li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>

<div class="container-x grid gap-14 py-14 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">
    <div class="min-w-0 space-y-14">

        <?php // ── What it is ─────────────────────────────────────────────── ?>
        <?php if ($body = rich_text($bundle['description'])): ?>
            <section aria-labelledby="overview">
                <h2 id="overview" class="section-title"><?= esc(lang('Catalog.bundles.overview')) ?></h2>
                <div class="prose-site mt-5"><?= $body ?></div>
            </section>
        <?php endif; ?>

        <?php // ── What is in it, in the order it is taken ────────────────── ?>
        <?php if ($courses !== []): ?>
            <section aria-labelledby="courses">
                <h2 id="courses" class="section-title"><?= esc(lang('Catalog.bundles.includes')) ?></h2>

                <?php // An ordered list rather than a table: the order is the
                      // information, and five columns of it would need a
                      // sideways scroll on the phone most of this traffic
                      // arrives on. ?>
                <ol class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line">
                    <?php foreach ($courses as $i => $course):
                        // A course withdrawn from publication is still part of
                        // what is being sold and is still counted in the price,
                        // so it stays on the list — but its page 404s, so it is
                        // not linked.
                        $published = (string) $course['status'] === 'published';
                    ?>
                        <li class="flex flex-col gap-4 bg-surface p-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <?php // Decorative: the <ol> already carries the
                                      // ordering for anything that reads the
                                      // page rather than looks at it. ?>
                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-line text-xs font-semibold tabular-nums text-white/60" aria-hidden="true">
                                    <?= (int) $i + 1 ?>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="font-semibold leading-snug">
                                        <?php if ($published): ?>
                                            <a href="<?= esc(course_url($course['slug'])) ?>" class="underline decoration-line underline-offset-4 hover:text-brand-red">
                                                <?= esc(t_field($course['title'])) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= esc(t_field($course['title'])) ?>
                                        <?php endif; ?>
                                    </h3>

                                    <p class="mt-2 flex flex-wrap items-center gap-2 text-xs text-white/55">
                                        <?php // Required or optional, on every
                                              // row rather than only on the
                                              // exceptions: a track that says
                                              // "Required" four times and
                                              // "Optional" five is readable at a
                                              // glance, and one that marks only
                                              // the optional ones makes the
                                              // reader infer the rest. ?>
                                        <span class="<?= (int) $course['is_required'] === 1 ? 'chip chip-accent' : 'chip' ?>">
                                            <?= esc((int) $course['is_required'] === 1 ? lang('Catalog.bundles.required') : lang('Catalog.bundles.optional')) ?>
                                        </span>
                                        <?php if ($level = level_label((int) $course['level'])): ?>
                                            <span class="chip"><?= esc($level) ?></span>
                                        <?php endif; ?>
                                        <?php if ($d = duration_label($course)): ?><span><?= esc($d) ?></span><?php endif; ?>
                                    </p>

                                    <?php if ($summary = t_field($course['summary'])): ?>
                                        <p class="mt-2 text-sm leading-relaxed text-white/65"><?= esc($summary) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($course['from_cents'] !== null): ?>
                                <?php // The same figure the saving is measured
                                      // against, so a buyer who adds this column
                                      // up arrives at the struck-through total
                                      // in the panel rather than at a
                                      // discrepancy. ?>
                                <p class="shrink-0 pl-11 text-sm sm:pl-0 sm:text-right">
                                    <span class="text-white/45"><?= esc(lang('Catalog.panel.from')) ?></span>
                                    <span class="ml-1 font-semibold sm:ml-0 sm:block"><?= esc(money((int) $course['from_cents'], $currency)) ?></span>
                                </p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endif; ?>

        <?php // ── What you come out able to do ───────────────────────────── ?>
        <?php if ($detail['outcomes'] !== []): ?>
            <section aria-labelledby="outcomes">
                <h2 id="outcomes" class="section-title"><?= esc(lang('Catalog.course.outcomes')) ?></h2>
                <?php // Not truncated. This is a multi-course programme costing
                      // several times what one course does, and a "what you will
                      // learn" list cut off at a tidy number is the thing that
                      // sends a buyer away to find the full one. ?>
                <ul class="mt-5 grid gap-x-8 gap-y-3 sm:grid-cols-2">
                    <?php foreach ($detail['outcomes'] as $outcome): ?>
                        <li class="flex gap-3 text-white/80">
                            <svg class="mt-1 h-4 w-4 shrink-0 text-brand-red" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10.5l4 4 8-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span><?= esc(t_field($outcome['text'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php // ── What comes with it ─────────────────────────────────────── ?>
        <?php if ($detail['includes'] !== []): ?>
            <section aria-labelledby="included">
                <h2 id="included" class="section-title"><?= esc(lang('Catalog.course.included')) ?></h2>
                <ul class="mt-5 grid gap-x-8 gap-y-2.5 text-white/80 sm:grid-cols-2">
                    <?php foreach ($detail['includes'] as $item): ?>
                        <li class="flex gap-3">
                            <svg class="mt-1 h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/><path d="M6.5 10.2l2.4 2.4 4.6-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span><?= esc(t_field($item['text'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php // ── Questions. Marked up as FAQPage in the head. ───────────── ?>
        <?php if ($detail['faqs'] !== []): ?>
            <section aria-labelledby="faq">
                <h2 id="faq" class="section-title"><?= esc(lang('Catalog.course.faq')) ?></h2>
                <?php // Details/summary rather than an Alpine accordion: it opens
                      // without JavaScript, it is keyboard-operable for free, and
                      // a search engine reads the closed content. ?>
                <div class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line">
                    <?php foreach ($detail['faqs'] as $faq): ?>
                        <details class="group bg-surface">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-medium">
                                <span><?= esc(t_field($faq['question'])) ?></span>
                                <svg class="h-4 w-4 shrink-0 text-white/40 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </summary>
                            <div class="prose-site prose-sm px-5 pb-5 text-sm"><?= rich_text($faq['answer']) ?></div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php // ── The way out for a team ─────────────────────────────────── ?>
        <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
            <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.course.corporate_heading')) ?></h2>
            <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.course.corporate_text')) ?></p>
            <a href="<?= esc(locale_url('corporate/request-quote')) ?>" class="btn-brand mt-5">
                <?= esc(lang('Catalog.course.corporate_cta')) ?>
            </a>
        </section>

        <?php // The way back up. The breadcrumb does this too, but it is four
              // thousand pixels away by the time somebody has read to here. ?>
        <p class="text-sm">
            <a href="<?= esc(locale_url($section)) ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4">
                <span aria-hidden="true">&larr;</span>
                <?= esc($section === 'bootcamps' ? lang('Catalog.bundles.bootcamps_title') : lang('Catalog.bundles.certificates_title')) ?>
            </a>
        </p>
    </div>

    <?php // Deliberately empty. This column exists only so the body runs to the
          // same measure as the heading above it; the panel occupies the
          // matching column in the hero grid, and anything put in this track
          // would read as belonging to the panel rather than to the page. ?>
    <div aria-hidden="true"></div>
</div>

<?= $this->endSection() ?>
