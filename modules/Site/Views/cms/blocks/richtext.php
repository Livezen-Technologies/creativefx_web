<?php helper('norlanka'); ?>
<div class="prose prose-invert max-w-3xl" data-gsap="reveal">
    <?php if (! empty($content['title'])): ?>
        <h2 class="text-2xl font-semibold"><?= esc(t_field($content['title'])) ?></h2>
    <?php endif; ?>
    <?php if (! empty($content['text'])): ?>
        <p class="leading-relaxed text-white/70"><?= esc(t_field($content['text'])) ?></p>
    <?php endif; ?>
</div>
