<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<section class="bg-gradient-to-b from-brand-black to-[#140003] py-20">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-10 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($content['items'] as $i => $item): ?>
                <div class="relative rounded-2xl border border-white/10 bg-white/[0.02] p-6" data-gsap="reveal">
                    <div class="text-3xl font-bold text-brand-red/80"><?= sprintf('%02d', $i + 1) ?></div>
                    <h3 class="mt-2 text-lg font-semibold"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                    <?php if (! empty($item['text'])): ?>
                        <p class="mt-2 text-sm text-white/60"><?= esc(t_field($item['text'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
