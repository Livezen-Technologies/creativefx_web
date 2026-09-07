<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The home page, in the order the blueprint sets out — which is also the order
 * a buyer decides in: what is this, is it real, what do you teach, when can I
 * come, how does it work, what does it cost together, why you, who teaches,
 * can you do this for my team, what did others think, what have you written,
 * and finally the low-commitment ask.
 *
 * Two sections tell the truth about a school that has just opened rather than
 * dressing it up. The reviews section says there are none yet; the instructor
 * cards say they are faculty profiles rather than people. Both were tempting
 * places to invent something, and both are the reason the rest of the page can
 * be believed.
 *
 * @var list<array> $upcoming
 * @var array       $pillars
 * @var list<array> $featured
 * @var list<array> $bundles
 * @var list<array> $instructors
 * @var list<array> $reviews
 * @var list<array> $posts
 * @var list<array> $facts
 * @var string      $currency
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\home\_hero', [
    'upcoming' => $upcoming,
    'currency' => $currency,
    'facts'    => $facts,
], ['saveData' => false]) ?>

<?php // ── The two pillars ───────────────────────────────────────────────── ?>
<section class="py-16 sm:py-20" aria-labelledby="pillars">
    <div class="container-x">
        <h2 id="pillars" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Site.home.pillars_heading')) ?></h2>
        <p class="mt-4 max-w-3xl text-lg leading-relaxed text-white/70"><?= esc(lang('Site.home.pillars_intro')) ?></p>

        <div class="mt-10 grid gap-6 lg:grid-cols-2">
            <?php foreach (['adobe', 'ai'] as $key):
                $pillar = $pillars[$key] ?? ['categories' => [], 'courses' => [], 'count' => 0]; ?>
                <article class="flex flex-col rounded-3xl border border-line bg-surface p-7 sm:p-9">
                    <h3 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Site.home.pillar_' . $key . '_title')) ?></h3>
                    <p class="mt-4 leading-relaxed text-white/70"><?= esc(lang('Site.home.pillar_' . $key . '_text')) ?></p>

                    <?php if ($pillar['categories'] !== []): ?>
                        <ul class="mt-6 flex flex-wrap gap-2">
                            <?php foreach ($pillar['categories'] as $category): ?>
                                <li>
                                    <a href="<?= esc(locale_url('courses/' . $category['slug'])) ?>" class="chip transition hover:bg-brand-red/10 hover:text-brand-red">
                                        <?= esc(t_field($category['name'])) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <a href="<?= esc(locale_url($key)) ?>" class="btn-brand mt-8 self-start">
                        <?= esc(lang('Catalog.pillars.all_courses', [(int) $pillar['count']])) ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php // ── Upcoming dates. The conversion engine. ────────────────────────── ?>
