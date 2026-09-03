<?php helper(['url', 'norlanka']); $locale = current_locale();
// Current slug (segment after the locale) for active-state highlighting.
$parts = explode('/', trim(uri_string(), '/'));
$currentSlug = $parts[1] ?? '';

$nav = site_nav();

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

    <?php // justify-between hangs the nav off the logo's width, so it sat left
          // of centre by however much wider the logo is than the controls
          // opposite it. Three explicit columns with the outer two sharing what
          // is left put the nav in the middle of the bar regardless.
          //
          // Only from xl, though. Centring means both margins are as wide as
          // the wider of the two flanks, and between 1024 and 1280 the six nav
          // items plus the controls do not leave that much: forcing it there
          // squeezed the logo's track to zero and the mark vanished. Below xl
          // the row stays the flex it was, which fits. ?>
    <div class="header-bar flex w-full items-center justify-between px-6 transition-all duration-300 lg:px-10 xl:grid xl:grid-cols-[1fr_auto_1fr]"
         :class="scrolled ? 'h-16' : 'h-24'">
        <!-- Logo: official NL monogram + wordmark (static sizes so it renders
             correctly even before/without JS). -->
        <a href="<?= esc(locale_url('')) ?>" class="flex shrink-0 items-center gap-2.5" aria-label="Kukuleganga Giants Forest — home">
            <?php // Half again as tall while the header is at rest over the hero,
                  // where there is room for it; back to its old size the moment
                  // the compact bar takes over, which is sized in CSS so it
                  // still transitions without JavaScript deciding it. ?>
            <?= view('Modules\\Core\\Views\\partials\\logo', ['class' => 'brand-mark w-auto']) ?>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden items-center justify-center gap-4 lg:flex xl:gap-6 2xl:gap-7" aria-label="Primary">
            <?php foreach ($nav as $slug => $label):
                $active = $slug === $currentSlug; ?>
                <a href="<?= esc(locale_url($slug)) ?>" class="nav-link <?= $active ? 'nav-link-active' : '' ?>"><?= esc($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <!-- Right side -->
        <div class="flex items-center justify-end gap-2.5 sm:gap-3">
            <?php // Two accounts, not five: the header row is for the ones a
                  // guest actually messages the hotel on, and five marks beside
                  // the theme switch and the language menu read as a toolbar.
                  // The full set is in the footer and the mobile menu.
                  //
                  // Two also buy back the breakpoint. At five the row had to
                  // wait for 2xl or the nav could not sit in the middle of the
                  // bar; at two it returns at xl, and the only cost is 13px of
                  // centring at exactly 1280 — measured, and about 1% of the
                  // width. Below xl there is genuinely no room, and the mobile
                  // menu carries them. ?>
            <div class="hidden xl:block">
                <?php // view(), not $this->include(): include()'s second argument
                      // is render options, not view data, so every array passed
                      // to it here was quietly discarded. That is why the mobile
                      // menu's icons were the compact size it asked not to have,
                      // and why this row would have kept showing all five. ?>
                <?php // saveData: false, or this data persists on the shared
                      // renderer and the next view() call inherits it — which it
                      // did: the footer's own icon row, which asks for all five,
                      // came back carrying this row's two. ?>
                <?= view('Modules\Core\Views\partials\social_links', [
                    'compact' => true,
                    'only'    => ['Facebook', 'WhatsApp'],
                ], ['saveData' => false]) ?>
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
         :class="scrolled ? 'top-16' : 'top-24'"
         class="fixed inset-0 z-40 bg-brand-black/98 backdrop-blur-xl lg:hidden"
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
                <?= view('Modules\Core\Views\partials\social_links', ['compact' => false], ['saveData' => false]) ?>
            </div>
        </nav>
    </div>
</header>
