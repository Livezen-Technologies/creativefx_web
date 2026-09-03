<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

// Category id => localized name (for card labels).
$catNames = [];
foreach ($categories as $c) {
    $catNames[(int) $c['id']] = t_field(json_decode($c['name'] ?? '[]', true) ?: []);
}

// Listing URL preserving the active category filter.
$newsUrl = static function (int $page = 1) use ($activeCategory): string {
    $qs = http_build_query(array_filter([
        'category' => $activeCategory['slug'] ?? null,
        'page'     => $page > 1 ? $page : null,
    ]));
    return locale_url('news') . ($qs !== '' ? '?' . $qs : '');
};

// On the first unfiltered page the newest article becomes the large lead card.
$lead = null;
$grid = $posts;
if ($currentPage === 1 && $activeCategory === null && $posts !== []) {
    $lead = $grid[0];
    $grid = array_slice($grid, 1);
}
?>
<?= $this->section('content') ?>

<!-- Newsroom hero -->
<section class="relative overflow-hidden">
    <!-- Photographic hero, dressed with the same washes as the CMS page heroes
         so this band reads like the rest of the site instead of a flat panel. -->
    <div class="hero-wash-side--page absolute inset-0 -z-20"></div>
    <div class="hero-wash-foot--page absolute inset-0 -z-20"></div>
    <div class="hero-red-glow absolute inset-0 -z-10"></div>
    <div class="container-x flex min-h-[34vh] flex-col justify-end pb-10 pt-36">
        <p class="text-xs font-semibold uppercase tracking-widest text-brand-red" data-gsap="reveal"><?= esc(lang('Site.news.eyebrow')) ?></p>
        <h1 class="mt-3 max-w-3xl text-4xl font-bold leading-[1.05] sm:text-5xl" data-gsap="reveal"><?= esc(lang('Site.news.title')) ?></h1>
        <p class="mt-4 max-w-2xl leading-relaxed text-white/60" data-gsap="reveal"><?= esc(lang('Site.news.intro')) ?></p>
    </div>
</section>

<section class="bg-brand-black pb-20">
    <div class="container-x">
        <!-- Category filter -->
        <nav class="flex flex-wrap gap-2 border-b border-white/10 py-6" aria-label="News categories">
            <?php $chip = static fn (bool $on): string => $on
                ? 'rounded-full bg-brand-red px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white'
                : 'rounded-full border border-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/60 transition hover:border-brand-red/50 hover:text-white'; ?>
            <a href="<?= esc(locale_url('news')) ?>" class="<?= $chip($activeCategory === null) ?>"><?= esc(lang('Site.news.all')) ?></a>
            <?php foreach ($categories as $c): ?>
                <a href="<?= esc(locale_url('news') . '?category=' . $c['slug']) ?>"
                   class="<?= $chip(($activeCategory['slug'] ?? '') === $c['slug']) ?>"><?= esc($catNames[(int) $c['id']]) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($posts === []): ?>
            <p class="py-20 text-center text-white/50"><?= esc(lang('Site.news.none')) ?></p>
        <?php else: ?>

            <?php if ($lead !== null):
                $lTitle   = t_field(json_decode($lead['title'] ?? '[]', true) ?: []);
                $lExcerpt = t_field(json_decode($lead['excerpt'] ?? '[]', true) ?: []);
                $lDate    = ! empty($lead['published_at']) ? date('j M Y', strtotime($lead['published_at'])) : ''; ?>
                <!-- Lead story -->
                <a href="<?= esc(locale_url('news/' . $lead['slug'])) ?>" data-gsap="reveal"
                   class="group isolate mt-8 grid overflow-hidden rounded-3xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/40 md:grid-cols-2">
                    <?php if (! empty($lead['image'])): ?>
                        <span class="relative block aspect-[16/10] overflow-hidden md:aspect-auto md:min-h-[20rem]">
                            <img src="<?= esc(media_src($lead['image']), 'attr') ?>" alt="<?= esc($lTitle, 'attr') ?>"
                                 class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                        </span>
                    <?php endif; ?>
                    <span class="flex flex-col justify-center p-8 lg:p-12">
                        <span class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-white/45">
                            <?php if (! empty($catNames[(int) ($lead['category_id'] ?? 0)])): ?>
                                <span class="text-brand-red"><?= esc($catNames[(int) $lead['category_id']]) ?></span>
                                <span aria-hidden="true">·</span>
                            <?php endif; ?>
                            <?= esc($lDate) ?>
                        </span>
                        <span class="mt-4 text-2xl font-bold leading-tight sm:text-3xl"><?= esc($lTitle) ?></span>
                        <?php if ($lExcerpt !== ''): ?><span class="mt-3 leading-relaxed text-white/60"><?= esc($lExcerpt) ?></span><?php endif; ?>
                        <span class="mt-6 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-brand-red">
                            <?= esc(lang('Site.news.read')) ?>
                            <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </span>
                </a>
            <?php endif; ?>

            <!-- Article grid -->
            <?php if ($grid !== []): ?>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-gsap="reveal">
                    <?php foreach ($grid as $p): ?>
                        <?= view('Modules\News\Views\partials\card', [
                            'post'  => $p,
                            'label' => $catNames[(int) ($p['category_id'] ?? 0)] ?? '',
                        ]) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($pageCount > 1): ?>
                <nav class="mt-12 flex items-center justify-center gap-2" aria-label="Pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= esc($newsUrl($currentPage - 1)) ?>" class="btn-ghost !px-4 !py-2 text-xs"><?= esc(lang('Site.news.newer')) ?></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                        <a href="<?= esc($newsUrl($i)) ?>"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm <?= $i === $currentPage ? 'bg-brand-red font-semibold text-white' : 'border border-white/15 text-white/60 hover:border-brand-red/50 hover:text-white' ?>"
                           <?= $i === $currentPage ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($currentPage < $pageCount): ?>
                        <a href="<?= esc($newsUrl($currentPage + 1)) ?>" class="btn-ghost !px-4 !py-2 text-xs"><?= esc(lang('Site.news.older')) ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
