<?php helper('norlanka'); ?>
<section class="bg-brand-black pt-20 pb-6">
    <div class="container-x max-w-3xl" data-gsap="reveal">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <h2 class="text-3xl font-bold sm:text-4xl"><?= esc(t_field($content['title'] ?? [])) ?></h2>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-4 text-lg leading-relaxed text-white/80"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>
    </div>
</section>
