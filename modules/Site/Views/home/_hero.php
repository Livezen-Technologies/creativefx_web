<?php
helper(['norlanka', 'catalog', 'commerce', 'url']);

/**
 * The hero.
 *
 * A photograph behind the words when there is one, and the typographic hero
 * this started as when there is not. The fallback is the point rather than
 * politeness: a training school with no photography of its own is better served
 * by its own words than by a stock picture of somebody else's classroom.
 *
 * **The scrim is a guarantee, not a look.** Text over a photograph is a
 * contrast measurement that has to be redone every time somebody changes the
 * picture — and the picture is changed from the admin, by people who will not
 * run `check-hero-contrast.mjs` afterwards. So the overlay is not tuned to the
 * photograph that happens to be there; it is set dark enough that the worst
 * possible image cannot break the text. `check-hero-contrast.mjs` is run
 * against a pure white hero image for exactly that reason: white is the
 * brightest thing anybody can upload, and if the words clear AA over white they
 * clear it over any photograph.
 *
 * The scrim alone cannot do it, and the arithmetic says why. Half-opacity white
 * text over a white photograph needs roughly a 95% overlay to clear AA — at
 * which point there is no photograph, only a very expensive dark rectangle. So
 * the work is split three ways, and only the first of them is the scrim:
 *
 *   - one left-weighted gradient, darkest under the headline and the longest
 *     lines, lightest on the right where the picture can be seen;
 *   - the quieter greys carry their own weight when there is an image behind
 *     them (white/55 → /85, white/50 → /80, white/60 → /85);
 *   - the dates card stops being a 5% white tint and becomes its own dark
 *     ground, since at wide viewports it reaches into the lit part of the frame.
 *
 * Against a pure white image that took the tightest line from a failing 3.54:1
 * to 8.66:1. None of it applies when there is no photograph, where the original
 * weights read better against flat brand-black — that hero still measures its
 * long-standing 4.67:1, unchanged.
 *
 * The dual call to action is the whole navigation problem of this site in one
 * element: half the audience arrived for Adobe and half for AI, and neither
 * half should have to read past the other to find theirs.
 *
 * @var list<array> $upcoming   the next few sessions, priced
 * @var string      $currency
 * @var list<array> $facts
 * @var array|null  $heroImage  a media_library row, or null
 */
$strip = array_slice(array_values(array_filter(
    $upcoming,
    static fn (array $s): bool => ! empty($s['start_date'])
)), 0, 3);

$heroImage = $heroImage ?? null;
?>
<?php // `isolate` is load-bearing, not tidiness. The photograph and its scrim sit
      // at a negative z-index, and `position: relative` with `z-index: auto`
      // does not make a stacking context — so they were painted in the root
      // context, underneath this section's own opaque `bg-brand-black`, and the
      // photograph never reached the screen. Measured, not assumed: with a pure
      // white hero image promoted and no `isolate`, every sample of the hero
      // backdrop read exactly rgb(14,18,34) — the section's own colour, with
      // nothing of the image showing. With `isolate`, rgb(29,35,56).
      //
      // Applied only when there is a photograph, and that is deliberate. The
      // same stacking rule has been hiding `.hero-aurora` since it was written:
      // isolating unconditionally makes the aurora appear for the first time,
      // which lightens the empty-state hero to rgb(56,63,95) and drops its
      // quietest text to 3.23:1. Revealing a decoration nobody has ever seen is
      // not part of adding a hero image, so the no-photograph hero is left
      // exactly as it renders today. The dead aurora is worth its own change. ?>
