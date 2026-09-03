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
            <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <?php // A booking button opens the dialog rather than navigating, when the
                      // block asks for it. It stays a real link to the same destination, so
                      // without JavaScript it still goes somewhere useful. ?>
                <a href="<?= esc($href) ?>"
                   <?php // $dispatch resolves through an Alpine component, and a CMS page has
                      // no x-data anywhere near this button — so the directive was
                      // parsed, bound to nothing, and the link simply navigated.
                      // An empty x-data makes the anchor its own root, which costs
                      // nothing and works wherever the block is placed. ?>
                   <?= booking_open($content) ?>
                   <?= str_ends_with($href, '.pdf') ? 'target="_blank" rel="noopener"' : '' ?>
                   class="btn-brand"><?= esc(t_field($content['button'])) ?></a>
                <?php // An optional second, quieter action beside the first. Blocks
                      // that seed only one button render exactly as before. ?>
                <?php if (! empty($content['button2'])):
                    $u2 = (string) ($content['url2'] ?? '');
                    $h2 = ($u2 !== '' && ($u2[0] === '/' || str_starts_with($u2, 'http'))) ? $u2 : locale_url($u2); ?>
                    <a href="<?= esc($h2) ?>" class="btn-ghost"><?= esc(t_field($content['button2'])) ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
