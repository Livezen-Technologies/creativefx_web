<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.hantana.title')]],
    'eyebrow' => lang('Site.nav.services'),
    'heading' => lang('Site.hantana.title'),
    'intro'   => lang('Site.hantana.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-5xl">
        <h2 class="text-xl font-semibold"><?= esc(lang('Site.hantana.calendar')) ?></h2>

        <?php if ($programmes === []): ?>
            <p class="mt-5 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.hantana.none')) ?></p>
        <?php else: ?>
            <ul class="mt-6 space-y-4" role="list">
                <?php foreach ($programmes as $programme):
                    $open  = \Modules\Tshda\Models\ProgrammeModel::isOpen($programme);
                    $left  = \Modules\Tshda\Models\ProgrammeModel::seatsLeft($programme);
                ?>
                    <li class="flex flex-wrap items-start gap-5 rounded-2xl border border-line bg-surface p-6">
                        <?php if (! empty($programme['starts_on'])): ?>
                            <div class="w-16 shrink-0 rounded-xl border border-line bg-brand-black py-3 text-center">
                                <span class="block text-2xl font-bold leading-none"><?= esc(date('j', strtotime((string) $programme['starts_on']))) ?></span>
                                <span class="mt-1 block text-[10px] font-semibold uppercase tracking-wider text-white/60"><?= esc(date('M Y', strtotime((string) $programme['starts_on']))) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-semibold leading-snug">
                                <a href="<?= esc(locale_url('hantana/' . $programme['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($programme['title'])) ?></a>
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(t_field($programme['summary'])) ?></p>
                            <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/55">
                                <span><?= esc((int) $programme['residential'] === 1 ? lang('Site.hantana.residential') : lang('Site.hantana.non_residential')) ?></span>
                                <?php if (! empty($programme['closes_on'])): ?>
                                    <span><?= esc(lang('Site.hantana.applications_close')) ?> <?= esc(date('j M Y', strtotime((string) $programme['closes_on']))) ?></span>
                                <?php endif; ?>
                                <?php if ($left !== null): ?>
                                    <span class="<?= $left === 0 ? 'font-semibold text-brand-red' : '' ?>"><?= esc($left === 0 ? lang('Site.hantana.full') : lang('Site.hantana.seats_left', [$left])) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <a href="<?= esc(locale_url('hantana/' . $programme['slug'])) ?>"
                           class="<?= $open ? 'btn-brand' : 'rounded-full border border-line text-white/60' ?> shrink-0 px-5 py-2 text-xs font-semibold uppercase tracking-widest">
                            <?= esc($open ? lang('Site.hantana.apply') : lang('Site.hantana.closed')) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
