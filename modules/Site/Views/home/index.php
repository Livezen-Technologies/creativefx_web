<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?php // ── B.IV Priority notices ─────────────────────────────────────────────
      // Above everything, because that is what "immediate public attention"
      // means. Renders nothing at all when there are none. ?>
<div class="pt-24 sm:pt-28">
    <?= view('Modules\Tshda\Views\partials\notice_band', ['notices' => $notices], ['saveData' => false]) ?>
</div>

<?php // ── Masthead ──────────────────────────────────────────────────────────
      // A government portal's first screen is not an advertisement. It says who
      // this is, and it puts the two things most visitors came for — the search
      // box and the way to their own service — above the fold. The photography
      // sits behind that, never in place of it. ?>
<?= view('Modules\Site\Views\home\_hero', ['heroSlides' => $heroSlides ?? []], ['saveData' => false]) ?>

<?php // ── B.I Stakeholder service clusters ──────────────────────────────────
      // Grouped by who you are, not by the Authority's org chart, so a visitor
      // reaches their own path in one click. ?>
<?php
$audienceOrder = ['smallholder', 'society', 'officer', 'supplier', 'jobseeker'];
$hasClusters   = array_filter($clusters ?? []);
?>
<?php if ($hasClusters): ?>
<section class="bg-brand-black py-16 sm:py-20" aria-labelledby="services-heading">
    <div class="container-x">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="services-heading" class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.services_title')) ?></h2>
                <p class="mt-2 max-w-2xl text-white/70"><?= esc(lang('Site.home.services_intro')) ?></p>
            </div>
            <a href="<?= esc(locale_url('services')) ?>" class="text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.home.all_services')) ?> &rarr;</a>
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-2">
            <?php foreach ($audienceOrder as $audience): ?>
                <?php $rows = $clusters[$audience] ?? []; if ($rows === []) { continue; } ?>
                <div class="rounded-2xl border border-line bg-surface p-6" data-gsap="reveal">
                    <h3 class="text-lg font-semibold"><?= esc(lang('Site.audience.' . $audience)) ?></h3>
                    <p class="mt-1 text-sm text-white/60"><?= esc(lang('Site.audience.' . $audience . '_note')) ?></p>
                    <ul class="mt-4 space-y-2.5" role="list">
                        <?php foreach (array_slice($rows, 0, 5) as $service): ?>
                            <li>
                                <a href="<?= esc(locale_url('services/' . $service['slug'])) ?>"
                                   class="group flex items-start gap-2.5 text-sm">
                                    <span aria-hidden="true" class="mt-[0.35rem] h-1.5 w-1.5 shrink-0 rounded-full bg-brand-red"></span>
                                    <span class="font-medium transition group-hover:text-brand-red"><?= esc(t_field($service['title'])) ?></span>
                                    <?php if ((int) $service['window_open'] !== 1): ?>
                                        <span class="ml-1 shrink-0 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.services.closed')) ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($rows) > 5): ?>
                        <a href="<?= esc(locale_url('services?for=' . $audience)) ?>" class="mt-4 inline-block text-sm font-semibold text-brand-red hover:underline">
                            <?= esc(lang('Site.home.more_for', [count($rows) - 5])) ?> &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── B.II News, press releases, announcements and events ───────────────
      // Latest first, straight off the newsroom. ?>
