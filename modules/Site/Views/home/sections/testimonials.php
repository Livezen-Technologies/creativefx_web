<?php helper('norlanka'); if (empty($section['blocks'])) { return; }

/**
 * "People Say" — guest reviews on a green panel beside a photograph.
 *
 * The quotes are reproduced as the guests wrote them, including the odd typo.
 * They are attributed to named people, so tidying their words would put
 * sentences in their mouths that they did not write.
 */
$blocks = $section['blocks'];
$head   = json_decode($blocks[0]['content'] ?? '[]', true) ?: [];
$quotes = [];
foreach (array_slice($blocks, 1) as $b) {
    $q = json_decode($b['content'] ?? '[]', true) ?: [];
    if ($q !== []) { $quotes[] = $q; }
}
if ($quotes === []) { return; }
?>
<section class="bg-brand-black py-20 sm:py-28">
    <div class="container-x">
        <div class="relative lg:grid lg:grid-cols-12 lg:items-center">

            <?php if (! empty($head['image'])): ?>
                <!-- Pinned to row 1 for the same reason as Our Facilities: the
                     panel and the photograph share a column, and auto-placement
                     would push whichever came second onto a row of its own. -->
                <div class="lg:col-span-7 lg:col-start-6 lg:row-start-1" data-gsap="reveal">
                    <img src="<?= esc(media_src($head['image']), 'attr') ?>"
                         alt="<?= esc(t_field($head['image_alt'] ?? []), 'attr') ?>"
                         loading="lazy" width="1200" height="800"
                         class="h-72 w-full rounded-sm object-cover sm:h-96 lg:h-[36rem]">
                </div>
            <?php endif; ?>

            <div class="panel-forest relative z-10 -mt-10 overflow-hidden p-8 sm:p-12
                        lg:col-span-6 lg:col-start-1 lg:row-start-1 lg:mt-0 lg:p-14"
                 data-gsap="reveal">

                <?php if (! empty($head['watermark'])): ?>
                    <img src="<?= esc(media_src($head['watermark']), 'attr') ?>" alt="" aria-hidden="true"
                         class="pointer-events-none absolute -right-12 top-1/2 w-96 max-w-none
                                -translate-y-1/2 opacity-[0.07]">
                <?php endif; ?>

                <div class="relative">
                    <span class="block h-0.5 w-12 bg-white"></span>
                    <?php if (! empty($head['title'])): ?>
                        <h2 class="mt-6 text-3xl font-bold sm:text-4xl"><?= esc(t_field($head['title'])) ?></h2>
                    <?php endif; ?>

                    <!-- One review at a time: these are paragraphs, not thumbnails,
                         so the usual multi-slide carousel settings do not fit. -->
                    <div class="swiper mt-10" data-swiper data-swiper-per-view="1">
                        <div class="swiper-wrapper">
                            <?php foreach ($quotes as $q): ?>
                                <div class="swiper-slide">
                                    <figure>
                                        <svg class="h-8 w-8 opacity-60" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M9.5 6C6.5 7.5 5 10 5 13v5h6v-6H8c0-2 .8-3.4 2.4-4.3L9.5 6zm9 0C15.5 7.5 14 10 14 13v5h6v-6h-3c0-2 .8-3.4 2.4-4.3L18.5 6z"/>
                                        </svg>
                                        <blockquote class="mt-4 whitespace-pre-line font-display text-lg italic leading-relaxed">
                                            <?= esc(t_field($q['quote'] ?? [])) ?>
                                        </blockquote>
                                        <figcaption class="mt-8">
                                            <span class="block font-semibold"><?= esc($q['name'] ?? '') ?></span>
                                            <span class="block text-sm text-white/75"><?= esc($q['place'] ?? '') ?></span>
                                        </figcaption>
                                    </figure>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-10 flex gap-3">
                            <button type="button"
                                    class="swiper-button-prev-c inline-flex h-10 w-10 items-center justify-center
                                           bg-white text-[rgb(var(--forest))] transition hover:opacity-85
                                           focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    aria-label="Previous review">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
                            </button>
                            <button type="button"
                                    class="swiper-button-next-c inline-flex h-10 w-10 items-center justify-center
                                           bg-white text-[rgb(var(--forest))] transition hover:opacity-85
                                           focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    aria-label="Next review">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
