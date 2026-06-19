<?php
helper('norlanka');
// Dynamic block: pulls the live product categories seeded by the Catalog module.
$categories = model('Modules\Catalog\Models\ProductCategoryModel')->published();
?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <?php foreach ($categories as $cat): ?>
                <div class="group relative overflow-hidden rounded-xl border border-white/10 bg-white/[0.02] p-6 transition hover:border-brand-red/60 hover:bg-white/[0.04]" data-gsap="reveal">
                    <div class="absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-brand-red transition-transform duration-300 group-hover:scale-x-100"></div>
                    <h3 class="text-base font-semibold leading-snug"><?= esc(t_field($cat['name'] ?? [])) ?></h3>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
