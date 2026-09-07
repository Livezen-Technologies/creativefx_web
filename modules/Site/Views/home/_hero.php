<?php
helper(['norlanka', 'catalog', 'commerce', 'url']);

/**
 * The hero.
 *
 * Typographic rather than photographic, on purpose. A training school launching
 * with no photography of its own has two options: a stock picture of somebody
 * else's classroom, or its own words. The words are more honest and they load
 * faster, and the strip of real dates underneath does the job the photograph
 * would have been pretending to do — it proves classes run.
 *
 * The dual call to action is the whole navigation problem of this site in one
 * element: half the audience arrived for Adobe and half for AI, and neither
 * half should have to read past the other to find theirs.
 *
 * @var list<array> $upcoming   the next few sessions, priced
 * @var string      $currency
 * @var list<array> $facts
 */
$strip = array_slice(array_values(array_filter(
    $upcoming,
    static fn (array $s): bool => ! empty($s['start_date'])
)), 0, 3);
?>
<section class="on-dark relative overflow-hidden bg-brand-black pb-16 pt-32 sm:pt-40">
    <?php // The aurora is a CSS gradient, not an image: no request, no layout
          // shift, and it recolours with the theme tokens rather than needing a
          // second file for dark. ?>
    <div class="hero-aurora absolute inset-0 -z-10" aria-hidden="true"></div>

    <div class="container-x">
        <p class="eyebrow text-gold"><?= esc(lang('Site.home.hero_eyebrow')) ?></p>

        <h1 class="mt-5 max-w-4xl text-4xl font-bold leading-[1.08] sm:text-6xl lg:text-7xl">
            <?= esc(lang('Site.home.hero_heading')) ?>
        </h1>

        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white/75 sm:text-xl">
            <?= esc(lang('Site.home.hero_sub')) ?>
        </p>

        <div class="mt-9 flex flex-wrap gap-3">
            <a href="<?= esc(locale_url('adobe')) ?>" class="btn-brand"><?= esc(lang('Site.home.hero_cta_adobe')) ?></a>
            <a href="<?= esc(locale_url('ai')) ?>" class="btn-ghost"><?= esc(lang('Site.home.hero_cta_ai')) ?></a>
        </div>

        <p class="mt-5 max-w-xl text-sm text-white/55"><?= esc(lang('Site.home.hero_note')) ?></p>

        <?php if ($strip !== []): ?>
            <?php // Three real dates, above the fold. This is the single most
                  // persuasive thing on the page and it costs one query. ?>
            <div class="mt-12 rounded-2xl border border-line bg-white/5 p-2 sm:p-3">
                <p class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-widest text-white/50">
                    <?= esc(lang('Site.home.hero_next')) ?>
                </p>
                <ul class="grid gap-2 sm:grid-cols-3">
                    <?php foreach ($strip as $session): ?>
                        <li>
                            <a href="<?= esc(session_url($session)) ?>"
                               class="block rounded-xl px-3 py-3 transition hover:bg-white/5">
                                <span class="block text-sm font-semibold"><?= esc(t_field($session['course_title'])) ?></span>
                                <span class="mt-1 block text-xs text-white/60">
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