<?php if (! empty($posts)): ?>
<section class="border-y border-line bg-surface py-16 sm:py-20" aria-labelledby="news-heading">
    <div class="container-x">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="news-heading" class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.news_title')) ?></h2>
            <div class="flex flex-wrap gap-4 text-sm font-semibold">
                <a href="<?= esc(locale_url('announcements')) ?>" class="text-brand-red hover:underline"><?= esc(lang('Site.nav.announcements')) ?> &rarr;</a>
                <a href="<?= esc(locale_url('news')) ?>" class="text-brand-red hover:underline"><?= esc(lang('Site.home.all_news')) ?> &rarr;</a>
            </div>
        </div>

        <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
            <?php foreach ($posts as $post): ?>
                <li class="flex flex-col rounded-2xl border border-line bg-brand-black p-5" data-gsap="reveal">
                    <?php if (! empty($post['published_at'])): ?>
                        <time datetime="<?= esc(date('Y-m-d', strtotime((string) $post['published_at'])), 'attr') ?>"
                              class="text-xs font-semibold uppercase tracking-wider text-white/50">
                            <?= esc(date('j M Y', strtotime((string) $post['published_at']))) ?>
                        </time>
                    <?php endif; ?>
                    <h3 class="mt-2 text-base font-semibold leading-snug">
                        <a href="<?= esc(locale_url('news/' . $post['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($post['title'])) ?></a>
                    </h3>
                    <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-white/60"><?= esc(mb_substr(strip_tags(t_field($post['excerpt'] ?: $post['body'])), 0, 160)) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<?php // ── Training calendar + statistics ────────────────────────────────────
      // The two things the Authority publishes on a schedule, side by side. ?>
<?php if (! empty($programmes) || ! empty($datasets)): ?>
<section class="bg-brand-black py-16 sm:py-20">
    <div class="container-x grid gap-10 lg:grid-cols-2">
        <?php if (! empty($programmes)): ?>
            <div>
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.training_title')) ?></h2>
                    <a href="<?= esc(locale_url('hantana')) ?>" class="text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.home.full_calendar')) ?> &rarr;</a>
                </div>
                <ul class="mt-6 divide-y divide-line rounded-2xl border border-line" role="list">
                    <?php foreach ($programmes as $programme): ?>
                        <li class="flex items-start gap-4 p-5">
                            <?php if (! empty($programme['starts_on'])): ?>
                                <div class="w-14 shrink-0 rounded-lg border border-line bg-surface py-2 text-center">
                                    <span class="block text-lg font-bold leading-none"><?= esc(date('j', strtotime((string) $programme['starts_on']))) ?></span>
                                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-white/60"><?= esc(date('M', strtotime((string) $programme['starts_on']))) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold leading-snug">
                                    <a href="<?= esc(locale_url('hantana/' . $programme['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($programme['title'])) ?></a>
                                </h3>
                                <p class="mt-1 text-xs text-white/60">
                                    <?= esc((int) $programme['residential'] === 1 ? lang('Site.hantana.residential') : lang('Site.hantana.non_residential')) ?>
                                    <?php $left = \Modules\Tshda\Models\ProgrammeModel::seatsLeft($programme); ?>
                                    <?php if ($left !== null): ?>
                                        · <?= esc(lang('Site.hantana.seats_left', [$left])) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (! empty($datasets)): ?>
            <div>
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.statistics_title')) ?></h2>
                    <a href="<?= esc(locale_url('statistics')) ?>" class="text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.home.all_statistics')) ?> &rarr;</a>
                </div>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2" role="list">
                    <?php foreach (array_slice($datasets, 0, 4) as $dataset): ?>
                        <li class="rounded-2xl border border-line bg-surface p-5">
                            <h3 class="text-sm font-semibold leading-snug">
                                <a href="<?= esc(locale_url('statistics/' . $dataset['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($dataset['title'])) ?></a>
                            </h3>
                            <p class="mt-1.5 line-clamp-3 text-xs leading-relaxed text-white/60"><?= esc(t_field($dataset['description'])) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php // ── B.V Discussion topic + moderated comments ─────────────────────────
      // and B.III the Facebook wall, side by side: both are "what people are
      // saying", and the social feed is the one that may not arrive. ?>
