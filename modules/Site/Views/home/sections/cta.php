<?php helper(['norlanka', 'url']); if (empty($section['blocks'])) { return; } $c = json_decode($section['blocks'][0]['content'] ?? '[]', true) ?: []; ?>
<section class="on-dark relative overflow-hidden py-28">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-60"></div>
    <div class="absolute inset-0 -z-10 bg-black/40"></div>
    <div class="container-x text-center" data-gsap="reveal">
        <h2 class="text-3xl font-bold sm:text-5xl"><?= esc(t_field($c['title'] ?? [])) ?></h2>
        <p class="mx-auto mt-4 max-w-xl text-white/70"><?= esc(t_field($c['text'] ?? [])) ?></p>
        <a href="<?= esc(locale_url($c['url'] ?? 'showroom')) ?>" class="btn-brand mt-8"><?= esc(t_field($c['button'] ?? [])) ?></a>
    </div>
</section>
