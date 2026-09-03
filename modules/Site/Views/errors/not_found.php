<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>

<?= $this->section('content') ?>

<?php // The mist scene the rest of the site uses, so a wrong URL still lands
      // somewhere that looks like the hotel rather than on a plain error page. ?>
<section class="mist-scene on-dark relative flex min-h-screen items-center overflow-hidden">
    <?= view('Modules\\Core\\Views\\partials\\mist_scene', [
        'image' => '/media/giantforests/Sinharaja-Tracking-2.jpg',
        'alt'   => '',
    ]) ?>
    <div class="hero-wash-pool absolute inset-0 -z-20"></div>
    <div class="hero-wash-foot absolute inset-0 -z-20"></div>

    <div class="container-x relative w-full py-32 text-center">
        <p class="eyebrow mb-6 justify-center"><?= esc(lang('Site.notfound.eyebrow')) ?></p>

        <p class="font-sans text-7xl font-bold leading-none tracking-[-0.03em] text-white/25 sm:text-8xl"
           aria-hidden="true">404</p>

        <h1 class="mx-auto mt-6 max-w-3xl font-sans text-3xl font-bold leading-[1.1] tracking-[-0.02em] sm:text-5xl">
            <?= esc(lang('Site.notfound.title')) ?>
        </h1>

        <p class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-white/90">
            <?= esc(lang('Site.notfound.body')) ?>
        </p>

        <div class="mt-10 flex flex-wrap items-center justify-center gap-6">
            <a href="<?= esc(locale_url('')) ?>" class="btn-brand btn-lg group">
                <?= esc(lang('Site.notfound.home')) ?>
                <svg class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <?php // Same dialog as every other Book Now on the site; the href is
                  // the contact page so it still goes somewhere without JS. ?>
            <a href="<?= esc(locale_url('contact')) ?>" x-data @click.prevent="$dispatch('booking-open')"
               class="inline-flex items-center gap-3 text-sm font-semibold uppercase tracking-widest text-white/85 transition hover:text-white">
                <?= esc(lang('Site.home.hero.primary')) ?>
            </a>
        </div>

        <?php // Where they were probably trying to go. The same list the header
              // and footer are built from, so a new page appears here too. ?>
        <div class="mt-16">
            <p class="text-[11px] uppercase tracking-[0.3em] text-white/60">
                <?= esc(lang('Site.notfound.try')) ?>
            </p>
            <ul class="mx-auto mt-5 flex max-w-3xl flex-wrap items-center justify-center gap-x-6 gap-y-3">
                <?php foreach (site_nav('header') as $item): ?>
                    <li>
                        <a href="<?= esc($item['url'], 'attr') ?>"
                           class="text-sm font-medium uppercase tracking-wider text-white/75 underline-offset-4 transition hover:text-white hover:underline">
                            <?= esc($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
