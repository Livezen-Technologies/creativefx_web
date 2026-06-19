<?php helper(['norlanka', 'url']); ?>
<div class="rounded-2xl border border-white/10 bg-white/[0.02] p-10 text-center" data-gsap="reveal">
    <?php if (! empty($content['title'])): ?>
        <h2 class="text-2xl font-semibold sm:text-3xl"><?= esc(t_field($content['title'])) ?></h2>
    <?php endif; ?>
    <?php if (! empty($content['text'])): ?>
        <p class="mx-auto mt-3 max-w-xl text-white/70"><?= esc(t_field($content['text'])) ?></p>
    <?php endif; ?>
    <?php if (! empty($content['button'])): ?>
        <a href="<?= esc(locale_url($content['url'] ?? '')) ?>" class="btn-brand mt-6"><?= esc(t_field($content['button'])) ?></a>
    <?php endif; ?>
</div>
