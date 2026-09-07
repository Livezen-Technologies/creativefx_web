<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Search results.
 *
 * The empty state does the most work here: somebody who searched and found
 * nothing is one click from leaving, so the page offers the catalogue and the
 * schedule rather than an apology.
 *
 * @var string $query
 * @var list<array{type:string,title:string,url:string,snippet:string}> $results
 * @var int    $total
 * @var int    $page
 * @var int    $perPage
 */
helper(['norlanka', 'url']);
$pages = (int) ceil($total / max(1, $perPage));
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => '',
    'heading' => lang('Site.search.title'),
    'intro'   => '',
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x py-12">
    <form method="get" action="<?= esc(locale_url('search')) ?>" class="flex max-w-2xl flex-wrap gap-2">
        <label class="sr-only" for="q"><?= esc(lang('Site.search.label')) ?></label>
        <input id="q" name="q" type="search" value="<?= esc($query, 'attr') ?>"
               class="field min-w-0 flex-1" placeholder="<?= esc(lang('Site.search.label'), 'attr') ?>" autofocus>
        <button type="submit" class="btn-brand"><?= esc(lang('Site.search.button')) ?></button>
    </form>

    <?php if ($query !== ''): ?>
        <p class="mt-6 text-sm text-white/55">
            <?= esc(lang('Site.search.results', [$total, $query])) ?>
        </p>
    <?php endif; ?>

    <?php if ($query !== '' && $results === []): ?>
        <div class="mt-8 max-w-2xl rounded-2xl border border-line bg-surface p-6">
            <p class="text-white/70"><?= esc(lang('Site.search.none')) ?></p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand"><?= esc(lang('Catalog.courses.all')) ?></a>
                <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Catalog.schedule.title')) ?></a>
            </div>
        </div>
    <?php elseif ($results !== []): ?>
        <ul class="mt-8 max-w-3xl divide-y divide-line">
            <?php foreach ($results as $result): ?>
                <li class="py-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-red"><?= esc($result['type']) ?></p>
                    <h2 class="mt-1 text-lg font-semibold">
                        <a href="<?= esc($result['url'], 'attr') ?>" class="hover:text-brand-red"><?= esc($result['title']) ?></a>
                    </h2>
                    <?php if ($result['snippet'] !== ''): ?>
                        <p class="mt-1 text-sm leading-relaxed text-white/65"><?= esc($result['snippet']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($pages > 1): ?>
            <nav class="mt-8 flex items-center gap-4 text-sm" aria-label="<?= esc(lang('Site.search.page_of', [$page, $pages]), 'attr') ?>">
                <?php if ($page > 1): ?>
                    <a rel="prev" class="btn-ghost px-5 py-2" href="<?= esc(locale_url('search') . '?q=' . rawurlencode($query) . '&page=' . ($page - 1)) ?>"><?= esc(lang('Site.search.prev')) ?></a>
                <?php endif; ?>
                <span class="text-white/55"><?= esc(lang('Site.search.page_of', [$page, $pages])) ?></span>
                <?php if ($page < $pages): ?>
                    <a rel="next" class="btn-ghost px-5 py-2" href="<?= esc(locale_url('search') . '?q=' . rawurlencode($query) . '&page=' . ($page + 1)) ?>"><?= esc(lang('Site.search.next')) ?></a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
