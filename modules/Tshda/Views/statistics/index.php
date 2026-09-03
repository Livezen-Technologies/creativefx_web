<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.statistics.title')]],
    'eyebrow' => lang('Site.nav.statistics'),
    'heading' => lang('Site.statistics.title'),
    'intro'   => lang('Site.statistics.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x">
        <ul class="grid gap-5 md:grid-cols-2 lg:grid-cols-3" role="list">
            <?php foreach ($datasets as $dataset): ?>
                <li class="flex flex-col rounded-2xl border border-line bg-surface p-6">
                    <h2 class="text-base font-semibold leading-snug">
                        <a href="<?= esc(locale_url('statistics/' . $dataset['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($dataset['title'])) ?></a>
                    </h2>
                    <p class="mt-3 flex-1 text-sm leading-relaxed text-white/70"><?= esc(t_field($dataset['description'])) ?></p>
                    <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-xs text-white/55">
                        <?php if (! empty($dataset['period'])): ?>
                            <div><dt class="inline font-semibold"><?= esc(lang('Site.statistics.period')) ?>:</dt> <dd class="inline"><?= esc($dataset['period']) ?></dd></div>
                        <?php endif; ?>
                        <?php if ($unit = t_field($dataset['unit'])): ?>
                            <div><dt class="inline font-semibold"><?= esc(lang('Site.statistics.unit')) ?>:</dt> <dd class="inline"><?= esc($unit) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold">
                        <a href="<?= esc(locale_url('statistics/' . $dataset['slug'])) ?>" class="text-brand-red hover:underline"><?= esc(lang('Site.services.details')) ?> &rarr;</a>
                        <a href="<?= esc(locale_url('statistics/' . $dataset['slug'] . '/csv')) ?>" class="text-white/60 hover:text-brand-red"><?= esc(lang('Site.statistics.download')) ?></a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<?= $this->endSection() ?>
