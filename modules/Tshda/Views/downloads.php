<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.downloads.title')]],
    'eyebrow' => lang('Site.nav.downloads'),
    'heading' => lang('Site.downloads.title'),
    'intro'   => lang('Site.downloads.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x grid gap-10 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.downloads.category')) ?></h2>
            <ul class="mt-4 space-y-1 text-sm" role="list">
                <li>
                    <a href="<?= esc(locale_url('downloads')) ?>"
                       class="block rounded-lg px-3 py-2 font-medium transition <?= $activeCategory === null ? 'bg-brand-red/10 text-brand-red' : 'hover:bg-surface' ?>">
                        <?= esc(lang('Site.downloads.all')) ?>
                    </a>
                </li>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="<?= esc(locale_url('downloads?category=' . $category['slug'])) ?>"
                           class="block rounded-lg px-3 py-2 font-medium transition <?= ($activeCategory['slug'] ?? '') === $category['slug'] ? 'bg-brand-red/10 text-brand-red' : 'hover:bg-surface' ?>">
                            <?= esc(t_field($category['name'])) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <div class="min-w-0">
            <form method="get" class="flex flex-wrap items-end gap-3" role="search">
                <?php if ($activeCategory !== null): ?>
                    <input type="hidden" name="category" value="<?= esc($activeCategory['slug'], 'attr') ?>">
                <?php endif; ?>
                <label class="min-w-[16rem] flex-1">
                    <span class="field-label"><?= esc(lang('Site.downloads.search')) ?></span>
                    <input type="search" name="q" value="<?= esc($query, 'attr') ?>" class="field">
                </label>
                <button type="submit" class="btn-brand"><?= esc(lang('Site.search.button')) ?></button>
            </form>

            <?php if ($documents === []): ?>
                <p class="mt-8 rounded-2xl border border-line bg-surface p-6 text-sm">
                    <?= esc($query !== '' ? lang('Site.downloads.none') : lang('Site.downloads.empty')) ?>
                </p>
            <?php else: ?>
                <p class="mt-6 text-sm text-white/60"><?= esc(lang('Site.downloads.count', [count($documents)])) ?></p>
                <ul class="mt-4 divide-y divide-line rounded-2xl border border-line" role="list">
                    <?php foreach ($documents as $document): ?>
                        <li class="flex flex-wrap items-start gap-4 p-5">
                            <span aria-hidden="true" class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-red/10 text-[10px] font-bold uppercase text-brand-red">
                                <?= esc(substr((string) ($document['file_type'] ?: 'file'), 0, 4)) ?>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-sm font-semibold leading-snug"><?= esc(t_field($document['title'])) ?></h2>
                                <?php if ($desc = t_field($document['description'])): ?>
                                    <p class="mt-1 text-sm leading-relaxed text-white/65"><?= esc($desc) ?></p>
                                <?php endif; ?>
                                <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/50">
                                    <?php if (! empty($document['published_at'])): ?>
                                        <span><?= esc(lang('Site.downloads.published')) ?> <?= esc(date('j M Y', strtotime((string) $document['published_at']))) ?></span>
                                    <?php endif; ?>
                                    <?php if (! empty($document['expires_at'])): ?>
                                        <span class="font-semibold text-brand-red"><?= esc(lang('Site.downloads.closes')) ?> <?= esc(date('j M Y', strtotime((string) $document['expires_at']))) ?></span>
                                    <?php endif; ?>
                                    <?php if ((int) $document['file_size'] > 0): ?>
                                        <span><?= esc(admin_bytes((int) $document['file_size'])) ?></span>
                                    <?php endif; ?>
                                    <span><?= esc(lang('Site.downloads.downloads', [(int) $document['download_count']])) ?></span>
                                </p>
                            </div>
                            <a href="<?= esc(locale_url('downloads/' . $document['slug'])) ?>" class="btn-brand shrink-0 px-5 py-2 text-xs">
                                <?= esc(lang('Site.downloads.get')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
