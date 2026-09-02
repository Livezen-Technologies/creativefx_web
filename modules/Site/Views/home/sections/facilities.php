<?php helper('norlanka'); if (empty($section['blocks'])) { return; }

/**
 * "Our Facilities" — a green panel of amenities laid over an aerial of the
 * property, the way the hotel's own site presents them.
 *
 * Icons are chosen by a key carried in the content, not by position, so
 * reordering or adding an amenity in the CMS cannot silently hand it somebody
 * else's picture. An unrecognised key falls back to a plain check, which is
 * honest: the amenity is still listed, just without a bespoke mark.
 */
$c = json_decode($section['blocks'][0]['content'] ?? '[]', true) ?: [];
if ($c === []) { return; }

$icons = [
    'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'droplet'   => '<path d="M12 3s6 6.2 6 10a6 6 0 01-12 0c0-3.8 6-10 6-10z"/>',
    'mic'       => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0014 0M12 18v3"/>',
    'car'       => '<path d="M5 16h14M6.5 16V9.5L8 6h8l1.5 3.5V16"/><circle cx="8" cy="17.5" r="1.5"/><circle cx="16" cy="17.5" r="1.5"/>',
    'waves'     => '<path d="M2 8c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2M2 14c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2"/>',
    'snowflake' => '<path d="M12 2v20M4 7l16 10M20 7L4 17M12 6l-2.5-2M12 6l2.5-2M12 18l-2.5 2M12 18l2.5 2"/>',
    'check'     => '<path d="M5 12.5l4.5 4.5L19 7"/>',
];
?>
<section class="bg-brand-black py-20 sm:py-28">
    <div class="container-x">
        <div class="relative lg:grid lg:grid-cols-12 lg:items-center">

            <?php if (! empty($c['image'])): ?>
                <!-- Both items are pinned to row 1, and both name their starting
                     column. Row alone is not enough: the panel explicitly holds
                     columns 7-12, so a span-7 image with no start could not fit
                     in the six columns left and grid invented seven more off the
                     right-hand side to hold it. Pinning the row without pinning
                     the column trades a stacked layout for a broken one. -->
                <div class="lg:col-span-7 lg:col-start-1 lg:row-start-1" data-gsap="reveal">
                    <img src="<?= esc(media_src($c['image']), 'attr') ?>"
                         alt="<?= esc(t_field($c['image_alt'] ?? []), 'attr') ?>"
                         loading="lazy" width="1200" height="800"
                         class="h-72 w-full rounded-sm object-cover sm:h-96 lg:h-[34rem]">
                </div>
            <?php endif; ?>

            <!-- The panel overlaps the photograph on wide screens and simply
                 follows it on narrow ones. -->
            <div class="panel-forest relative z-10 -mt-10 overflow-hidden p-8 sm:p-12
                        lg:col-span-6 lg:col-start-7 lg:row-start-1 lg:mt-0 lg:p-14"
                 data-gsap="reveal">

                <?php if (! empty($c['watermark'])): ?>
                    <img src="<?= esc(media_src($c['watermark']), 'attr') ?>" alt="" aria-hidden="true"
                         class="pointer-events-none absolute -right-10 top-1/2 w-96 max-w-none
                                -translate-y-1/2 opacity-[0.07]">
                <?php endif; ?>

                <div class="relative">
                    <span class="block h-0.5 w-12 bg-white"></span>
                    <?php if (! empty($c['title'])): ?>
                        <h2 class="mt-6 text-3xl font-bold sm:text-4xl"><?= esc(t_field($c['title'])) ?></h2>
                    <?php endif; ?>
                    <?php if (! empty($c['intro'])): ?>
                        <p class="mt-5 max-w-md leading-relaxed text-white/85"><?= esc(t_field($c['intro'])) ?></p>
                    <?php endif; ?>

                    <ul class="mt-10 grid gap-6 sm:grid-cols-2">
                        <?php foreach ($c['items'] ?? [] as $item): ?>
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.6"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <?= $icons[$item['icon'] ?? 'check'] ?? $icons['check'] ?>
                                </svg>
                                <span class="text-sm leading-snug sm:text-base"><?= esc(t_field($item['label'] ?? [])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>
