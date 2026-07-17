<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<!-- Full-width centred statements (vision / mission style), per the content
     sheet: "Have the statements covering the page, align it to the middle". -->
<section class="relative overflow-hidden py-24 sm:py-32">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-50"></div>
    <div class="container-x space-y-16 text-center">
        <?php foreach ($content['items'] as $item): ?>
            <div data-gsap="reveal">
                <?php if (! empty($item['title'])): ?>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc(t_field($item['title'])) ?></p>
                <?php endif; ?>
                <?php if (! empty($item['text'])): ?>
                    <blockquote class="mx-auto mt-5 max-w-4xl text-2xl font-semibold leading-snug sm:text-4xl sm:leading-snug">
                        &lsquo;<?= esc(t_field($item['text'])) ?>&rsquo;
                    </blockquote>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
