<?php helper(['url', 'norlanka']); $locale = current_locale(); ?>
<header
    x-data="{ scrolled: false }"
    @scroll.window="scrolled = window.scrollY > 40"
    :class="scrolled ? 'bg-brand-black/90 backdrop-blur shadow-lg shadow-black/30' : 'bg-transparent'"
    class="fixed inset-x-0 top-0 z-50 transition-colors duration-300"
>
    <div class="flex h-20 w-full items-center justify-between px-6 lg:px-10">
        <a href="<?= esc(locale_url('')) ?>" class="flex items-center gap-2">
            <span class="text-2xl font-bold tracking-widest text-white">NOR<span class="text-brand-red">LANKA</span></span>
        </a>

        <nav class="hidden items-center gap-8 lg:flex">
            <?php
            $nav = [
                'our-story'    => lang('Site.nav.story'),
                'our-expertise'=> lang('Site.nav.expertise'),
                'manufacturing'=> lang('Site.nav.manufacturing'),
                'showroom'     => lang('Site.nav.showroom'),
                'impact'       => lang('Site.nav.impact'),
                'careers'      => lang('Site.nav.careers'),
                'contact'      => lang('Site.nav.contact'),
            ];
            foreach ($nav as $slug => $label): ?>
                <a href="<?= esc(locale_url($slug)) ?>"
                   class="text-sm font-medium uppercase tracking-widest text-white/80 transition hover:text-white"><?= esc($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <?= $this->include('Modules\Core\Views\partials\lang_switcher') ?>
    </div>
</header>