<section class="site-hero on-dark relative <?= $heroImage ? 'isolate' : '' ?> overflow-hidden bg-brand-black pb-16 pt-32 sm:pt-40">
    <?php if ($heroImage): ?>
        <?php
        // Eager and high priority: this is the largest element above the fold,
        // so it is the page's Largest Contentful Paint. `loading="lazy"` on it
        // would defer the one image the score is measured on.
        //
        // An <img> rather than a CSS background-image, so it can carry alt text
        // and its dimensions, and so the browser can pick it up from the parser
        // rather than waiting for the stylesheet to say it is needed.
        $w = (int) ($heroImage['width'] ?? 0);
        $h = (int) ($heroImage['height'] ?? 0);
        ?>
        <img src="<?= esc(media_src(Modules\Media\Models\MediaModel::urlOf($heroImage)), 'attr') ?>"
             alt="<?= esc((string) ($heroImage['alt'] ?? ''), 'attr') ?>"
             <?php if ($w > 0 && $h > 0): ?>width="<?= $w ?>" height="<?= $h ?>"<?php endif; ?>
             class="absolute inset-0 -z-30 h-full w-full object-cover"
             fetchpriority="high" decoding="async">

        <?php // One layer, not two. A flat scrim and a gradient stacked multiply
              // — 70% over 70% is 91% in the middle — which is how the first
              // version of this buried the photograph so completely that the
              // bright block in the test image could not be found on screen.
              // The gradient alone carries the whole job: darkest under the
              // headline, lightest on the right where the only text is the dates
              // card, and that card brings its own ground. ?>
        <div class="absolute inset-0 -z-20 bg-gradient-to-r from-brand-black via-brand-black/85 to-brand-black/45"
             aria-hidden="true"></div>
    <?php endif; ?>

    <?php // The aurora is a CSS gradient, not an image: no request, no layout
          // shift, and it recolours with the theme tokens rather than needing a
          // second file for dark.
          //
          // Only when there is no photograph, because `.hero-aurora` finishes
          // its background stack with a bare `rgb(var(--bg))` — an opaque base,
          // not a wash. Left in place above the picture it is simply a solid
          // panel covering it, which is what it was: the scrim was tuned twice
          // and the bright block in the test image never appeared on screen,
          // because nothing of the image was reaching the screen at all. With a
          // photograph, the photograph is the backdrop. ?>
    <?php if (! $heroImage): ?>
        <div class="hero-aurora absolute inset-0 -z-10" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="container-x">
        <p class="eyebrow text-gold"><?= esc(lang('Site.home.hero_eyebrow')) ?></p>

        <h1 class="mt-5 max-w-4xl text-4xl font-bold leading-[1.08] sm:text-6xl lg:text-7xl">
            <?= esc(lang('Site.home.hero_heading')) ?>
        </h1>

        <p class="mt-6 max-w-2xl text-lg leading-relaxed <?= $heroImage ? 'text-white/90' : 'text-white/75' ?> sm:text-xl">
            <?= esc(lang('Site.home.hero_sub')) ?>
        </p>

        <div class="mt-9 flex flex-wrap gap-3">
            <a href="<?= esc(locale_url('adobe')) ?>" class="btn-brand"><?= esc(lang('Site.home.hero_cta_adobe')) ?></a>
            <a href="<?= esc(locale_url('ai')) ?>" class="btn-ghost"><?= esc(lang('Site.home.hero_cta_ai')) ?></a>
        </div>

        <p class="mt-5 max-w-xl text-sm <?= $heroImage ? 'text-white/85' : 'text-white/55' ?>"><?= esc(lang('Site.home.hero_note')) ?></p>

        <?php if ($strip !== []): ?>
            <?php // Three real dates, above the fold. This is the single most
                  // persuasive thing on the page and it costs one query. ?>
            <div class="mt-12 rounded-2xl border border-line <?= $heroImage ? 'bg-brand-black/80' : 'bg-white/5' ?> p-2 sm:p-3">
                <p class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-widest <?= $heroImage ? 'text-white/80' : 'text-white/50' ?>">
                    <?= esc(lang('Site.home.hero_next')) ?>
                </p>
                <ul class="grid gap-2 sm:grid-cols-3">
                    <?php foreach ($strip as $session): ?>
                        <li>
                            <a href="<?= esc(session_url($session)) ?>"
                               class="block rounded-xl px-3 py-3 transition hover:bg-white/5">
                                <span class="block text-sm font-semibold"><?= esc(t_field($session['course_title'])) ?></span>
                                <span class="mt-1 block text-xs <?= $heroImage ? 'text-white/85' : 'text-white/60' ?>">
                                    <?= esc(session_dates($session)) ?>
                                    · <?= esc(mode_label($session['mode'])) ?>
                                    <?php if (! empty($session['venue_city'])): ?> · <?= esc($session['venue_city']) ?><?php endif; ?>
                                </span>
                                <?php if (! empty($session['price_cents'])): ?>
                                    <span class="mt-1 block text-xs font-medium text-gold"><?= esc(money((int) $session['price_cents'], $currency)) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($facts !== []): ?>
    <?php // Derived, every one of them, in Home::facts(). Nothing here is typed
          // and nothing here is estimated — see the controller for why the
          // "learners trained" line the copy asked for is absent. ?>
    <section class="border-b border-line bg-surface py-6" aria-label="<?= esc(lang('Site.home.facts_label'), 'attr') ?>">
        <div class="container-x">
            <ul class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($facts as $fact): ?>
                    <li>
                        <p class="text-2xl font-bold text-brand-red"><?= esc($fact['value']) ?></p>
                        <p class="mt-0.5 text-sm text-white/60"><?= esc($fact['label']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>
