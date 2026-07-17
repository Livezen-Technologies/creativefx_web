<?php
helper(['norlanka', 'url']);
// Dynamic block: renders the newest live articles from the News module.
try {
    $newsPosts = model('Modules\News\Models\NewsPostModel')->latest((int) ($content['limit'] ?? 3));
} catch (\Throwable $e) {
    $newsPosts = [];
}
if ($newsPosts === []) {
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
                <h2 class="mt-2 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(! empty($content['title']) ? t_field($content['title']) : lang('Site.news.latest')) ?></h2>
            </div>
            <a href="<?= esc(locale_url('news')) ?>" class="btn-ghost !px-5 !py-2.5 text-xs" data-gsap="reveal"><?= esc(lang('Site.news.view_all')) ?></a>
        </div>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-gsap="reveal">
            <?php foreach ($newsPosts as $newsPost): ?>
                <?= view('Modules\News\Views\partials\card', ['post' => $newsPost, 'label' => '']) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
