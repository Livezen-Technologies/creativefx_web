<?php helper(['norlanka', 'url']); ?>
<section class="relative overflow-hidden py-24">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-50"></div>
    <div class="absolute inset-0 -z-10 bg-black/40"></div>
    <div class="container-x text-center" data-gsap="reveal">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-3xl font-bold sm:text-4xl"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['text'])): ?>
            <p class="mx-auto mt-4 max-w-xl text-white/70"><?= esc(t_field($content['text'])) ?></p>
        <?php endif; ?>
        <?php if (! empty($content['button'])): ?>
            <a href="<?= esc(locale_url($content['url'] ?? '')) ?>" class="btn-brand mt-8"><?= esc(t_field($content['button'])) ?></a>
        <?php endif; ?>
    </div>
</section>
