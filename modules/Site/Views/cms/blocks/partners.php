<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-3 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mb-10 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5" data-gsap="reveal">
            <?php foreach ($content['items'] as $item): ?>
                <div class="flex h-20 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-4 text-center text-sm font-semibold uppercase tracking-wider text-white/70">
                    <?= esc(is_array($item) ? t_field($item) : $item) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
