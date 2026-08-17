<?php helper(['url', 'norlanka']); $locale = current_locale();
// Current slug (segment after the locale) for active-state highlighting.
$parts       = explode('/', trim(uri_string(), '/'));
$currentSlug = $parts[1] ?? '';

$nav = [
    'our-story' => lang('Site.nav.story'),
    'portfolio' => lang('Site.nav.portfolio'),
    'contact'   => lang('Site.nav.contact'),
];

// The Services dropdown is driven by the services registry, so adding a service
// in the admin puts it in the menu. Wrapped because the header must render even
// with the table missing (fresh checkout, mid-migration) — it falls back to a
// plain link to the overview page.
$services = [];
try {
    $services = model('Modules\Services\Models\ServiceModel')->published();
} catch (\Throwable $e) {
    $services = [];
}

$serviceIcons = [
    'camera'    => 'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
    'mic'       => 'M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3Z M19 10v2a7 7 0 0 1-14 0v-2 M12 19v4 M8 23h8',
    'broadcast' => 'M4.9 19.1a10 10 0 0 1 0-14.2 M19.1 4.9a10 10 0 0 1 0 14.2 M7.8 16.2a6 6 0 0 1 0-8.4 M16.2 7.8a6 6 0 0 1 0 8.4 M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
    'box'       => 'M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8ZM3.3 7l8.7 5 8.7-5M12 22V12',
    'megaphone' => 'M3 11v2a1 1 0 0 0 1 1h3l7 5V5L7 10H4a1 1 0 0 0-1 1Z M18 8a5 5 0 0 1 0 8',
    'chart'     => 'M3 3v18h18 M7 15l4-5 3 3 5-7',
];

$whatsapp = preg_replace('/[^0-9+]/', '', (string) setting('whatsapp', '', 'contact'));
?>
<header
    x-data="siteHeader()"
    @scroll.window="onScroll()"
    @keydown.escape.window="mobile=false"
    :class="scrolled ? 'is-scrolled' : ''"
    class="site-header <?= ($pageDark ?? false) ? 'on-dark' : '' ?> fixed inset-x-0 top-0 z-50"
