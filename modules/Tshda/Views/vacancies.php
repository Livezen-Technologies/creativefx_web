<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.vacancies.title')]],
    'eyebrow' => lang('Site.nav.vacancies'),
    'heading' => lang('Site.vacancies.title'),
    'intro'   => lang('Site.careers.meta'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-4xl">
        <?php if ($jobs === []): ?>
            <p class="rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.careers.none')) ?></p>
        <?php else: ?>
            <ul class="divide-y divide-line rounded-2xl border border-line" role="list">
                <?php foreach ($jobs as $job): ?>
                    <li class="p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold leading-snug">
                                    <a href="<?= esc(locale_url('vacancies/' . $job['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($job['title'])) ?></a>
                                </h2>
                                <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/55">
                                    <?php if (! empty($job['department'])): ?><span><?= esc($job['department']) ?></span><?php endif; ?>
                                    <?php if (! empty($job['location'])): ?><span><?= esc($job['location']) ?></span><?php endif; ?>
                                    <?php if (! empty($job['closes_at'])): ?>
                                        <span class="font-semibold text-brand-red"><?= esc(lang('Site.careers.deadline')) ?> <?= esc(date('j M Y', strtotime((string) $job['closes_at']))) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="<?= esc(locale_url('vacancies/' . $job['slug'])) ?>" class="btn-brand shrink-0 px-5 py-2 text-xs"><?= esc(lang('Site.careers.view_apply')) ?></a>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-white/70"><?= esc(mb_substr(strip_tags(t_field($job['description'])), 0, 240)) ?>…</p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="mt-10 rounded-2xl border border-line bg-surface p-6">
            <h2 class="text-lg font-semibold"><?= esc(lang('Site.alerts.topic_vacancies')) ?></h2>
            <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.alerts.intro')) ?></p>
            <a href="<?= esc(locale_url('announcements')) ?>" class="btn-brand mt-5 inline-flex"><?= esc(lang('Site.alerts.subscribe')) ?></a>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
