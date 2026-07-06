<?php helper(['url', 'norlanka']); $locale = current_locale();
// Current slug (segment after the locale) for active-state highlighting.
$parts = explode('/', trim(uri_string(), '/'));
$currentSlug = $parts[1] ?? '';

$nav = [
    'our-story'     => lang('Site.nav.story'),
    'our-expertise' => lang('Site.nav.expertise'),
    'showroom'      => lang('Site.nav.showroom'),
    'impact'        => lang('Site.nav.impact'),
    'careers'       => lang('Site.nav.careers'),
    'contact'       => lang('Site.nav.contact'),
];

// Mega-menu contents for "Our Expertise" (titles reuse the home capability keys).
$expertiseMega = [
    ['title' => lang('Site.home.cap.apparel_t'),  'text' => lang('Site.mega.apparel_d'),  'icon' => 'M6 3l-2 4 3 2v12h10V9l3-2-2-4-3 2a4 4 0 01-6 0L6 3z'],
    ['title' => lang('Site.home.cap.washing_t'),  'text' => lang('Site.mega.washing_d'),  'icon' => 'M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z'],
    ['title' => lang('Site.home.cap.printing_t'), 'text' => lang('Site.mega.printing_d'), 'icon' => 'M4 20h16M5 16l9-9 3 3-9 9H5v-3zM14 7l3-3 3 3-3 3'],
    ['title' => lang('Site.home.cap.design_t'),   'text' => lang('Site.mega.design_d'),   'icon' => 'M12 20h9M3 20l2-6 11-11 4 4L9 18l-6 2zM14 6l4 4'],
];
?>
<header
    x-data="siteHeader()"
    @scroll.window="onScroll()"
    @keydown.escape.window="mega=false; mobile=false"
    :class="scrolled ? 'is-scrolled' : ''"
    class="site-header <?= ($pageDark ?? false) ? 'on-dark' : '' ?> fixed inset-x-0 top-0 z-50"
>
    <!-- Top shade: a soft light wash that keeps the dark logo/nav legible over
         hero media at the top of the page; fades out once the solid header kicks in. -->
    <div aria-hidden="true"
         class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-b from-brand-black/90 via-brand-black/45 to-transparent transition-opacity duration-300"
         :class="scrolled ? 'opacity-0' : 'opacity-100'"></div>

    <!-- Scroll progress bar -->
    <div class="absolute inset-x-0 top-0 h-0.5 bg-brand-red origin-left" :style="`transform:scaleX(${progress/100})`"></div>

    <div class="header-bar flex w-full items-center justify-between px-6 transition-all duration-300 lg:px-10"
         :class="scrolled ? 'h-16' : 'h-20'">
        <!-- Logo: official NL monogram + wordmark (static sizes so it renders
             correctly even before/without JS). -->
        <a href="<?= esc(locale_url('')) ?>" class="flex items-center gap-2.5 shrink-0" aria-label="Norlanka — home">
            <img src="/media/brand/nl-symbol.png" alt="Norlanka" width="36" height="36" class="h-9 w-auto">
            <span class="font-display text-lg font-semibold uppercase tracking-[0.22em] text-white">Norlanka</span>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden items-center gap-7 lg:flex" aria-label="Primary">
            <?php foreach ($nav as $slug => $label):
                $active = $slug === $currentSlug; ?>
                <?php if ($slug === 'our-expertise'): ?>
                    <div class="relative" @mouseenter="mega=true" @mouseleave="mega=false">
                        <a href="<?= esc(locale_url($slug)) ?>"
                           @focus="mega=true"
                           class="nav-link <?= $active ? 'nav-link-active' : '' ?> inline-flex items-center gap-1"
                           aria-haspopup="true" :aria-expanded="mega">
                            <?= esc($label) ?>
                            <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="mega ? 'rotate-180' : ''" viewBox="0 0 12 12" fill="none"><path d="M3 4.5 6 7.5 9 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <!-- Mega panel -->
                        <div x-show="mega" x-transition.opacity.duration.200ms x-cloak
                             class="mega-panel absolute left-1/2 top-full z-50 w-[40rem] -translate-x-1/2 pt-3">
                            <div class="grid grid-cols-2 gap-2 rounded-2xl border border-white/10 bg-brand-black/95 p-4 shadow-2xl shadow-black/50 backdrop-blur-xl">
                                <?php foreach ($expertiseMega as $m): ?>
                                    <a href="<?= esc(locale_url('our-expertise')) ?>"
                                       class="group flex items-start gap-4 rounded-xl border border-transparent p-4 transition hover:border-brand-red/40 hover:bg-white/[0.04]">
                                        <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-white/10 text-brand-red transition group-hover:border-brand-red/60 group-hover:bg-brand-red/10">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="<?= esc($m['icon'], 'attr') ?>"/></svg>
                                        </span>
                                        <span>
                                            <span class="block text-sm font-semibold text-white"><?= esc($m['title']) ?></span>
                                            <span class="mt-1 block text-xs leading-relaxed text-white/55"><?= esc($m['text']) ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                                <a href="<?= esc(locale_url('our-expertise')) ?>"
                                   class="col-span-2 mt-1 flex items-center justify-between rounded-xl bg-brand-red/10 px-4 py-3 text-sm font-semibold uppercase tracking-widest text-brand-red transition hover:bg-brand-red/20">
                                    <?= esc(lang('Site.mega.see_all')) ?>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= esc(locale_url($slug)) ?>" class="nav-link <?= $active ? 'nav-link-active' : '' ?>"><?= esc($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- Right side -->
        <div class="flex items-center gap-3">
            <?= $this->include('Modules\Core\Views\partials\lang_switcher') ?>
            <!-- Mobile toggle -->
            <button type="button" @click="mobile=!mobile" :aria-expanded="mobile"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/15 text-white lg:hidden"
                    aria-label="Toggle menu">
                <svg x-show="!mobile" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="mobile" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobile" x-cloak x-transition.opacity
         class="fixed inset-0 top-16 z-40 bg-brand-black/98 backdrop-blur-xl lg:hidden"
         @click.self="mobile=false">
        <nav class="container-x flex flex-col gap-1 py-8" aria-label="Mobile">
            <?php foreach ($nav as $slug => $label):
                $active = $slug === $currentSlug; ?>
                <a href="<?= esc(locale_url($slug)) ?>" @click="mobile=false"
                   class="flex items-center justify-between border-b border-white/5 py-4 text-lg font-medium <?= $active ? 'text-brand-red' : 'text-white/85' ?>">
                    <?= esc($label) ?>
                    <svg class="h-4 w-4 text-white/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
