<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$process   = json_decode((string) $service['process'], true);
$documents = json_decode((string) $service['documents'], true);
$process   = is_array($process) ? $process : [];
$documents = is_array($documents) ? $documents : [];
$isOpen    = (int) $service['window_open'] === 1;
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [
        ['label' => lang('Site.services.title'), 'url' => locale_url('services')],
        ['label' => t_field($service['title'])],
    ],
    'eyebrow' => lang('Site.services.area_' . $service['area']),
    'heading' => t_field($service['title']),
    'intro'   => t_field($service['summary']),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-10">
            <?php // The status of the application window, first, because it is
                  // the thing that decides whether the rest of the page is
                  // useful today. ?>
            <div class="rounded-2xl border p-5 <?= $isOpen ? 'border-brand-red/40 bg-brand-red/[0.06]' : 'border-line bg-surface' ?>">
                <p class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.services.window')) ?></p>
                <p class="mt-1.5 text-sm font-medium leading-relaxed">
                    <?= esc($isOpen ? lang('Site.services.window_open') : lang('Site.services.window_closed')) ?>
                </p>
            </div>

            <?php if (t_field($service['eligibility']) !== ''): ?>
                <div>
                    <h2 class="text-xl font-semibold"><?= esc(lang('Site.services.eligibility')) ?></h2>
                    <p class="mt-3 leading-relaxed text-white/75"><?= esc(t_field($service['eligibility'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($process !== []): ?>
                <div>
                    <h2 class="text-xl font-semibold"><?= esc(lang('Site.services.process')) ?></h2>
                    <ol class="mt-4 space-y-4">
                        <?php foreach ($process as $i => $step): ?>
                            <li class="flex gap-4">
                                <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-red/10 text-xs font-bold text-brand-red"><?= $i + 1 ?></span>
                                <span class="pt-0.5 leading-relaxed text-white/75"><?= esc(t_field($step)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>

            <?php if ($documents !== []): ?>
                <div>
                    <h2 class="text-xl font-semibold"><?= esc(lang('Site.services.documents')) ?></h2>
                    <ul class="mt-4 space-y-2.5" role="list">
                        <?php foreach ($documents as $doc): ?>
                            <li class="flex gap-3 leading-relaxed text-white/75">
                                <span aria-hidden="true" class="mt-[0.55rem] h-1.5 w-1.5 shrink-0 rounded-full bg-brand-red"></span>
                                <span><?= esc(t_field($doc)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($related !== []): ?>
                <div>
                    <h2 class="text-xl font-semibold"><?= esc(lang('Site.services.related')) ?></h2>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2" role="list">
                        <?php foreach ($related as $other): ?>
                            <li>
                                <a href="<?= esc(locale_url('services/' . $other['slug'])) ?>"
                                   class="block rounded-xl border border-line bg-surface px-4 py-3 text-sm font-medium transition hover:border-brand-red">
                                    <?= esc(t_field($other['title'])) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <aside class="space-y-6">
            <dl class="rounded-2xl border border-line bg-surface p-6 text-sm">
                <?php foreach ([
                    'fee'           => 'Site.services.fee',
                    'duration'      => 'Site.services.duration',
                    'division'      => 'Site.services.division',
                    'contact_point' => 'Site.services.contact_point',
                ] as $field => $key): ?>
                    <?php $value = t_field($service[$field]); if ($value === '') { continue; } ?>
                    <dt class="mt-5 text-xs font-semibold uppercase tracking-wider text-white/50 first:mt-0"><?= esc(lang($key)) ?></dt>
                    <dd class="mt-1 leading-relaxed text-white/80"><?= esc($value) ?></dd>
                <?php endforeach; ?>
            </dl>

            <div class="flex flex-col gap-3">
                <?php if (! empty($service['form_url'])): ?>
                    <a href="<?= esc($service['form_url'], 'attr') ?>" class="btn-brand text-center"><?= esc(lang('Site.services.form')) ?></a>
                <?php else: ?>
                    <a href="<?= esc(locale_url('downloads?category=application-forms')) ?>" class="btn-brand text-center"><?= esc(lang('Site.services.form')) ?></a>
                <?php endif; ?>
                <a href="<?= esc(locale_url('directory')) ?>" class="rounded-full border border-line px-6 py-3 text-center text-sm font-semibold transition hover:border-brand-red"><?= esc(lang('Site.nav.directory')) ?></a>
                <a href="<?= esc(locale_url('feedback')) ?>" class="rounded-full border border-line px-6 py-3 text-center text-sm font-semibold transition hover:border-brand-red"><?= esc(lang('Site.nav.feedback')) ?></a>
            </div>
        </aside>
    </div>
</section>

<?= $this->endSection() ?>
