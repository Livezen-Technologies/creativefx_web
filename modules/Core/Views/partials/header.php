<?php helper(['url', 'norlanka']); $locale = current_locale();
// Current slug (segment after the locale) for active-state highlighting.
$parts = explode('/', trim(uri_string(), '/'));
$currentSlug = $parts[1] ?? '';

$nav = site_nav('header');

// Header behaviour, all editable under Settings → Header. Defaults match what
// the site did before any of them existed, so an install that has never opened
// the screen behaves exactly as it always has.
$sticky      = setting('sticky', '1', 'header') !== '0';
$showSocials = setting('show_socials', '1', 'header') !== '0';
$ctaLabel    = trim((string) setting('cta_label', '', 'header'));
$ctaUrl      = trim((string) setting('cta_url', '', 'header'));
// "booking" is a word, not an address: it means open the dialog in place.
$ctaIsModal  = strtolower($ctaUrl) === 'booking';

// An item is current if it is the page, or if the open page sits inside its
// dropdown — otherwise a parent goes dim the moment one of its children is
// opened, which reads as having navigated away from it.
$isCurrent = static function (array $item) use ($currentSlug): bool {
    if ($item['slug'] === $currentSlug) {
        return true;
    }
    foreach ($item['children'] as $child) {
        if ($child['slug'] === $currentSlug) {
            return true;
        }
    }
    return false;
};
?>
<header
    x-data="siteHeader()"
    @scroll.window="onScroll()"
    @keydown.escape.window="mobile=false"
    :class="scrolled ? 'is-scrolled' : ''"
    <?php // Not sticky means the bar scrolls away with the page. absolute rather
          // than static so it still sits over the hero rather than pushing it
          // down — the hero is built to have the header on top of it. ?>
    <?php // Follows the page theme: a light bar on a light page, dark on dark.
          // Only the footer is pinned to the dark scope.
          //
          // The exception is a page that opens with a dark band. There the bar
          // is floating over dark ground while it is transparent, so it takes
          // the dark scope for exactly that state and drops it the moment it
          // goes solid — at which point it is sitting on the page's own ground
          // again and the page's own tokens are correct.
          $heroDark = $heroDark ?? false; ?>
    class="site-header <?= $sticky ? 'fixed' : 'absolute' ?> inset-x-0 top-0 z-50<?= $heroDark ? ' on-dark' : '' ?>"
    <?php // Object syntax, not a ternary. A ternary binding makes Alpine own
          // every class it evaluates, so it would strip the `on-dark` that is
          // already in the static attribute above and the bar would be light
          // over the hero for the first frame after hydration. The object form
          // toggles exactly the one class it names. ?>
    <?php if ($heroDark): ?>:class="{ 'on-dark': ! scrolled }"<?php endif; ?>
