<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$key = $mode === 'confirm'
    ? ($ok ? 'Site.alerts.confirm_ok' : 'Site.alerts.confirm_bad')
    : ($ok ? 'Site.alerts.unsub_ok'  : 'Site.alerts.unsub_bad');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang($mode === 'confirm' ? 'Site.alerts.confirm_title' : 'Site.alerts.unsub_title')]],
    'eyebrow' => lang('Site.alerts.title'),
    'heading' => lang($mode === 'confirm' ? 'Site.alerts.confirm_title' : 'Site.alerts.unsub_title'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-2xl">
        <p role="status" class="rounded-2xl border p-6 leading-relaxed <?= $ok ? 'border-brand-red/40 bg-brand-red/[0.07]' : 'border-line bg-surface' ?>">
            <?= esc(lang($key)) ?>
        </p>
        <p class="mt-6">
            <a href="<?= esc(locale_url('')) ?>" class="font-semibold text-brand-red hover:underline">&larr; <?= esc(lang('Site.alerts.back_home')) ?></a>
        </p>
    </div>
</section>

<?= $this->endSection() ?>
