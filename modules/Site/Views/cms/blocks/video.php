<?php helper('norlanka'); if (empty($content['src'])) { return; } ?>
<!-- Corporate video block: a simple, self-hosted player. -->
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-8 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <figure class="isolate overflow-hidden rounded-3xl border border-white/10" data-gsap="reveal">
            <video controls preload="metadata" playsinline class="aspect-video w-full bg-black object-cover"
                   <?= ! empty($content['poster']) ? 'poster="' . esc($content['poster'], 'attr') . '"' : '' ?>>
                <source src="<?= esc($content['src'], 'attr') ?>" type="video/mp4">
            </video>
            <?php if (! empty($content['caption'])): ?>
                <figcaption class="px-6 py-4 text-sm text-white/55"><?= esc(t_field($content['caption'])) ?></figcaption>
            <?php endif; ?>
        </figure>
    </div>
</section>