<section class="border-t border-line bg-surface py-16 sm:py-20">
    <div class="container-x grid gap-10 lg:grid-cols-2">
        <div>
            <h2 class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.discussion_title')) ?></h2>
            <?php if (! empty($topic)): ?>
                <h3 class="mt-4 text-lg font-semibold">
                    <a href="<?= esc(locale_url('discussion/' . $topic['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($topic['title'])) ?></a>
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(mb_substr(strip_tags(t_field($topic['body'])), 0, 260)) ?>…</p>

                <?php if (! empty($comments)): ?>
                    <ul class="mt-6 space-y-4" role="list">
                        <?php foreach ($comments as $comment): ?>
                            <li class="rounded-xl border border-line bg-brand-black p-4">
                                <p class="text-sm leading-relaxed text-white/80"><?= esc(mb_substr($comment['body'], 0, 220)) ?></p>
                                <p class="mt-2 text-xs text-white/50">
                                    <?= esc($comment['author']) ?><?= ! empty($comment['district']) ? ' · ' . esc($comment['district']) : '' ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <a href="<?= esc(locale_url('discussion/' . $topic['slug'])) ?>" class="btn-brand mt-6 inline-flex"><?= esc(lang('Site.home.join_discussion')) ?></a>
            <?php else: ?>
                <p class="mt-4 text-sm text-white/60"><?= esc(lang('Site.home.no_discussion')) ?></p>
            <?php endif; ?>
        </div>

        <?php // B.III The official Facebook wall. Loaded after the page is
              // interactive and inside an <iframe> so a slow or unavailable
              // social service can never delay this page or block its render —
              // the specific requirement in Clause 3.9 B.III. ?>
        <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 800)">
            <h2 class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.facebook_title')) ?></h2>
            <?php $fb = trim((string) setting('facebook', '', 'social')); ?>
            <?php if ($fb !== ''): ?>
                <div class="mt-4 overflow-hidden rounded-2xl border border-line bg-brand-black">
                    <template x-if="shown">
                        <iframe
                            :src="'https://www.facebook.com/plugins/page.php?href=<?= rawurlencode($fb) ?>&tabs=timeline&width=500&height=560&small_header=true&adapt_container_width=true&hide_cover=false&show_facepile=false'"
                            title="<?= esc(lang('Site.home.facebook_title'), 'attr') ?>"
                            class="h-[560px] w-full border-0" loading="lazy" scrolling="no"
                            allow="encrypted-media"></iframe>
                    </template>
                    <div x-show="!shown" class="flex h-[560px] items-center justify-center text-sm text-white/50">
                        <?= esc(lang('Site.home.facebook_loading')) ?>
                    </div>
                </div>
                <p class="mt-3 text-sm">
                    <a href="<?= esc($fb) ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-red hover:underline"><?= esc(lang('Site.home.facebook_open')) ?> &rarr;</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php // ── B.VI Media gallery + B.VII Related organisations ──────────────────
      // and the alert subscription, which is the thing a visitor who read the
      // notices at the top of this page most plausibly wants next. ?>
<section class="bg-brand-black py-16 sm:py-20">
    <div class="container-x grid gap-10 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <?= view('Modules\Tshda\Views\partials\alerts_form', [], ['saveData' => false]) ?>

            <?php if (! empty($links)): ?>
                <h2 class="mt-12 text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.related_title')) ?></h2>
                <ul class="mt-5 grid gap-3 sm:grid-cols-2" role="list">
                    <?php foreach ($links as $link): ?>
                        <li>
                            <a href="<?= esc($link['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface px-4 py-3 text-sm font-medium transition hover:border-brand-red">
                                <span><?= esc(t_field($link['name'])) ?></span>
                                <span aria-hidden="true" class="text-brand-red">&#8599;</span>
                                <span class="sr-only"><?= esc(lang('Site.home.opens_new_tab')) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-2xl font-bold sm:text-3xl"><?= esc(lang('Site.home.gallery_title')) ?></h2>
            <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.home.gallery_intro')) ?></p>
            <div class="mt-5 flex flex-col gap-3">
                <a href="<?= esc(locale_url('gallery')) ?>" class="rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold transition hover:border-brand-red"><?= esc(lang('Site.nav.photo_gallery')) ?> &rarr;</a>
                <a href="<?= esc(locale_url('videos')) ?>" class="rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold transition hover:border-brand-red"><?= esc(lang('Site.nav.video_gallery')) ?> &rarr;</a>
                <a href="<?= esc(locale_url('downloads')) ?>" class="rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold transition hover:border-brand-red"><?= esc(lang('Site.nav.downloads')) ?> &rarr;</a>
                <a href="<?= esc(locale_url('vacancies')) ?>" class="rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold transition hover:border-brand-red"><?= esc(lang('Site.nav.vacancies')) ?> &rarr;</a>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
