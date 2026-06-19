<?php helper('norlanka'); ?>
<section class="relative overflow-hidden bg-gradient-to-b from-[#140003] to-brand-black py-20">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>

        <?php if (! empty($content['embed'])): ?>
            <div class="mt-8 overflow-hidden rounded-2xl border border-white/10" data-gsap="reveal">
                <iframe src="<?= esc($content['embed']) ?>" class="h-80 w-full" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        <?php endif; ?>

        <?php if (! empty($content['items']) && is_array($content['items'])): ?>
            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <?php foreach ($content['items'] as $item): ?>
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-5" data-gsap="reveal">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-brand-red"></span>
                            <span class="text-sm font-semibold"><?= esc(t_field($item['region'] ?? [])) ?></span>
                        </div>
                        <?php if (! empty($item['detail'])): ?>
                            <p class="mt-2 text-xs text-white/55"><?= esc(t_field($item['detail'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
