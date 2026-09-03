<?php
helper(['norlanka', 'url', 'form']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.officer.title')]],
    'eyebrow' => lang('Site.nav.field_officer'),
    'heading' => lang('Site.officer.title'),
    'intro'   => lang('Site.officer.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-md">
        <?php if ($msg = session()->getFlashdata('officer_error')): ?>
            <p role="alert" class="mb-5 rounded-lg border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('officer_ok')): ?>
            <p role="status" class="mb-5 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= esc(locale_url('field-officer/login')) ?>"
              class="space-y-5 rounded-2xl border border-line bg-surface p-6">
            <?= csrf_field() ?>
            <label class="block">
                <span class="field-label"><?= esc(lang('Site.officer.email')) ?></span>
                <input type="email" name="email" required autocomplete="username" class="field">
            </label>
            <label class="block">
                <span class="field-label"><?= esc(lang('Site.officer.password')) ?></span>
                <input type="password" name="password" required autocomplete="current-password" class="field">
            </label>
            <button type="submit" class="btn-brand w-full"><?= esc(lang('Site.officer.sign_in')) ?></button>
        </form>

        <p class="mt-5 text-xs leading-relaxed text-white/50"><?= esc(lang('Site.officer.security_note')) ?></p>
    </div>
</section>

<?= $this->endSection() ?>