>
    <!-- Top shade: a soft dark wash that keeps the logo/nav legible over hero
         media at the top of the page; fades out once the solid header kicks in. -->
    <div aria-hidden="true"
         class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-b from-brand-black/90 via-brand-black/45 to-transparent transition-opacity duration-300"
         :class="scrolled ? 'opacity-0' : 'opacity-100'"></div>

    <!-- Scroll progress bar -->
    <div class="absolute inset-x-0 top-0 h-0.5 bg-brand-red origin-left" :style="`transform:scaleX(${progress/100})`"></div>

    <div class="header-bar flex w-full items-center justify-between gap-4 px-6 transition-all duration-300 lg:px-10"
         :class="scrolled ? 'h-16' : 'h-20'">
        <!-- Logo: lens/play mark + wordmark (static sizes so it renders correctly
             even before/without JS). -->
        <a href="<?= esc(locale_url('')) ?>" class="flex shrink-0 items-center gap-2.5" aria-label="CreativeFX — home">
            <img src="/media/brand/cfx-symbol.svg" alt="" width="36" height="36" class="h-9 w-auto">
            <span class="font-display text-lg font-semibold uppercase tracking-[0.18em] text-white">Creative<span class="text-brand-red">FX</span></span>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden items-center gap-7 xl:flex" aria-label="Primary">
            <a href="<?= esc(locale_url('our-story')) ?>" class="nav-link <?= $currentSlug === 'our-story' ? 'nav-link-active' : '' ?>"><?= esc(lang('Site.nav.story')) ?></a>

            <?php if ($services !== []): ?>
                <!-- Services is a link, not a disclosure button: pointing at it
                     opens the panel, and clicking or pressing Enter goes to the
                     overview page. A toggle-on-click button fights the hover —
                     hover opens the panel and the click immediately closes it
                     again. Keyboard users get the panel on focus and can Tab
                     straight into it; Escape and leaving both close it. -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"
                     @focusin="open = true" @focusout="if (! $el.contains($event.relatedTarget)) open = false"
                     @keydown.escape.stop="open = false" @click.outside="open = false">
                    <a href="<?= esc(locale_url('services')) ?>" :aria-expanded="open ? 'true' : 'false'"
                       aria-haspopup="true" aria-controls="services-menu"
                       class="nav-link inline-flex items-center gap-1.5 <?= $currentSlug === 'services' ? 'nav-link-active' : '' ?>">
                        <?= esc(lang('Site.nav.services')) ?>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>

                    <div id="services-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms
                         class="absolute left-1/2 top-full z-50 w-[36rem] -translate-x-1/2 pt-4">
                        <div class="overflow-hidden rounded-2xl border border-white/10 bg-brand-black/95 p-2 shadow-2xl shadow-black/60 backdrop-blur-xl">
                            <ul class="grid grid-cols-2 gap-1">
                                <?php foreach ($services as $service):
                                    $icon = $serviceIcons[$service['icon'] ?? ''] ?? $serviceIcons['camera']; ?>
                                    <li>
                                        <a href="<?= esc(locale_url('services/' . $service['slug'])) ?>"
                                           class="group flex items-start gap-3 rounded-xl p-3 transition hover:bg-white/[0.06]">
                                            <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-white/10 bg-brand-red/10 text-brand-red transition group-hover:border-brand-red/50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= esc($icon, 'attr') ?>"/></svg>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold text-white"><?= esc(t_field($service['name'] ?? [])) ?></span>
                                                <?php if (! empty($service['tagline'])): ?>
                                                    <span class="mt-0.5 block text-xs leading-relaxed text-white/50"><?= esc(t_field($service['tagline'])) ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?= esc(locale_url('services')) ?>"
                               class="mt-1 flex items-center justify-between rounded-xl border-t border-white/10 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-white/60 transition hover:text-white">
                                <?= esc(lang('Site.nav.all_services')) ?>
                                <svg class="h-3.5 w-3.5 text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= esc(locale_url('services')) ?>" class="nav-link <?= $currentSlug === 'services' ? 'nav-link-active' : '' ?>"><?= esc(lang('Site.nav.services')) ?></a>
            <?php endif; ?>

            <a href="<?= esc(locale_url('portfolio')) ?>" class="nav-link <?= $currentSlug === 'portfolio' ? 'nav-link-active' : '' ?>"><?= esc(lang('Site.nav.portfolio')) ?></a>
            <a href="<?= esc(locale_url('contact')) ?>" class="nav-link <?= $currentSlug === 'contact' ? 'nav-link-active' : '' ?>"><?= esc(lang('Site.nav.contact')) ?></a>
        </nav>

        <!-- Right side -->
        <div class="flex items-center gap-2 sm:gap-2.5">
            <?php if ($whatsapp !== ''): ?>
                <a href="https://wa.me/<?= esc(ltrim($whatsapp, '+'), 'attr') ?>" target="_blank" rel="noopener"
                   class="hidden h-10 w-10 items-center justify-center rounded-lg border border-white/15 text-white/80 transition hover:border-brand-red hover:text-white sm:inline-flex">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.8-.6-3.1-1.3-5.1-4.4-5.3-4.6-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.4.7-.4h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 2 1.1 1 2 1.3 2.3 1.4.3.1.4.1.6-.1l.9-1c.2-.2.3-.2.6-.1l2 .9c.3.1.4.2.5.3 0 .1 0 .6-.1 1.3Z"/></svg>
                    <span class="sr-only"><?= esc(lang('Site.nav.whatsapp')) ?></span>
                </a>
            <?php endif; ?>

            <?= $this->include('Modules\Core\Views\partials\theme_toggle') ?>
            <?= $this->include('Modules\Core\Views\partials\lang_switcher') ?>

            <a href="<?= esc(locale_url('quote')) ?>"
               class="hidden items-center rounded-lg bg-brand-red px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-brand-red/85 sm:inline-flex">
                <?= esc(lang('Site.nav.quote')) ?>
            </a>

            <!-- Mobile toggle -->
            <button type="button" @click="mobile=!mobile" :aria-expanded="mobile ? 'true' : 'false'"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/15 text-white xl:hidden"
                    aria-label="Toggle menu">
                <svg x-show="!mobile" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="mobile" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobile" x-cloak x-transition.opacity
         class="fixed inset-0 top-16 z-40 overflow-y-auto bg-brand-black/98 backdrop-blur-xl xl:hidden"
         @click.self="mobile=false">
        <nav class="container-x flex flex-col gap-1 py-8" aria-label="Mobile">
            <a href="<?= esc(locale_url('our-story')) ?>" @click="mobile=false"
               class="flex items-center justify-between border-b border-white/5 py-4 text-lg font-medium <?= $currentSlug === 'our-story' ? 'text-brand-red' : 'text-white/85' ?>">
                <?= esc(lang('Site.nav.story')) ?>
                <svg class="h-4 w-4 text-white/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>

            <!-- Services collapses in place rather than pushing the visitor to a
                 second screen, so the six services stay one tap away. -->
            <div x-data="{ open: <?= $currentSlug === 'services' ? 'true' : 'false' ?> }" class="border-b border-white/5">
                <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-controls="m-services"
                        class="flex w-full items-center justify-between py-4 text-lg font-medium <?= $currentSlug === 'services' ? 'text-brand-red' : 'text-white/85' ?>">
                    <?= esc(lang('Site.nav.services')) ?>
                    <svg class="h-4 w-4 text-white/30 transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div id="m-services" x-show="open" x-cloak class="pb-3">
                    <?php foreach ($services as $service): ?>
                        <a href="<?= esc(locale_url('services/' . $service['slug'])) ?>" @click="mobile=false"
                           class="block py-2.5 pl-4 text-base text-white/70"><?= esc(t_field($service['name'] ?? [])) ?></a>
                    <?php endforeach; ?>
                    <a href="<?= esc(locale_url('services')) ?>" @click="mobile=false"
                       class="block py-2.5 pl-4 text-xs font-semibold uppercase tracking-widest text-brand-red"><?= esc(lang('Site.nav.all_services')) ?></a>
                </div>
            </div>

            <?php foreach (['portfolio' => lang('Site.nav.portfolio'), 'contact' => lang('Site.nav.contact')] as $slug => $label): ?>
                <a href="<?= esc(locale_url($slug)) ?>" @click="mobile=false"
                   class="flex items-center justify-between border-b border-white/5 py-4 text-lg font-medium <?= $slug === $currentSlug ? 'text-brand-red' : 'text-white/85' ?>">
                    <?= esc($label) ?>
                    <svg class="h-4 w-4 text-white/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            <?php endforeach; ?>

            <div class="mt-6 flex flex-col gap-3">
                <a href="<?= esc(locale_url('quote')) ?>" @click="mobile=false"
                   class="rounded-lg bg-brand-red px-5 py-3.5 text-center text-sm font-semibold uppercase tracking-widest text-white">
                    <?= esc(lang('Site.nav.quote')) ?>
                </a>
                <?php if ($whatsapp !== ''): ?>
                    <a href="https://wa.me/<?= esc(ltrim($whatsapp, '+'), 'attr') ?>" target="_blank" rel="noopener"
                       class="rounded-lg border border-white/15 px-5 py-3.5 text-center text-sm font-semibold uppercase tracking-widest text-white/80">
                        <?= esc(lang('Site.nav.whatsapp')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
