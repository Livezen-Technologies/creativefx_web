<?php helper(['url', 'norlanka']); $locale = current_locale();
// Current slug (segment after the locale) for active-state highlighting.
$parts = explode('/', trim(uri_string(), '/'));
$currentSlug = $parts[1] ?? '';

$nav = [
    'accommodation' => lang('Site.nav.accommodation'),
    'dining'        => lang('Site.nav.dining'),
    'things-to-do'  => lang('Site.nav.things_to_do'),
    'gallery'       => lang('Site.nav.gallery'),
    'contact'       => lang('Site.nav.contact'),
];

?>
<header
    x-data="siteHeader()"
    @scroll.window="onScroll()"
    @keydown.escape.window="mobile=false"
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
        <a href="<?= esc(locale_url('')) ?>" class="flex items-center gap-2.5 shrink-0" aria-label="Kukuleganga Giants Forest — home">
            <img src="<?= esc(media_src('/media/giantforests/Kukuleganga-Giants-Forest-Logo-white.png'), 'attr') ?>" alt="Kukuleganga Giants Forest" width="300" height="200" class="h-9 w-auto">
        </a>

        <!-- Desktop nav -->
        <nav class="hidden items-center gap-7 lg:flex" aria-label="Primary">
            <?php foreach ($nav as $slug => $label):
                $active = $slug === $currentSlug; ?>
                <a href="<?= esc(locale_url($slug)) ?>" class="nav-link <?= $active ? 'nav-link-active' : '' ?>"><?= esc($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <!-- Right side -->
        <div class="flex items-center gap-2.5 sm:gap-3">
            <!-- Social accounts. Hidden below sm, where the row would crowd the
                 language switcher out; the mobile menu carries them instead. -->
            <div class="hidden sm:block">
                <?= $this->include('Modules\Core\Views\partials\social_links', ['compact' => true]) ?>
            </div>
            <?= $this->include('Modules\Core\Views\partials\theme_toggle') ?>
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

            <!-- The header's icon row is hidden at this width, so the accounts
                 appear here instead rather than not at all. -->
            <div class="mt-8 flex justify-center sm:hidden">
                <?= $this->include('Modules\Core\Views\partials\social_links', ['compact' => false]) ?>
            </div>
        </nav>
    </div>
</header>
