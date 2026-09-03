<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.directory.title')]],
    'eyebrow' => lang('Site.nav.about'),
    'heading' => lang('Site.directory.title'),
    'intro'   => lang('Site.directory.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x">
        <form method="get" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end" role="search">
            <label class="lg:col-span-2">
                <span class="field-label"><?= esc(lang('Site.directory.search')) ?></span>
                <input type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" class="field">
            </label>
            <label>
                <span class="field-label"><?= esc(lang('Site.directory.district')) ?></span>
                <select name="district" class="field">
                    <option value=""><?= esc(lang('Site.directory.all')) ?></option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?= esc($district, 'attr') ?>" <?= $filters['district'] === $district ? 'selected' : '' ?>><?= esc($district) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="field-label"><?= esc(lang('Site.directory.division')) ?></span>
                <select name="division" class="field">
                    <option value=""><?= esc(lang('Site.directory.all')) ?></option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?= esc($division, 'attr') ?>" <?= $filters['division'] === $division ? 'selected' : '' ?>><?= esc($division) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="flex items-center gap-4">
                <button type="submit" class="btn-brand"><?= esc(lang('Site.directory.filter')) ?></button>
                <?php if (array_filter($filters)): ?>
                    <a href="<?= esc(locale_url('directory')) ?>" class="text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.directory.clear')) ?></a>
                <?php endif; ?>
            </div>
        </form>

        <?php // Officers first: that is what the page is for. The office list
              // follows, for the visitor who wants a place rather than a person. ?>
        <h2 class="mt-12 text-xl font-semibold"><?= esc(lang('Site.directory.people')) ?></h2>
        <?php if ($people === []): ?>
            <p class="mt-4 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.directory.none')) ?></p>
        <?php else: ?>
            <p class="mt-2 text-sm text-white/60"><?= esc(lang('Site.directory.count', [count($people)])) ?></p>
            <div class="mt-5 overflow-x-auto rounded-2xl border border-line">
                <table class="w-full min-w-[52rem] border-collapse text-left text-sm">
                    <caption class="sr-only"><?= esc(lang('Site.directory.title')) ?></caption>
                    <thead class="bg-surface">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.directory.people')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.directory.division')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.directory.subject')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.directory.office')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.directory.phone')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($people as $person): ?>
                            <tr class="border-t border-line align-top">
                                <td class="px-4 py-3">
                                    <p class="font-medium"><?= esc($person['name']) ?></p>
                                    <p class="text-xs text-white/60"><?= esc(t_field($person['designation'])) ?></p>
                                </td>
                                <td class="px-4 py-3 text-white/70"><?= esc(t_field($person['division'])) ?></td>
                                <td class="px-4 py-3 text-white/70"><?= esc(t_field($person['subject_area'])) ?></td>
                                <td class="px-4 py-3 text-white/70"><?= esc(t_field($person['office_name'] ?? '')) ?></td>
                                <td class="px-4 py-3">
                                    <?php $tel = $person['phone'] ?: $person['mobile']; ?>
                                    <?php if ($tel): ?>
                                        <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $tel), 'attr') ?>" class="text-brand-red hover:underline"><?= esc($tel) ?></a>
                                    <?php endif; ?>
                                    <?php if (! empty($person['email'])): ?>
                                        <a href="mailto:<?= esc($person['email'], 'attr') ?>" class="block break-words text-xs text-white/60 hover:text-brand-red"><?= esc($person['email']) ?></a>
                                    <?php endif; ?>
                                    <?php if (! $tel && empty($person['email'])): ?>
                                        <span class="text-white/40"><?= esc(lang('Site.directory.tbc')) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="mt-14 text-xl font-semibold"><?= esc(lang('Site.directory.offices')) ?></h2>
        <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
            <?php foreach ($offices as $office): ?>
                <li class="rounded-2xl border border-line bg-surface p-5">
                    <h3 class="text-sm font-semibold leading-snug"><?= esc(t_field($office['name'])) ?></h3>
                    <?php if (! empty($office['district'])): ?>
                        <p class="mt-1 text-xs uppercase tracking-wider text-white/50"><?= esc($office['district']) ?><?= ! empty($office['province']) ? ' · ' . esc($office['province']) : '' ?></p>
                    <?php endif; ?>
                    <?php if ($addr = t_field($office['address'])): ?>
                        <p class="mt-3 text-sm leading-relaxed text-white/70"><?= esc($addr) ?></p>
                    <?php endif; ?>
                    <?php if (! empty($office['phone'])): ?>
                        <p class="mt-2 text-sm"><a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $office['phone']), 'attr') ?>" class="text-brand-red hover:underline"><?= esc($office['phone']) ?></a></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<?= $this->endSection() ?>
