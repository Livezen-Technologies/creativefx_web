<?php helper(['norlanka', 'url']); ?>
<section class="relative overflow-hidden py-24">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-70"></div>
    <div class="container-x text-center" data-gsap="reveal">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-3xl font-bold sm:text-4xl"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['text'])): ?>
            <p class="mx-auto mt-4 max-w-xl text-white/70"><?= esc(t_field($content['text'])) ?></p>
        <?php endif; ?>
        <?php if (! empty($content['button'])):
            // Absolute paths / full URLs (e.g. file downloads) pass through as-is;
            // bare slugs are locale-prefixed routes.
            $ctaUrl = (string) ($content['url'] ?? '');
            $href   = ($ctaUrl !== '' && ($ctaUrl[0] === '/' || str_starts_with($ctaUrl, 'http'))) ? $ctaUrl : locale_url($ctaUrl); ?>
            <a href="<?= esc($href) ?>" <?= str_ends_with($href, '.pdf') ? 'target="_blank" rel="noopener"' : '' ?> class="btn-brand mt-8"><?= esc(t_field($content['button'])) ?></a>
        <?php endif; ?>
    </div>
</section>