>
    <?php // The normal state's ground: a soft wash so the bar reads over hero
          // media at the top of the page, fading out as the solid sticky state
          // takes over. Both are in the theme's tokens, so it is a light wash
          // over a light page and a dark one over a dark page.
          //
          // This is only ever seen at the top of the document. Everything below
          // that is the sticky state, which is opaque — the failure this used
          // to cause was the sticky state not engaging, not the wash itself. ?>
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
    <div x-ref="bar"
         class="header-bar flex w-full items-center justify-between px-6 transition-all duration-300 lg:px-10"
         :class="[scrolled ? 'h-16' : 'h-24', fits ? 'xl:grid xl:grid-cols-[1fr_auto_1fr]' : '']">
        <!-- Logo: official NL monogram + wordmark (static sizes so it renders
             correctly even before/without JS). -->
        <a x-ref="logo" href="<?= esc(locale_url('')) ?>" class="flex shrink-0 items-center gap-2.5"
           aria-label="<?= esc(setting('site_name', ''), 'attr') ?>">
            <?php // Half again as tall while the header is at rest over the hero,
                  // where there is room for it; back to its old size the moment
                  // the compact bar takes over, which is sized in CSS so it
                  // still transitions without JavaScript deciding it. ?>
            <?php // Two crops, one shown at a time.
                  //
                  // The full lockup is 216px wide at its resting height. On a
                  // 390px phone the controls beside it are 173px and the bar
                  // has 48px of padding, which is 437px of content in 390px of
                  // screen — and because both flanks are shrink-0 the overflow
                  // landed on the hamburger, clipping the only way into the
                  // navigation on the device that needs it most. The badge
                  // alone is 47px and leaves room to spare. ?>
            <?= view('Modules\\Core\\Views\\partials\\logo', [
                'class'   => 'brand-mark w-auto sm:hidden',
                'variant' => 'mark',
            ], ['saveData' => false]) ?>
            <?= view('Modules\\Core\\Views\\partials\\logo', [
                'class' => 'brand-mark hidden w-auto sm:block',
            ], ['saveData' => false]) ?>
        </a>

        <!-- Desktop nav -->
        <?php // `fits` is measured, not assumed — see siteHeader.js. When the
              // menu is too wide for the bar in this language, at this width,
              // with these labels, it is hidden and the drawer button appears
              // in its place. ?>
        <nav x-ref="nav" :class="fits ? 'lg:flex' : ''"
             class="primary-nav hidden items-center justify-center gap-4 whitespace-nowrap 2xl:gap-6"
             aria-label="Primary">
            <?php foreach ($nav as $item):
                $active = $isCurrent($item); ?>

                <?php if ($item['children'] === []): ?>
                    <a href="<?= esc($item['url'], 'attr') ?>"<?= $item['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>
                       class="nav-link <?= $active ? 'nav-link-active' : '' ?>"><?= esc($item['label']) ?></a>
                <?php else: ?>
                    <?php // A dropdown opens on hover for a mouse and on click,
                          // Enter or Space for everything else — hover alone is
                          // unreachable by keyboard, and click alone feels broken
                          // with a pointer. Escape closes it and puts focus back
                          // on the trigger.
                          //
                          // focusin belongs on the panel, not on this wrapper.
                          // On the wrapper it fired when the trigger itself
                          // received focus, so tabbing to the button opened the
                          // menu and the Enter that should have opened it closed
                          // it again — the one route a keyboard user has. Here
                          // it only keeps an already-open panel open while focus
                          // moves through its links. ?>
                    <div class="relative" x-data="{ open: false }"
                         @mouseenter="open = true" @mouseleave="open = false"
                         @focusout="if (! $el.contains($event.relatedTarget)) open = false"
                         @keydown.escape.stop="open = false; $refs.trigger.focus()">
                        <?php // @click opens; it does not toggle.
                              //
                              // mouseenter fires before click on any pointer
                              // device, so by the time a mouse user's click
                              // lands the panel is already open and a toggle
                              // shut it again — the menu appeared on hover and
                              // vanished the instant they clicked the thing
                              // they were aiming at. Opening twice is harmless;
                              // Escape, mouseleave and focusout all still close
                              // it, and Space still toggles for the keyboard,
                              // where there is no hover to collide with. ?>
                        <button type="button" x-ref="trigger" @click="open = true" @keydown.space.prevent="open = ! open"
                                :aria-expanded="open ? 'true' : 'false'" aria-haspopup="true"
                                class="nav-link inline-flex items-center gap-1.5 <?= $active ? 'nav-link-active' : '' ?>">
                            <?= esc($item['label']) ?>
                            <svg class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                             @focusin="open = true"
                             class="absolute left-1/2 top-full z-50 min-w-[13rem] -translate-x-1/2 pt-3">
                            <ul class="overflow-hidden rounded-xl border border-white/10 bg-brand-black/95 py-2 shadow-2xl backdrop-blur-xl">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li>
                                        <a href="<?= esc($child['url'], 'attr') ?>"<?= $child['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>
                                           class="block px-4 py-2.5 text-sm text-white/80 transition hover:bg-white/10 hover:text-white <?= $child['slug'] === $currentSlug ? 'text-white' : '' ?>">
                                            <?= esc($child['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- Right side -->
        <div x-ref="controls" class="flex shrink-0 items-center justify-end gap-2.5 sm:gap-3">
            <?php // Rides with the desktop nav: if the menu did not fit, five
                  // more pixels of icons certainly do not. The mobile drawer
                  // carries the full set. ?>
            <div class="hidden shrink-0"<?= $showSocials ? ' :class="fits ? \'xl:block\' : \'\'"' : '' ?>>
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
                    'only'    => ['Facebook', 'YouTube'],
                ], ['saveData' => false]) ?>
            </div>
            <?php if ($ctaLabel !== ''): ?>
                <?php // A booking button in the bar, off until somebody gives it a
                      // label. It appears from lg, where the row has the width for
                      // it; below that the mobile menu and the hero both already
                      // carry the same call to action. ?>
                <a href="<?= esc($ctaIsModal ? locale_url('contact') : menu_link($ctaUrl), 'attr') ?>"
                   <?= $ctaIsModal ? 'x-data @click.prevent="$dispatch(\'booking-open\')"' : '' ?>
                   class="hidden rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-widest transition hover:opacity-90 lg:inline-flex"
                   style="background: rgb(var(--accent-fill)); color: rgb(var(--accent-ink))">
                    <?= esc($ctaLabel) ?>
                </a>
            <?php endif; ?>
            <?= $this->include('Modules\Core\Views\partials\theme_toggle') ?>
            <?= $this->include('Modules\Core\Views\partials\lang_switcher') ?>
            <!-- Mobile toggle -->
            <button type="button" @click="mobile=!mobile" :aria-expanded="mobile"
                    :class="fits ? 'lg:hidden' : ''"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-white/15 text-white"
                    aria-label="Toggle menu">
                <svg x-show="!mobile" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="mobile" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    <!-- The drawer.
         Its visibility follows `fits`, exactly like the button that opens it,
         and that is the whole point: the button appears whenever the nav does
         not fit — a measurement, not a breakpoint — while this was hidden by a
         hard `lg:hidden`. Between 1024px and the width the menu actually fits
         at (1440 in English, 1920 in Tamil) that left a hamburger which opened
         a drawer CSS refused to paint. Every link on the site was unreachable
         at the width most laptops run at, and nothing on screen said so.

         One state decides both, so they cannot disagree again. -->
    <div x-show="mobile" x-cloak x-transition.opacity
         :class="[scrolled ? 'top-16' : 'top-24', fits ? 'lg:hidden' : '']"
         <?php // /95, not /98. Tailwind's opacity scale stops at 90, 95, 100 —
               // there is no 98, so the utility was silently dropped and the
               // drawer had no ground at all: a full-screen menu with only a
               // blur behind it, the page showing through every link. It is the
               // worst kind of failure, because the class is right there in the
               // markup and reads as if it works. ?>
         <?php // Lenis takes the wheel for the whole document, so a scroll
               // inside a nested panel never reaches it: the drawer had
               // overflow-y: auto and still would not move. data-lenis-prevent
               // hands wheel events inside this element back to the browser. ?>
         data-lenis-prevent
         class="drawer-panel fixed inset-0 z-40 bg-brand-black/95 backdrop-blur-xl"
         @click.self="mobile=false">
        <nav class="container-x drawer-nav" aria-label="Mobile">
            <?php // No disclosure widgets: a dropdown's children are listed under
                  // their parent. One tap reaches every page instead of two, and
                  // there is no open/closed state to get stuck in.
                  //
                  // Laid out in columns from `sm`, because as one flowing list
                  // this is twenty-two rows — taller than any phone and most
                  // laptops, so the reader had to scroll a menu to find the
                  // thing they opened the menu to find. Each parent and its
                  // children stay together in one cell, so the grouping the
                  // indentation implies is still true when they sit side by
                  // side. ?>
            <div class="drawer-grid">
                <?php foreach ($nav as $item):
                    $active = $isCurrent($item); ?>
                    <div class="drawer-group">
                        <a href="<?= esc($item['url'], 'attr') ?>" @click="mobile=false"<?= $item['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>
                           class="drawer-parent<?= $active ? ' is-current' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                            <span><?= esc($item['label']) ?></span>
                            <svg class="drawer-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>

                        <?php if ($item['children'] !== []): ?>
                            <div class="drawer-children">
                                <?php foreach ($item['children'] as $child): ?>
                                    <a href="<?= esc($child['url'], 'attr') ?>" @click="mobile=false"<?= $child['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>
                                       class="drawer-child<?= $child['slug'] === $currentSlug ? ' is-current' : '' ?>"<?= $child['slug'] === $currentSlug ? ' aria-current="page"' : '' ?>>
                                        <?= esc($child['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- The header's icon row is hidden at this width, so the accounts
                 appear here instead rather than not at all. -->
            <div class="drawer-socials sm:hidden">
                <?= view('Modules\Core\Views\partials\social_links', ['compact' => false], ['saveData' => false]) ?>
            </div>
        </nav>
    </div>
</header>
