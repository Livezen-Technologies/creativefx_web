<?php
/**
 * The home page hero: a single rounded panel, inset from the page edges, with
 * the Authority's photography sliding behind the words.
 *
 * Two decisions worth keeping:
 *
 * 1. The words never sit on bare photograph. A scrim runs from an opaque
 *    tea-green at the reading edge out to nothing, so the text keeps its
 *    measured contrast whatever the CMT uploads next — including a photograph
 *    that is mostly bright sky. A hero that passes AA only for today's picture
 *    is not a hero that passes AA.
 *
 * 2. With no photographs at all the panel is not broken, it is plain: the same
 *    green ground, the emblem as a watermark, no controls. The Authority can
 *    launch before the photography is signed off, which is the order these
 *    things actually happen in.
 *
 * @var array $heroSlides ['src','webp','alt','width','height'] each
 */
$slides = array_values(array_filter($heroSlides ?? []));
$many   = count($slides) > 1;
?>
<section class="container-x pt-6 sm:pt-8" aria-labelledby="hero-heading">
    <div class="hero-box <?= $slides === [] ? 'hero-box--bare' : '' ?>">

        <?php if ($slides !== []): ?>
            <?php // The slider is decoration behind the page's own heading, so it
                  // is hidden from assistive technology and kept out of the tab
                  // order. A screen reader gets the H1 and the search box, which
                  // is the content; it does not get five photographs of tea. ?>
            <div class="hero-slider" data-hero-slider aria-hidden="true">
                <div class="swiper-wrapper">
                    <?php foreach ($slides as $i => $slide): ?>
                        <div class="swiper-slide">
                            <picture>
                                <?php if ($slide['webp'] !== null): ?>
                                    <source srcset="<?= esc($slide['webp'], 'attr') ?>" type="image/webp">
                                <?php endif; ?>
                                <?php // The first slide is the largest thing above the
                                      // fold, so it is eager and fetch-priority high;
                                      // the rest wait, because a visitor who never sees
                                      // slide four should not pay for it. ?>
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

        <div class="hero-scrim" aria-hidden="true"></div>

        <div class="hero-body">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-hero-gold">
                <?= esc(setting('parent_org', '', 'general')) ?>
            </p>

            <?php // The Authority's name in the language the reader chose. The
                  // site_name setting is the legal English name — it belongs in
                  // the browser tab, in email and in the copyright line — but a
                  // Tamil reader arriving at a Tamil page should not be greeted
                  // by it in English. The translated form is a language string,
                  // so it is editable in the Translation Manager. ?>
            <h1 id="hero-heading" class="mt-4 max-w-3xl text-3xl font-bold leading-tight text-hero-ink sm:text-5xl">
                <?= esc(lang('Site.home.hero')) ?>
            </h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-hero-muted">
                <?= esc(lang('Site.home.lede')) ?>
            </p>

            <?php // Search is a primary navigation route on an information
                  // portal, not a utility tucked in the header. Clause 3.12's
                  // search, on the first screen. ?>
            <form method="get" action="<?= esc(locale_url('search')) ?>" role="search"
                  class="mt-8 flex max-w-2xl flex-wrap gap-3">
                <label class="min-w-[14rem] flex-1">
                    <span class="sr-only"><?= esc(lang('Site.search.label')) ?></span>
                    <input type="search" name="q" class="field field-on-hero"
                           placeholder="<?= esc(lang('Site.search.placeholder'), 'attr') ?>">
                </label>
                <button type="submit" class="btn-hero"><?= esc(lang('Site.search.button')) ?></button>
            </form>

            <ul class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm text-hero-muted">
                <li><?= esc(lang('Site.search.popular')) ?></li>
                <?php foreach ([
                    'replanting-subsidy'        => 'Site.nav.replanting',
                    'fertilizer-subsidy'        => 'Site.nav.fertilizer',
                    'smallholder-registration'  => 'Site.nav.registration',
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

        <?php if ($many): ?>
            <?php // Controls last in the source, so the keyboard reaches the
                  // search box before the slideshow. They are real buttons with
                  // real labels: a slideshow a keyboard cannot stop is a
                  // WCAG 2.2.2 failure, not a styling preference. ?>
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
    </div>
</section>
