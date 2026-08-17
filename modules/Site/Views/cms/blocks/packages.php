<?php helper(['norlanka', 'url']); if (empty($content['items']) || ! is_array($content['items'])) { return; }

// Resolve every package once: the column count, and whether a ribbon strip has
// to be reserved, are decisions about the *set*, so they can't be made inside
// the render loop (same prepass idiom as faq.php).
$packages = [];
foreach ($content['items'] as $item) {
    $name = trim(t_field($item['name'] ?? []));
    if ($name === '') { continue; } // an unnamed tier has nothing to sell

    $features = [];
    foreach ((array) ($item['features'] ?? []) as $feature) {
        $label = trim(t_field($feature));
        if ($label !== '') { $features[] = $label; } // a blank line would render a stray tick
    }

    // Absolute paths and full URLs pass through as-is; a bare slug is a
    // locale-prefixed route. No url at all = a card that only informs.
    $url = trim((string) ($item['url'] ?? ''));

    $featured = ! empty($item['featured']);

    $packages[] = [
        'name' => $name,
        // Raw string on purpose: "Custom" and "From LKR 75,000" are both valid
        // prices, so nothing is parsed or formatted here.
        'price'    => trim((string) ($item['price'] ?? '')),
        'currency' => trim((string) ($item['currency'] ?? '')),
        'period'   => trim(t_field($item['period'] ?? [])),
        'summary'  => trim(t_field($item['summary'] ?? [])),
        'features' => $features,
        'href'     => $url === '' ? null : (($url[0] === '/' || str_starts_with($url, 'http')) ? $url : locale_url($url)),
        'cta'      => trim(t_field($item['cta'] ?? [])),
        'featured' => $featured,
        // The ribbon only ever belongs to the highlighted tier, and its wording
        // comes from the payload — no English fallback on a multilingual site.
        'ribbon' => $featured ? trim(t_field($item['ribbon'] ?? [])) : '',
    ];
}
if ($packages === []) { return; }

// A ribbon on one card alone would push that card's name a line lower than the
// rest; reserving the strip on every card keeps the tier names on one baseline.
$hasRibbon = array_filter(array_column($packages, 'ribbon')) !== [];

// Four tracks is the wide default, but three packages in it would strand the
// last card on a row of its own — and one or two cards would be squeezed into
// a narrow column with acres of empty grid beside them.
$gridCols = match (count($packages)) {
    1       => 'max-w-sm',
    2       => 'sm:grid-cols-2',
    3       => 'sm:grid-cols-2 lg:grid-cols-3',
    default => 'sm:grid-cols-2 lg:grid-cols-4',
};
?>
<!-- Pricing tiers for a service page. Cards stretch to the tallest in the row
     (grid default) and each is a flex column, so the CTA sits on the bottom
     edge no matter how uneven the feature lists are. -->
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="eyebrow" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <?php if (! empty($content['title'])): ?>
            <h2 class="mt-5 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>

        <div class="mt-10 grid gap-6 <?= $gridCols ?>">
            <?php foreach ($packages as $package): ?>
                <article class="flex flex-col rounded-2xl border p-7 transition <?= $package['featured']
                        ? 'border-brand-red bg-white/[0.05] shadow-lg shadow-brand-red/10'
                        : 'border-white/10 bg-white/[0.02] hover:border-brand-red/60' ?>" data-gsap="reveal">

                    <?php if ($hasRibbon): ?>
                        <!-- Kept as real text, not a decoration: "Most popular" is
                             part of what the card says. -->
                        <p class="mb-4 flex min-h-[1.75rem] items-start">
                            <?php if ($package['ribbon'] !== ''): ?>
                                <span class="inline-flex items-center rounded-full border border-brand-red/50 bg-brand-red/15 px-3 py-1 text-[10px] font-semibold uppercase tracking-widest text-brand-red"><?= esc($package['ribbon']) ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <h3 class="text-lg font-semibold"><?= esc($package['name']) ?></h3>

                    <?php if ($package['price'] !== ''): ?>
                        <!-- No price, no row: a lone currency symbol or a dangling
                             period would read as a bug. break-words keeps a long
                             "From LKR 75,000" inside a narrow column. -->
                        <p class="mt-4 flex flex-wrap items-baseline gap-x-1.5 break-words">
                            <?php if ($package['currency'] !== ''): ?>
                                <span class="text-lg font-semibold text-brand-red"><?= esc($package['currency']) ?></span>
                            <?php endif; ?>
                            <span class="text-2xl font-bold sm:text-3xl"><?= esc($package['price']) ?></span>
                            <?php if ($package['period'] !== ''): ?>
                                <!-- The payload owns the wording ("/mo", "per month",
                                     "one-off"), so no separator is prepended here. -->
                                <span class="text-sm text-white/50"><?= esc($package['period']) ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($package['summary'] !== ''): ?>
                        <p class="mt-3 text-sm leading-relaxed text-white/60"><?= esc($package['summary']) ?></p>
                    <?php endif; ?>

                    <?php if ($package['features'] !== []): ?>
                        <ul class="mt-6 space-y-3 text-sm leading-relaxed text-white/70">
                            <?php foreach ($package['features'] as $feature): ?>
                                <li class="flex gap-3">
                                    <svg class="mt-1 h-4 w-4 flex-none text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m20 6-11 11-5-5"/></svg>
                                    <?php // min-w-0 + break-words: a long unbroken term wraps inside the column, never widens the page. ?>
                                    <span class="min-w-0 break-words"><?= esc($feature) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($package['href'] !== null): ?>
                        <div class="mt-auto pt-7">
                            <?php if ($package['cta'] !== ''): ?>
                                <?php // px-4 overrides the button component's px-7: four tiers across
                                      // a row leave each card narrow, and the default padding wraps
                                      // a label like "Request a Quote" onto two ragged lines. ?>
                                <a href="<?= esc($package['href'], 'attr') ?>" class="<?= $package['featured'] ? 'btn-brand' : 'btn-ghost' ?> w-full px-4 text-center">
                                    <?= esc($package['cta']) ?>
                                    <!-- "Get started" four times over is ambiguous out of
                                         context — name the tier for screen readers. -->
                                    <span class="sr-only"><?= esc($package['name']) ?></span>
                                </a>
                            <?php else: ?>
                                <!-- No label in the payload: fall back to the arrow link
                                     rather than shipping a button with nothing written on
                                     it (same fallback as services_grid.php). -->
                                <a href="<?= esc($package['href'], 'attr') ?>"
                                   class="inline-flex items-center gap-2 rounded-sm text-xs font-semibold uppercase tracking-widest text-brand-red focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                    <span class="sr-only"><?= esc($package['name']) ?></span>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
