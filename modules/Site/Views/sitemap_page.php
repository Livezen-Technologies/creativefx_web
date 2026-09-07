<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The human sitemap.
 *
 * Where somebody lands from a 404, what a screen-reader user reaches for when
 * a menu is fighting them, and a single page of internal links a crawler can
 * follow in one visit.
 *
 * @var list<array> $categories
 * @var list<array> $pages
 */
helper(['norlanka', 'url']);

$byParent = [];
foreach ($categories as $category) {
    $byParent[(int) ($category['parent_id'] ?? 0)][] = $category;
}
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'heading' => lang('Site.sitemap.title'),
    'intro'   => lang('Site.sitemap.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x grid gap-12 py-14 sm:grid-cols-2 lg:grid-cols-3">

    <section>
        <h2 class="section-title"><?= esc(lang('Catalog.courses.title')) ?></h2>
        <ul class="mt-4 space-y-2 text-sm">
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('courses')) ?>"><?= esc(lang('Catalog.courses.all')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('schedule')) ?>"><?= esc(lang('Catalog.schedule.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('on-demand')) ?>"><?= esc(lang('Catalog.ondemand.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('certificates')) ?>"><?= esc(lang('Catalog.bundles.certificates_title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('bootcamps')) ?>"><?= esc(lang('Catalog.bundles.bootcamps_title')) ?></a></li>
        </ul>
    </section>

    <?php foreach ($byParent[0] ?? [] as $top): ?>
        <section>
            <h2 class="section-title">
                <a class="hover:text-brand-red" href="<?= esc(locale_url('courses/' . $top['slug'])) ?>"><?= esc(t_field($top['name'])) ?></a>
            </h2>
            <ul class="mt-4 space-y-2 text-sm">
                <?php foreach ($byParent[(int) $top['id']] ?? [] as $child): ?>
                    <li>
                        <a class="hover:text-brand-red" href="<?= esc(locale_url('courses/' . $child['slug'])) ?>">
                            <?= esc(t_field($child['name'])) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>

    <section>
        <h2 class="section-title"><?= esc(lang('Site.footer.company')) ?></h2>
        <ul class="mt-4 space-y-2 text-sm">
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('corporate')) ?>"><?= esc(lang('Site.home.corporate_heading')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('instructors')) ?>"><?= esc(lang('Catalog.instructors.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('locations')) ?>"><?= esc(lang('Catalog.locations.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('reviews')) ?>"><?= esc(lang('Catalog.reviews.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('blog')) ?>"><?= esc(lang('Site.news.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('resources')) ?>"><?= esc(lang('Catalog.resources.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('webinars')) ?>"><?= esc(lang('Catalog.webinars.title')) ?></a></li>
            <li><a class="hover:text-brand-red" href="<?= esc(locale_url('careers')) ?>"><?= esc(lang('Site.careers.title')) ?></a></li>
        </ul>
    </section>

    <?php if ($pages !== []): ?>
        <section>
            <h2 class="section-title"><?= esc(lang('Site.footer.legal')) ?></h2>
            <ul class="mt-4 space-y-2 text-sm">
                <?php foreach ($pages as $page): ?>
                    <li>
                        <a class="hover:text-brand-red" href="<?= esc(locale_url($page['slug'])) ?>"><?= esc(t_field($page['title'])) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
