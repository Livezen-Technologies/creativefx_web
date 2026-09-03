<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.sitemap.title')]],
    'eyebrow' => lang('Site.nav.sitemap'),
    'heading' => lang('Site.sitemap.title'),
    'intro'   => lang('Site.sitemap.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.sitemap.sections')) ?></h2>
                <ul class="mt-4 space-y-2 text-sm" role="list">
                    <li><a href="<?= esc(locale_url('')) ?>" class="font-medium hover:text-brand-red"><?= esc(lang('Site.nav.home')) ?></a></li>
                    <?php foreach ($nav as $item): ?>
                        <li>
                            <a href="<?= esc($item['url'], 'attr') ?>" class="font-medium hover:text-brand-red"><?= esc($item['label']) ?></a>
                            <?php if ($item['children'] !== []): ?>
                                <ul class="mt-1.5 space-y-1.5 pl-4" role="list">
                                    <?php foreach ($item['children'] as $child): ?>
                                        <li><a href="<?= esc($child['url'], 'attr') ?>" class="text-white/65 hover:text-brand-red"><?= esc($child['label']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($services !== []): ?>
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.sitemap.services')) ?></h2>
                    <ul class="mt-4 space-y-2 text-sm" role="list">
                        <?php foreach ($services as $service): ?>
                            <li><a href="<?= esc(locale_url('services/' . $service['slug'])) ?>" class="hover:text-brand-red"><?= esc(t_field($service['title'])) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="space-y-10">
                <?php if ($pages !== []): ?>
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.sitemap.pages')) ?></h2>
                        <ul class="mt-4 space-y-2 text-sm" role="list">
                            <?php foreach ($pages as $page): ?>
                                <li>
                                    <a href="<?= esc(locale_url((int) $page['is_home'] === 1 ? '' : $page['slug'])) ?>" class="hover:text-brand-red">
                                        <?= esc(t_field($page['title'])) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($datasets !== []): ?>
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.sitemap.datasets')) ?></h2>
                        <ul class="mt-4 space-y-2 text-sm" role="list">
                            <?php foreach ($datasets as $dataset): ?>
                                <li><a href="<?= esc(locale_url('statistics/' . $dataset['slug'])) ?>" class="hover:text-brand-red"><?= esc(t_field($dataset['title'])) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <p class="mt-12 text-sm">
            <a href="<?= esc(base_url('sitemap.xml')) ?>" class="font-semibold text-brand-red hover:underline"><?= esc(lang('Site.sitemap.xml')) ?> &rarr;</a>
        </p>
    </div>
</section>

<?= $this->endSection() ?>
