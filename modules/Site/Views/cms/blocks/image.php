<?php helper('norlanka'); if (empty($content['src'])) { return; } ?>
<figure class="overflow-hidden rounded-2xl" data-gsap="reveal">
    <img src="<?= esc($content['src']) ?>" alt="<?= esc(t_field($content['alt'] ?? [])) ?>" class="w-full object-cover" loading="lazy">
    <?php if (! empty($content['caption'])): ?>
        <figcaption class="mt-2 text-sm text-white/50"><?= esc(t_field($content['caption'])) ?></figcaption>
    <?php endif; ?>
</figure>
