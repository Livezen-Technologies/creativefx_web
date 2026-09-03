<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.services.title')]],
    'eyebrow' => lang('Site.nav.services'),
    'heading' => lang('Site.services.title'),
    'intro'   => lang('Site.services.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x">
        <?php // Filters submit normally: a filtered view then has its own
              // address, which a regional office can send to somebody and a
              // smallholder can bookmark. A client-side filter over a
              // pre-rendered list gives neither. ?>
        <form method="get" class="flex flex-wrap items-end gap-3" role="search">
            <label class="min-w-[14rem] flex-1">
                <span class="field-label"><?= esc(lang('Site.search.label')) ?></span>
                <input type="search" name="q" value="<?= esc($query, 'attr') ?>" class="field"
                       placeholder="<?= esc(lang('Site.services.title'), 'attr') ?>">
            </label>
            <label class="min-w-[12rem]">
                <span class="field-label"><?= esc(lang('Site.services.filter_area')) ?></span>
                <select name="area" class="field">
                    <option value=""><?= esc(lang('Site.services.all_areas')) ?></option>
                    <?php foreach (\Modules\Tshda\Controllers\Services::AREAS as $a): ?>
                        <option value="<?= esc($a, 'attr') ?>" <?= $area === $a ? 'selected' : '' ?>><?= esc(lang('Site.services.area_' . $a)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="min-w-[12rem]">
                <span class="field-label"><?= esc(lang('Site.services.filter_for')) ?></span>
                <select name="for" class="field">
                    <option value=""><?= esc(lang('Site.services.all_audiences')) ?></option>
                    <?php foreach (\Modules\Tshda\Controllers\Services::AUDIENCES as $a): ?>
                        <option value="<?= esc($a, 'attr') ?>" <?= $audience === $a ? 'selected' : '' ?>><?= esc(lang('Site.audience.' . $a)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn-brand"><?= esc(lang('Site.directory.filter')) ?></button>
            <?php if ($query !== '' || $area !== '' || $audience !== ''): ?>
                <a href="<?= esc(locale_url('services')) ?>" class="pb-3 text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.directory.clear')) ?></a>
            <?php endif; ?>
        </form>

        <?php if ($services === []): ?>
            <p class="mt-10 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.services.none')) ?></p>
        <?php else: ?>
            <p class="mt-8 text-sm text-white/60"><?= esc(lang('Site.services.count', [count($services)])) ?></p>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                <?php foreach ($services as $service): ?>
                    <li>
                        <a href="<?= esc(locale_url('services/' . $service['slug'])) ?>"
                           class="group flex h-full flex-col rounded-2xl border border-line bg-surface p-6 transition hover:border-brand-red focus-visible:border-brand-red">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-base font-semibold leading-snug group-hover:text-brand-red"><?= esc(t_field($service['title'])) ?></h2>
                                <span class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider <?= (int) $service['window_open'] === 1 ? 'bg-brand-red/10 text-brand-red' : 'bg-white/10 text-white/50' ?>">
                                    <?= (int) $service['window_open'] === 1 ? esc(lang('Site.services.open')) : esc(lang('Site.services.closed')) ?>
                                </span>
                            </div>
                            <p class="mt-3 flex-1 text-sm leading-relaxed text-white/70"><?= esc(mb_substr(t_field($service['summary']), 0, 180)) ?></p>
                            <span class="mt-4 text-sm font-semibold text-brand-red"><?= esc(lang('Site.services.details')) ?> &rarr;</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
