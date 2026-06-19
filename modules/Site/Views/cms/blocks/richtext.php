<?php helper('norlanka'); if (empty($content['title']) && empty($content['text'])) { return; } ?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <div class="max-w-3xl" data-gsap="reveal">
            <?php if (! empty($content['eyebrow'])): ?>
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc(t_field($content['eyebrow'])) ?></p>
            <?php endif; ?>
            <?php if (! empty($content['title'])): ?>
                <h2 class="text-2xl font-semibold sm:text-3xl"><?= esc(t_field($content['title'])) ?></h2>
            <?php endif; ?>
            <?php if (! empty($content['text'])): ?>
                <p class="mt-4 leading-relaxed text-white/70"><?= nl2br(esc(t_field($content['text']))) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
