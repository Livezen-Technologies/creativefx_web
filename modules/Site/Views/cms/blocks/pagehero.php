<?php helper('norlanka'); ?>
<section class="relative overflow-hidden">
    <div class="hero-aurora absolute inset-0 -z-20"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-black/40 via-black/20 to-brand-black"></div>
    <div class="container-x flex min-h-[60vh] flex-col justify-end pb-16 pt-40">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="mb-4 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <h1 class="max-w-4xl text-4xl font-bold leading-[1.05] sm:text-6xl" data-gsap="reveal"><?= esc(t_field($content['title'] ?? [])) ?></h1>
        <?php if (! empty($content['subtitle'])): ?>
            <p class="mt-6 max-w-2xl text-lg text-white/70" data-gsap="reveal"><?= esc(t_field($content['subtitle'])) ?></p>
        <?php endif; ?>
    </div>
</section>
