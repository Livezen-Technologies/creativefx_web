<?php
helper(['norlanka', 'url']);
// Dynamic block: renders featured catalog products (or the newest published
// ones when nothing is featured yet).
try {
    $model    = model('Modules\Catalog\Models\ProductModel');
    $limit    = (int) ($content['limit'] ?? 4);
    $gridProducts = $model->featured($limit);
    if ($gridProducts === []) {
        $gridProducts = $model->published()->findAll($limit);
    }
} catch (\Throwable $e) {
    $gridProducts = [];
}
if ($gridProducts === []) {
    return;
}
?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <?php if (! empty($content['eyebrow'])): ?>
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-red" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
                <?php endif; ?>
                <h2 class="mt-2 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(! empty($content['title']) ? t_field($content['title']) : lang('Site.products.title')) ?></h2>
            </div>
            <a href="<?= esc(locale_url('products')) ?>" class="btn-ghost !px-5 !py-2.5 text-xs" data-gsap="reveal"><?= esc(lang('Site.products.view_all')) ?></a>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4" data-gsap="reveal">
            <?php foreach ($gridProducts as $gridProduct): ?>
                <?= view('Modules\Catalog\Views\partials\card', ['product' => $gridProduct, 'label' => '']) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
