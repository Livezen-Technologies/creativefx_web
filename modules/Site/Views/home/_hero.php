<?php
/**
 * The home page hero: a full-bleed photograph with the Authority's words on a
 * translucent card over it, and the priority notices beside them.
 *
 * The photograph runs edge to edge and is meant to be seen, so the veil over it
 * is light — nowhere near enough to carry text. The contrast comes from the
 * cards instead, which is what lets the picture stay bright: a panel-wide scrim
 * heavy enough for AA is a panel-wide scrim you cannot see the photograph
 * through. Card opacity is not a taste setting; it is the number
 * scripts/check-hero-contrast.mjs measures against a deliberately bright frame.
 *
 * Notices sit in the hero rather than in a band above it (Clause 3.9 B.IV still
 * wants them first): same fold, same glance, and they keep their own warm colour
 * because an alert that looks like the rest of the site is not an alert.
 *
 * With no photographs the panel is not broken, it is plain — the green ground
 * and the emblem — so the Authority can launch before the photography is signed
 * off, which is the order these things actually happen in.
 *
 * @var array $heroSlides ['src','webp','alt','width','height'] each
 * @var array $notices
 */
$slides  = array_values(array_filter($heroSlides ?? []));
$notices = $notices ?? [];
$many    = count($slides) > 1;
?>
<section class="hero-full <?= $slides === [] ? 'hero-full--bare' : '' ?>" aria-labelledby="hero-heading">

    <?php if ($slides !== []): ?>
        <?php // Decoration behind the page's own heading: hidden from assistive
              // technology and out of the tab order. A screen reader gets the H1
              // and the search box, not six photographs of tea. ?>
        <div class="hero-slider" data-hero-slider aria-hidden="true">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $i => $slide): ?>
                    <div class="swiper-slide">
                        <picture>
                            <?php if ($slide['webp'] !== null): ?>
                                <source srcset="<?= esc($slide['webp'], 'attr') ?>" type="image/webp">
                            <?php endif; ?>
                            <?php // The first slide is the largest thing above the
                                  // fold, so it is eager and high priority; the rest
                                  // wait, because a visitor who never reaches slide
                                  // four should not pay for it. ?>
                            <img src="<?= esc($slide['src'], 'attr') ?>"
                                 alt=""
                                 <?php if ($slide['width'] !== null && $slide['height'] !== null): ?>
                                     width="<?= (int) $slide['width'] ?>" height="<?= (int) $slide['height'] ?>"
                                 <?php endif; ?>
                                 <?= $i === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy" decoding="async"' ?>>
                        </picture>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="hero-veil" aria-hidden="true"></div>

    <div class="container-x hero-grid">

        <div class="hero-card">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-hero-gold">
                <?= esc(setting('parent_org', '', 'general')) ?>
            </p>

            <?php // The Authority's name in the language the reader chose. The
                  // site_name setting is the legal English name — it belongs in
                  // the browser tab, in email and in the copyright line — but a
                  // Tamil reader arriving at a Tamil page should not be greeted
                  // by it in English. The translated form is a language string,
                  // so it is editable in the Translation Manager. ?>
            <h1 id="hero-heading" class="mt-4 text-3xl font-bold leading-tight text-hero-ink sm:text-4xl xl:text-5xl">
                <?= esc(lang('Site.home.hero')) ?>
            </h1>
            <p class="mt-4 text-base leading-relaxed text-hero-muted sm:text-lg">
                <?= esc(lang('Site.home.lede')) ?>
            </p>

            <?php // Search is a primary navigation route on an information
                  // portal, not a utility tucked in the header. Clause 3.12's
                  // search, on the first screen. ?>
            <form method="get" action="<?= esc(locale_url('search')) ?>" role="search"
                  class="mt-7 flex flex-wrap gap-3">
                <label class="min-w-[12rem] flex-1">
                    <span class="sr-only"><?= esc(lang('Site.search.label')) ?></span>
                    <input type="search" name="q" class="field field-on-hero"
                           placeholder="<?= esc(lang('Site.search.placeholder'), 'attr') ?>">
                </label>
                <button type="submit" class="btn-hero"><?= esc(lang('Site.search.button')) ?></button>
            </form>

            <ul class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-hero-muted">
                <li><?= esc(lang('Site.search.popular')) ?></li>
                <?php foreach ([
                    'replanting-subsidy'       => 'Site.nav.replanting',
                    'fertilizer-subsidy'       => 'Site.nav.fertilizer',
                    'smallholder-registration' => 'Site.nav.registration',
                ] as $slug => $key): ?>
                    <li>
                        <a href="<?= esc(locale_url('services/' . $slug)) ?>"
                           class="underline decoration-current/40 underline-offset-4 transition hover:text-hero-gold">
                            <?= esc(lang($key)) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php // ── B.IV Priority notices ────────────────────────────────────
              // Beside the masthead, not above it. Still the first thing on the
              // page, still its own colour, and still a live region so a screen
              // reader announces one that appears without going looking for it.
              // Renders nothing at all when there are none, and the masthead
              // takes the width back. ?>
        <?php if ($notices !== []): ?>
            <aside class="hero-notices" aria-label="<?= esc(lang('Site.home.notices'), 'attr') ?>" aria-live="polite">
                <h2 class="hero-notices-title"><?= esc(lang('Site.home.notices')) ?></h2>
                <ul class="hero-notices-list" role="list">
                    <?php foreach (array_slice($notices, 0, 3) as $notice):
                        $url    = trim((string) ($notice['url'] ?? ''));
                        $href   = $url === '' ? '' : (preg_match('~^(https?:|/)~i', $url) ? $url : locale_url($url));
                        $urgent = ($notice['severity'] ?? '') === 'urgent';
                        $body   = t_field($notice['body'] ?? '');
                    ?>
                        <li>
                            <span class="hero-notice-tag <?= $urgent ? 'hero-notice-tag--urgent' : '' ?>">
                                <?= esc($urgent ? lang('Site.home.urgent') : lang('Site.home.notice')) ?>
                            </span>
                            <p class="hero-notice-title">
                                <?php if ($href !== ''): ?>
                                    <a href="<?= esc($href) ?>"><?= esc(t_field($notice['title'])) ?></a>
                                <?php else: ?>
                                    <?= esc(t_field($notice['title'])) ?>
                                <?php endif; ?>
                            </p>
                            <?php if ($body !== ''): ?>
                                <p class="hero-notice-body"><?= esc($body) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        <?php endif; ?>
    </div>

    <?php if ($many): ?>
        <?php // Last in the source, so the keyboard reaches the search box and
              // the notices before the slideshow. Real buttons with real labels:
              // a slideshow a keyboard cannot stop is a WCAG 2.2.2 failure, not
              // a styling preference. ?>
        <div class="hero-controls" data-hero-controls>
            <button type="button" class="hero-ctl" data-hero-prev
                    aria-label="<?= esc(lang('Site.slider.previous'), 'attr') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <button type="button" class="hero-ctl hero-ctl--play" data-hero-toggle
                    aria-label="<?= esc(lang('Site.slider.pause'), 'attr') ?>"
                    data-label-play="<?= esc(lang('Site.slider.play'), 'attr') ?>"
                    data-label-pause="<?= esc(lang('Site.slider.pause'), 'attr') ?>">
                <svg class="hero-ctl-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5v14M15 5v14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <svg class="hero-ctl-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5l11 7-11 7z" fill="currentColor"/></svg>
            </button>

            <button type="button" class="hero-ctl" data-hero-next
                    aria-label="<?= esc(lang('Site.slider.next'), 'attr') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div class="hero-dots" data-hero-dots
                 data-label-slide="<?= esc(lang('Site.slider.slide'), 'attr') ?>"></div>
        </div>
    <?php endif; ?>
</section>
