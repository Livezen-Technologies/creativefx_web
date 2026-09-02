<?php helper('norlanka'); if (empty($section['blocks'])) { return; }

/**
 * "Welcome to Giants Forest" — the hotel's own introduction, set beside a pair
 * of overlapping photographs.
 *
 * The collage is two images rather than one because that is what the source
 * site does, and it earns the space: a portrait of the pool at dusk and a
 * landscape of the reservoir say two different things about the place. They
 * overlap on large screens and stack cleanly on small ones, where an offset
 * would only cost height.
 */
$c = json_decode($section['blocks'][0]['content'] ?? '[]', true) ?: [];
if ($c === []) { return; }
?>
<section id="welcome" class="bg-brand-black py-20 sm:py-28">
    <div class="container-x">
        <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">

            <!-- Collage -->
            <?php if (! empty($c['image_a'])): ?>
                <div class="relative" data-gsap="reveal" data-parallax="7">
                    <img src="<?= esc(media_src($c['image_a']), 'attr') ?>"
                         alt="<?= esc(t_field($c['image_a_alt'] ?? []), 'attr') ?>"
                         loading="lazy" width="902" height="1024"
                         class="w-full max-w-md rounded-sm object-cover shadow-2xl sm:w-4/5">
                    <?php if (! empty($c['image_b'])): ?>
                        <!-- Pulled up into the first image on wide screens; in normal
                             flow below it on narrow ones, where overlapping would
                             hide the subject rather than layer it. -->
                        <img src="<?= esc(media_src($c['image_b']), 'attr') ?>"
                             alt="<?= esc(t_field($c['image_b_alt'] ?? []), 'attr') ?>"
                             loading="lazy" width="1024" height="576"
                             class="mt-6 w-full rounded-sm object-cover shadow-2xl
                                    sm:absolute sm:-bottom-10 sm:right-0 sm:mt-0 sm:w-3/5">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Copy -->
            <div data-gsap="reveal">
                <?php if (! empty($c['eyebrow'])): ?>
                    <p class="eyebrow"><?= esc(t_field($c['eyebrow'])) ?></p>
                <?php endif; ?>

                <?php if (! empty($c['title'])): ?>
                    <h2 class="mt-5 text-3xl font-bold leading-tight sm:text-5xl">
                        <?= esc(t_field($c['title'])) ?>
                    </h2>
                <?php endif; ?>

                <?php if (! empty($c['lead'])): ?>
                    <p class="mt-8 text-lg font-semibold leading-relaxed text-white/85">
                        <?= esc(t_field($c['lead'])) ?>
                    </p>
                <?php endif; ?>

                <?php foreach ($c['body'] ?? [] as $para): ?>
                    <p class="mt-5 leading-relaxed text-white/70"><?= esc(t_field($para)) ?></p>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>
