<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$catNames = [];
foreach ($categories as $c) {
    $catNames[(int) $c['id']] = t_field(json_decode($c['name'] ?? '[]', true) ?: []);
}

// Listing URL preserving active filters.
$listUrl = static function (array $override = []) use ($activeCategory, $activeLabel): string {
    $params = [
        'category' => $activeCategory['slug'] ?? null,
        'label'    => $activeLabel ?: null,
        'page'     => null,
    ];
    foreach ($override as $k => $v) {
        $params[$k] = $v;
    }
    if (isset($params['page']) && (int) $params['page'] <= 1) {
        $params['page'] = null;
    }
    $qs = http_build_query(array_filter($params, static fn ($v) => $v !== null && $v !== ''));
    return locale_url('products') . ($qs !== '' ? '?' . $qs : '');
};

$labels = ['featured', 'new', 'bestseller', 'popular'];
?>
<?= $this->section('content') ?>

<!-- Catalog hero -->
<section class="relative overflow-hidden">
    <!-- Photographic hero, dressed with the same washes as the CMS page heroes
         so this band reads like the rest of the site instead of a flat panel. -->
    <img src="/media/magiccorn/6.jpg" alt="" aria-hidden="true"
         class="hero-media absolute inset-0 -z-30 h-full w-full object-cover">
    <div class="hero-wash-side--page absolute inset-0 -z-20"></div>
    <div class="hero-wash-foot--page absolute inset-0 -z-20"></div>
    <div class="hero-red-glow absolute inset-0 -z-10"></div>
    <div class="container-x flex min-h-[34vh] flex-col justify-end pb-10 pt-36">
        <p class="text-xs font-semibold uppercase tracking-widest text-brand-red" data-gsap="reveal"><?= esc(lang('Site.products.eyebrow')) ?></p>
        <h1 class="mt-3 max-w-3xl text-4xl font-bold leading-[1.05] sm:text-5xl" data-gsap="reveal"><?= esc(lang('Site.products.title')) ?></h1>
        <p class="mt-4 max-w-2xl leading-relaxed text-white/60" data-gsap="reveal"><?= esc(lang('Site.products.intro')) ?></p>
    </div>
</section>

<section class="bg-brand-black pb-20">
    <div class="container-x">
        <?php $chip = static fn (bool $on): string => $on
            ? 'rounded-full bg-brand-red px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white'
            : 'rounded-full border border-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/60 transition hover:border-brand-red/50 hover:text-white'; ?>

        <!-- Category filter -->
        <nav class="flex flex-wrap gap-2 border-b border-white/10 py-6" aria-label="Product categories">
            <a href="<?= esc($listUrl(['category' => null])) ?>" class="<?= $chip($activeCategory === null) ?>"><?= esc(lang('Site.products.all')) ?></a>
            <?php foreach ($categories as $c): ?>
                <a href="<?= esc($listUrl(['category' => $c['slug']])) ?>"
                   class="<?= $chip(($activeCategory['slug'] ?? '') === $c['slug']) ?>"><?= esc($catNames[(int) $c['id']]) ?></a>
            <?php endforeach; ?>
        </nav>

        <!-- Label filter -->
        <div class="flex flex-wrap items-center gap-2 py-4">
            <span class="text-[11px] font-semibold uppercase tracking-widest text-white/40"><?= esc(lang('Site.products.filter')) ?></span>
            <a href="<?= esc($listUrl(['label' => null])) ?>" class="<?= $chip($activeLabel === '') ?>"><?= esc(lang('Site.products.all')) ?></a>
            <?php foreach ($labels as $l): ?>
                <a href="<?= esc($listUrl(['label' => $l])) ?>" class="<?= $chip($activeLabel === $l) ?>"><?= esc(lang('Site.products.label_' . $l)) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($products === []): ?>
            <p class="py-20 text-center text-white/50"><?= esc(lang('Site.products.none')) ?></p>
        <?php else: ?>
            <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 xl:grid-cols-4" data-gsap="reveal">
                <?php foreach ($products as $p): ?>
                    <?= view('Modules\Catalog\Views\partials\card', [
                        'product' => $p,
                        'label'   => $catNames[(int) ($p['category_id'] ?? 0)] ?? '',
                    ]) ?>
                <?php endforeach; ?>
            </div>

            <?php if ($pageCount > 1): ?>
                <nav class="mt-12 flex items-center justify-center gap-2" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                        <a href="<?= esc($listUrl(['page' => $i])) ?>"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm <?= $i === $currentPage ? 'bg-brand-red font-semibold text-white' : 'border border-white/15 text-white/60 hover:border-brand-red/50 hover:text-white' ?>"
                           <?= $i === $currentPage ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
