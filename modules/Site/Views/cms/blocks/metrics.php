<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<section class="border-y border-white/10 bg-gradient-to-b from-brand-black to-[#140003] py-20">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-10 text-center text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
            <?php foreach ($content['items'] as $item): ?>
                <div class="text-center" data-gsap="reveal">
                    <div class="text-4xl font-bold sm:text-5xl">
                        <span data-counter="<?= esc($item['value'] ?? '0', 'attr') ?>" data-decimals="0">0</span><span class="text-brand-red"><?= esc($item['suffix'] ?? '') ?></span>
                    </div>
                    <p class="mt-2 text-xs uppercase tracking-widest text-white/50"><?= esc(t_field($item['label'] ?? [])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
