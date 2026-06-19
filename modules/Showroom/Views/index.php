<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
$accent = static fn ($hex) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $hex) ? $hex : '#CF2030';
?>
<?= $this->section('content') ?>
<section class="relative overflow-hidden">
    <div class="hero-aurora absolute inset-0 -z-20"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-black/40 to-brand-black"></div>
    <div class="container-x flex min-h-[42vh] flex-col justify-end pb-12 pt-36">
        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red" data-gsap="reveal">Virtual Showroom</p>
        <h1 class="max-w-3xl text-4xl font-bold sm:text-6xl" data-gsap="reveal">Step inside our collections</h1>
        <p class="mt-4 max-w-xl text-white/70" data-gsap="reveal">Explore each category in an interactive 3D space — click any piece for details and inquiries.</p>
    </div>
</section>

<section class="bg-brand-black py-12" x-data="{ q: '' }">
    <div class="container-x">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <input type="search" x-model="q" placeholder="Search categories…"
                   class="w-full max-w-xs rounded-full border border-white/15 bg-black/40 px-4 py-2 text-sm focus:border-brand-red focus:outline-none">
            <a href="#" class="text-sm text-white/60">
                <span class="text-brand-red">♥</span> <span x-data x-text="$store.wishlist.count">0</span> saved
            </a>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            <?php foreach ($categories as $cat): $hex = $accent($cat['background']); $name = t_field($cat['name']); ?>
                <a href="<?= esc(locale_url('showroom/' . $cat['slug'])) ?>"
                   x-show="q === '' || <?= esc(json_encode(mb_strtolower($name)), 'attr') ?>.includes(q.toLowerCase())"
                   class="group relative flex h-44 flex-col justify-end overflow-hidden rounded-2xl border border-white/10 p-6 transition hover:border-white/30"
                   style="background: linear-gradient(155deg, <?= esc($hex, 'attr') ?>33, #0b0b0c 72%);">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.25em]" style="color: <?= esc($hex, 'attr') ?>"><?= esc($cat['theme']) ?></span>
                    <h3 class="mt-1 text-xl font-semibold leading-tight"><?= esc($name) ?></h3>
                    <span class="mt-2 text-xs text-white/50 transition group-hover:text-white">Enter showroom →</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
