<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Open vacancies.
 *
 * @var list<array> $jobs   rows from JobModel::openJobs(), may be empty
 * @var list<array> $crumbs
 */
helper(['norlanka', 'url']);

$chipsFor = static function (array $job): array {
    return array_values(array_filter([
        $job['department'] ?? null,
        $job['location'] ?? ($job['country'] ?? null),
        $job['employment_type'] ?? null,
    ]));
};
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Site.careers.all'),
    'heading' => lang('Site.careers.title'),
    'crumbs'  => $crumbs ?? [],
], ['saveData' => false]) ?>

<div class="container-x py-12">
    <?php if ($jobs === []): ?>
        <?php // The empty state is the page's normal state between hires, not an
              // error, so it is styled as a statement rather than a warning. ?>
        <div class="rounded-2xl border border-line bg-surface p-8 text-center sm:p-12">
            <p class="text-lg text-white/70"><?= esc(lang('Site.careers.none')) ?></p>
            <a href="<?= esc(locale_url('contact')) ?>" class="btn-outline mt-6 inline-flex">
                <?= esc(lang('Site.nav.contact')) ?>
            </a>
        </div>
    <?php else: ?>
        <ul class="grid gap-5 sm:grid-cols-2">
            <?php foreach ($jobs as $job): ?>
                <?php
                $title = t_field(json_decode($job['title'] ?? '[]', true) ?: []);
                $chips = $chipsFor($job);
                $closes = ! empty($job['closes_at']) ? date('j M Y', strtotime((string) $job['closes_at'])) : null;
                ?>
                <li class="group relative flex flex-col rounded-2xl border border-line bg-surface p-6 transition hover:border-brand-red/40">
                    <h2 class="text-lg font-semibold leading-snug">
                        <a href="<?= esc(locale_url('careers/' . $job['slug'])) ?>" class="after:absolute after:inset-0 hover:text-brand-red">
                            <?= esc($title) ?>
                        </a>
                    </h2>

                    <?php if ($chips !== []): ?>
                        <p class="mt-3 flex flex-wrap gap-2 text-xs text-white/55">
                            <?php foreach ($chips as $chip): ?>
                                <span class="chip"><?= esc($chip) ?></span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($closes !== null): ?>
                        <p class="mt-auto pt-5 text-xs text-white/45">
                            <?= esc(lang('Site.careers.deadline')) ?>: <span class="text-white/70"><?= esc($closes) ?></span>
                        </p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
