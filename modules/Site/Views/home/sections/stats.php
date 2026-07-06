<?php helper('norlanka'); if (empty($section['blocks'])) { return; } ?>
<section class="border-y border-white/10 bg-brand-black py-20">
    <div class="container-x grid grid-cols-2 gap-8 md:grid-cols-4">
        <?php foreach ($section['blocks'] as $block): $c = json_decode($block['content'] ?? '[]', true) ?: []; ?>
            <div class="text-center" data-gsap="reveal">
                <div class="text-4xl font-bold sm:text-5xl">
                    <span data-counter="<?= esc($c['value'] ?? '0', 'attr') ?>" data-decimals="0">0</span><span class="text-brand-red"><?= esc($c['suffix'] ?? '') ?></span>
                </div>
                <p class="mt-2 text-xs uppercase tracking-widest text-white/50"><?= esc(t_field($c['label'] ?? [])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
