<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
$hex = static fn ($v, $fallback = '#FFC107') => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $v) ? $v : $fallback;
$firstSlug = $categories[0]['slug'] ?? '';
?>
<?= $this->section('content') ?>
<div class="on-dark">

<!-- ===================== LOBBY HERO ===================== -->
<section class="relative overflow-hidden">
    <div class="hero-aurora absolute inset-0 -z-20"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-black/55 via-black/30 to-brand-black"></div>
    <div class="container-x flex min-h-[52vh] flex-col justify-end pb-12 pt-36">
        <p class="eyebrow mb-5" data-gsap="reveal"><?= esc(lang('Site.showroom.lobby_eyebrow')) ?></p>
        <h1 class="max-w-3xl text-4xl font-bold leading-[1.05] sm:text-6xl" data-gsap="reveal"><?= esc(lang('Site.showroom.lobby_title')) ?></h1>
        <p class="mt-5 max-w-xl text-lg text-white/70" data-gsap="reveal"><?= esc(lang('Site.showroom.lobby_intro')) ?></p>
        <div class="mt-8 flex flex-wrap items-center gap-4" data-gsap="reveal">
            <?php if ($firstSlug !== ''): ?>
                <a href="<?= esc(locale_url('showroom/' . $firstSlug)) ?>" class="btn-brand btn-lg group">
                    <?= esc(lang('Site.showroom.guided')) ?>
                    <svg class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            <?php endif; ?>
            <span class="text-sm text-white/60"><span class="text-brand-red">♥</span> <span x-data x-text="$store.wishlist.count">0</span> <?= esc(lang('Site.showroom.saved')) ?></span>
        </div>
    </div>
</section>

<!-- ===================== CATEGORY PORTALS ===================== -->
<section class="bg-brand-black py-12" x-data="{ q: '' }">
    <div class="container-x">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <input type="search" x-model="q" placeholder="<?= esc(lang('Site.showroom.search'), 'attr') ?>"
                   class="w-full max-w-xs rounded-full border border-white/15 bg-black/40 px-4 py-2 text-sm focus:border-brand-red focus:outline-none">
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($categories as $cat):
                $palette   = json_decode($cat['palette'] ?? '[]', true) ?: [$hex($cat['background'])];
                $c0        = $hex($palette[0] ?? $cat['background']);
                $c1        = $hex($palette[1] ?? $c0);
                $name      = t_field($cat['name']);
                $themeName = t_field($cat['theme_name'] ?? []) ?: $cat['theme'];
                $tagline   = t_field($cat['tagline'] ?? []);
            ?>
                <a href="<?= esc(locale_url('showroom/' . $cat['slug'])) ?>"
                   x-show="q === '' || <?= esc(json_encode(mb_strtolower($name . ' ' . $themeName)), 'attr') ?>.includes(q.toLowerCase())"
                   class="group relative isolate flex min-h-[15rem] flex-col justify-end overflow-hidden rounded-2xl border border-white/10 p-7 transition hover:border-white/30 hover:shadow-2xl hover:shadow-black/40"
                   style="background: linear-gradient(150deg, <?= esc($c0, 'attr') ?>40, <?= esc($c1, 'attr') ?>22 45%, #0b0b0c 82%);">
                    <?php if (! empty($cat['image'])): ?>
                        <!-- Real category photo (company portfolio) with a legibility scrim -->
                        <img src="<?= esc($cat['image']) ?>" alt="" loading="lazy"
                             class="absolute inset-0 -z-20 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-[#0b0b0c] via-[#0b0b0c]/55 to-transparent"></div>
                    <?php endif; ?>
                    <!-- palette swatches -->
                    <div class="mb-auto flex gap-1.5">
                        <?php foreach (array_slice($palette, 0, 3) as $pc): ?>
                            <span class="h-4 w-4 rounded-full ring-1 ring-white/20" style="background: <?= esc($hex($pc), 'attr') ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <span class="mt-6 text-[10px] font-semibold uppercase tracking-[0.28em]" style="color: <?= esc($c0, 'attr') ?>"><?= esc($themeName) ?></span>
                    <h3 class="mt-1 text-2xl font-semibold leading-tight"><?= esc($name) ?></h3>
                    <?php if ($tagline !== ''): ?>
                        <p class="mt-2 line-clamp-2 text-sm text-white/55"><?= esc($tagline) ?></p>
                    <?php endif; ?>
                    <span class="mt-4 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-white/60 transition group-hover:text-white">
                        <?= esc(lang('Site.showroom.enter')) ?>
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== SUSTAINABILITY EXPERIENCE CENTER ===================== -->
<section class="relative overflow-hidden border-t border-white/10 bg-brand-black py-20">
    <div class="hero-red-glow absolute inset-0 -z-10 opacity-60"></div>
    <div class="container-x grid gap-8 lg:grid-cols-2 lg:items-center">
        <div data-gsap="reveal">
            <p class="eyebrow"><?= esc(lang('Site.nav.impact')) ?></p>
            <h2 class="mt-5 text-3xl font-bold sm:text-4xl"><?= esc(lang('Site.showroom.sustain_title')) ?></h2>
            <p class="mt-4 max-w-lg text-white/65"><?= esc(lang('Site.showroom.sustain_text')) ?></p>
        </div>
        <div class="flex lg:justify-end" data-gsap="reveal">
            <a href="<?= esc(locale_url('impact')) ?>" class="btn-brand btn-lg"><?= esc(lang('Site.home.impact.cta')) ?></a>
        </div>
    </div>
</section>
</div>
<?= $this->endSection() ?>
