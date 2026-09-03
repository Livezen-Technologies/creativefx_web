<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.gallery.videos_title')]],
    'eyebrow' => lang('Site.nav.media_centre'),
    'heading' => lang('Site.gallery.videos_title'),
    'intro'   => lang('Site.gallery.videos_intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x">
        <?php if ($videos === []): ?>
            <p class="rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.gallery.no_videos')) ?></p>
        <?php else: ?>
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                <?php foreach ($videos as $video): ?>
                    <li class="overflow-hidden rounded-2xl border border-line bg-surface">
                        <?php // Self-hosted files play in the browser's own
                              // controls; a video whose src is missing shows its
                              // poster rather than a black box. ?>
                        <video controls preload="none"
                               <?= ! empty($video['poster_path']) ? 'poster="' . esc(media_src($video['poster_path']), 'attr') . '"' : '' ?>
                               class="aspect-video w-full bg-black">
                            <?php if (! empty($video['src_path_webm'])): ?>
                                <source src="<?= esc(media_src($video['src_path_webm']), 'attr') ?>" type="video/webm">
                            <?php endif; ?>
                            <?php if (! empty($video['src_path'])): ?>
                                <source src="<?= esc(media_src($video['src_path']), 'attr') ?>" type="video/mp4">
                            <?php endif; ?>
                        </video>
                        <div class="p-5">
                            <h2 class="text-sm font-semibold leading-snug"><?= esc(t_field($video['title'])) ?></h2>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