<section class="border-y border-line bg-surface py-16 sm:py-20" aria-labelledby="upcoming">
    <div class="container-x">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-2xl">
                <h2 id="upcoming" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Site.home.upcoming_heading')) ?></h2>
                <p class="mt-3 leading-relaxed text-white/70"><?= esc(lang('Site.home.upcoming_intro')) ?></p>
            </div>
            <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Site.home.upcoming_all')) ?></a>
        </div>

        <?php if ($upcoming === []): ?>
            <p class="mt-8 max-w-2xl text-white/60"><?= esc(lang('Site.home.upcoming_none')) ?></p>
        <?php else: ?>
            <div class="mt-8 overflow-x-auto rounded-2xl border border-line bg-brand-black">
                <table class="w-full min-w-[46rem] border-collapse text-sm">
                    <caption class="sr-only"><?= esc(lang('Site.home.upcoming_caption')) ?></caption>
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-white/45">
                            <th scope="col" class="px-5 py-3 font-semibold"><?= esc(lang('Catalog.dates.when')) ?></th>
                            <th scope="col" class="px-5 py-3 font-semibold"><?= esc(lang('Catalog.courses.title')) ?></th>
                            <th scope="col" class="px-5 py-3 font-semibold"><?= esc(lang('Catalog.dates.mode')) ?></th>
                            <th scope="col" class="px-5 py-3 font-semibold"><?= esc(lang('Catalog.dates.seats')) ?></th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold"><?= esc(lang('Catalog.dates.price')) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <?php foreach ($upcoming as $session):
                            $seats = seats_note($session); ?>
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3.5 font-medium"><?= esc(session_dates($session)) ?></td>
                                <td class="px-5 py-3.5">
                                    <a href="<?= esc(course_url($session['course_slug'])) ?>" class="underline decoration-line underline-offset-4 hover:text-brand-red">
                                        <?= esc(t_field($session['course_title'])) ?>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-white/65">
                                    <?= esc(mode_label($session['mode'])) ?><?php if (! empty($session['venue_city'])): ?> · <?= esc($session['venue_city']) ?><?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 <?= $seats['urgent'] ? 'font-semibold text-gold' : 'text-white/55' ?>"><?= esc($seats['text']) ?></td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold tabular-nums">
                                    <?= esc(money($session['price_cents'] ?? null, $currency)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php // ── The four ways to learn ────────────────────────────────────────── ?>
<section class="py-16 sm:py-20" aria-labelledby="modes">
    <div class="container-x">
        <h2 id="modes" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Site.home.modes_heading')) ?></h2>
        <p class="mt-3 max-w-3xl leading-relaxed text-white/70"><?= esc(lang('Site.home.modes_intro')) ?></p>

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach (['LIVE_ONLINE', 'CLASSROOM', 'SELF_PACED', 'PRIVATE'] as $mode):
                $key = strtolower($mode); ?>
                <article class="rounded-2xl border border-line bg-surface p-6">
                    <h3 class="font-semibold"><?= esc(mode_label($mode)) ?></h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/65"><?= esc(lang('Site.home.mode_' . $key)) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php // ── Certificate programmes ────────────────────────────────────────── ?>
<?php if ($bundles !== []): ?>
    <section class="border-y border-line bg-surface py-16 sm:py-20" aria-labelledby="programmes">
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <h2 id="programmes" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Catalog.bundles.certificates_title')) ?></h2>
                    <p class="mt-3 leading-relaxed text-white/70"><?= esc(lang('Catalog.bundles.certificates_intro')) ?></p>
                </div>
                <a href="<?= esc(locale_url('certificates')) ?>" class="btn-ghost"><?= esc(lang('Site.home.programmes_all')) ?></a>
            </div>

            <div class="mt-8 grid gap-5 lg:grid-cols-3">
                <?php foreach ($bundles as $bundle): ?>
                    <article class="relative flex flex-col rounded-2xl border border-line bg-brand-black p-6">
                        <h3 class="text-lg font-semibold leading-snug">
                            <a href="<?= esc(locale_url('certificates/' . $bundle['slug'])) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                                <?= esc(t_field($bundle['title'])) ?>
                            </a>
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/65"><?= esc(t_field($bundle['summary'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php // ── Why here ──────────────────────────────────────────────────────── ?>
<section class="py-16 sm:py-20" aria-labelledby="why">
    <div class="container-x">
        <h2 id="why" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Site.home.why_heading')) ?></h2>
        <ul class="mt-10 grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <li>
                    <h3 class="font-semibold text-brand-red"><?= esc(lang('Site.home.why_' . $i . '_title')) ?></h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.home.why_' . $i . '_text')) ?></p>
                </li>
            <?php endfor; ?>
        </ul>
    </div>
</section>

<?php // ── Featured courses ──────────────────────────────────────────────── ?>
<?php if ($featured !== []): ?>
    <section class="border-y border-line bg-surface py-16 sm:py-20" aria-labelledby="featured">
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="featured" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Site.home.featured_heading')) ?></h2>
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-ghost"><?= esc(lang('Catalog.courses.all')) ?></a>
            </div>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($featured as $course): ?>
                    <?= view('Modules\Catalog\Views\partials\course_card', ['course' => $course], ['saveData' => false]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php // ── Who teaches ───────────────────────────────────────────────────── ?>
<?php if ($instructors !== []): ?>
    <section class="py-16 sm:py-20" aria-labelledby="faculty">
        <div class="container-x">
            <h2 id="faculty" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Catalog.instructors.title')) ?></h2>
            <p class="mt-3 max-w-2xl leading-relaxed text-white/70"><?= esc(lang('Catalog.instructors.intro')) ?></p>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($instructors as $instructor): ?>
                    <article class="relative rounded-2xl border border-line bg-surface p-5">
                        <h3 class="font-semibold">
                            <a href="<?= esc(locale_url('instructors/' . $instructor['slug'])) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                                <?= esc($instructor['name']) ?>
                            </a>
                        </h3>
                        <p class="mt-1 text-sm text-white/60"><?= esc(t_field($instructor['headline'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php // ── Corporate ─────────────────────────────────────────────────────── ?>
<section class="border-y border-line bg-surface py-16 sm:py-20" aria-labelledby="corporate">
    <div class="container-x flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
            <h2 id="corporate" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Site.home.corporate_heading')) ?></h2>
            <p class="mt-3 leading-relaxed text-white/70"><?= esc(lang('Site.home.corporate_text')) ?></p>
        </div>
        <a href="<?= esc(locale_url('corporate')) ?>" class="btn-brand shrink-0"><?= esc(lang('Site.home.corporate_cta')) ?></a>
    </div>
</section>

<?php // ── Reviews, or the honest absence of them ────────────────────────── ?>
<section class="py-16 sm:py-20" aria-labelledby="reviews">
    <div class="container-x">
        <h2 id="reviews" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Catalog.reviews.title')) ?></h2>
        <?php if ($reviews === []): ?>
            <?php // Deliberate. A school with no learners yet that shows six
                  // glowing quotes has told its first lie on its front page. ?>
            <p class="mt-4 max-w-2xl leading-relaxed text-white/65"><?= esc(lang('Catalog.reviews.none')) ?></p>
        <?php else: ?>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($reviews as $review): ?>
                    <blockquote class="rounded-2xl border border-line bg-surface p-5">
                        <p class="text-gold" aria-label="<?= esc(lang('Catalog.course.rating_of', [(int) $review['rating']]), 'attr') ?>">
                            <?= str_repeat('★', (int) $review['rating']) ?><span class="text-white/20"><?= str_repeat('★', 5 - (int) $review['rating']) ?></span>
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-white/75"><?= esc($review['body']) ?></p>
                        <footer class="mt-3 text-xs text-white/50">
                            <?= esc($review['author_name'] ?: lang('Catalog.course.review_anon')) ?> ·
                            <a href="<?= esc(course_url($review['course_slug'])) ?>" class="underline decoration-line underline-offset-4"><?= esc(t_field($review['course_title'])) ?></a>
                        </footer>
                    </blockquote>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php // ── From the blog ─────────────────────────────────────────────────── ?>
<?php if ($posts !== []): ?>
    <section class="border-y border-line bg-surface py-16 sm:py-20" aria-labelledby="blog">
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="blog" class="text-2xl font-bold sm:text-4xl"><?= esc(lang('Site.home.blog_heading')) ?></h2>
                <a href="<?= esc(locale_url('blog')) ?>" class="btn-ghost"><?= esc(lang('Site.home.blog_all')) ?></a>
            </div>
            <div class="mt-8 grid gap-5 lg:grid-cols-3">
                <?php foreach ($posts as $post): ?>
                    <article class="relative flex flex-col rounded-2xl border border-line bg-brand-black p-6">
                        <h3 class="text-lg font-semibold leading-snug">
                            <a href="<?= esc(locale_url('blog/' . $post['slug'])) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                                <?= esc(t_field($post['title'])) ?>
                            </a>
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/65"><?= esc(t_field($post['excerpt'])) ?></p>
                        <?php if (! empty($post['reading_min'])): ?>
                            <p class="mt-3 text-xs text-white/45"><?= esc(lang('Site.home.reading_min', [(int) $post['reading_min']])) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php // ── The low-commitment ask ────────────────────────────────────────── ?>
<section class="py-16 sm:py-20" aria-labelledby="newsletter">
    <div class="container-x">
        <div class="rounded-3xl border border-line bg-surface p-7 sm:p-10">
            <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 id="newsletter" class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.news_heading')) ?></h2>
                    <p class="mt-3 leading-relaxed text-white/70"><?= esc(lang('Site.home.news_text')) ?></p>
                </div>
                <form method="post" action="<?= esc(locale_url('subscribe')) ?>" class="space-y-3">
                    <?= csrf_field() ?>
                    <label class="field-label" for="news-email"><?= esc(lang('Site.home.news_email')) ?></label>
                    <div class="flex flex-wrap gap-2">
                        <input id="news-email" name="email" type="email" required autocomplete="email"
                               class="field min-w-0 flex-1" placeholder="<?= esc(lang('Site.home.news_email'), 'attr') ?>">
                        <button type="submit" class="btn-brand"><?= esc(lang('Site.home.news_cta')) ?></button>
                    </div>
                    <?php // Un-ticked, and worded so that ticking it is a
                          // decision. A pre-ticked consent box is not consent
                          // under either the GDPR or the Sri Lanka PDPA. ?>
                    <label class="flex items-start gap-2 text-sm text-white/60">
                        <input type="checkbox" name="consent" value="1" required class="mt-1">
                        <span><?= esc(lang('Site.home.news_consent')) ?></span>
                    </label>
                </form>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
