<?php
helper(['norlanka', 'url', 'form']);
$this->extend('Modules\Core\Views\layouts\main');

$errors = session()->getFlashdata('errors') ?? [];
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [
        ['label' => lang('Site.officer.title'), 'url' => locale_url('field-officer')],
        ['label' => lang('Site.officer.dashboard')],
    ],
    'eyebrow' => lang('Site.nav.field_officer'),
    'heading' => lang('Site.officer.dashboard'),
    'intro'   => lang('Site.officer.welcome', [$officer['name'] ?? '']),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x grid gap-12 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="min-w-0 space-y-12">
            <?php if ($msg = session()->getFlashdata('officer_ok')): ?>
                <p role="status" class="rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
            <?php endif; ?>
            <?php if ($msg = session()->getFlashdata('error')): ?>
                <p role="alert" class="rounded-lg border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
            <?php endif; ?>

            <div>
                <h2 class="text-xl font-semibold"><?= esc(lang('Site.officer.new_submission')) ?></h2>
                <form method="post" action="<?= esc(locale_url('field-officer/submit')) ?>" enctype="multipart/form-data" class="mt-5 space-y-5">
                    <?= csrf_field() ?>
                    <label class="block">
                        <span class="field-label"><?= esc(lang('Site.officer.kind')) ?></span>
                        <select name="kind" class="field">
                            <?php foreach ($kinds as $value => $label): ?>
                                <option value="<?= esc($value, 'attr') ?>" <?= old('kind') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="field-label"><?= esc(lang('Site.officer.subject')) ?> *</span>
                        <input type="text" name="subject" required maxlength="255" class="field" value="<?= esc(old('subject'), 'attr') ?>">
                        <?php if (isset($errors['subject'])): ?><span class="field-error"><?= esc($errors['subject']) ?></span><?php endif; ?>
                    </label>
                    <label class="block">
                        <span class="field-label"><?= esc(lang('Site.officer.body')) ?> *</span>
                        <textarea name="body" required rows="7" maxlength="8000" class="field"><?= esc(old('body')) ?></textarea>
                        <?php if (isset($errors['body'])): ?><span class="field-error"><?= esc($errors['body']) ?></span><?php endif; ?>
                    </label>
                    <label class="block">
                        <span class="field-label"><?= esc(lang('Site.officer.attachment')) ?></span>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.xls,.xlsx,.csv,.doc,.docx"
                               class="field file:mr-3 file:rounded-full file:border-0 file:bg-brand-red/10 file:px-4 file:py-1.5 file:text-xs file:font-semibold file:text-brand-red">
                    </label>
                    <button type="submit" class="btn-brand"><?= esc(lang('Site.officer.submit')) ?></button>
                </form>
            </div>

            <div>
                <h2 class="text-xl font-semibold"><?= esc(lang('Site.officer.your_submissions')) ?></h2>
                <?php if ($submissions === []): ?>
                    <p class="mt-4 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.officer.no_submissions')) ?></p>
                <?php else: ?>
                    <div class="mt-4 overflow-x-auto rounded-2xl border border-line">
                        <table class="w-full min-w-[38rem] border-collapse text-left text-sm">
                            <thead class="bg-surface">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.officer.reference')) ?></th>
                                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.officer.subject')) ?></th>
                                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.feedback.status')) ?></th>
                                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.feedback.submitted_on')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $row): ?>
                                    <tr class="border-t border-line align-top">
                                        <td class="px-4 py-3 font-mono text-xs"><?= esc($row['reference']) ?></td>
                                        <td class="px-4 py-3">
                                            <p class="font-medium"><?= esc($row['subject']) ?></p>
                                            <p class="text-xs text-white/55"><?= esc($kinds[$row['kind']] ?? $row['kind']) ?></p>
                                            <?php if (! empty($row['attachment'])): ?>
                                                <a href="<?= esc(locale_url('field-officer/attachment/' . $row['reference'])) ?>" class="text-xs font-semibold text-brand-red hover:underline">
                                                    <?= esc(lang('Site.officer.download')) ?> &darr;
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full bg-brand-red/10 px-2.5 py-0.5 text-xs font-semibold text-brand-red"><?= esc(lang('Site.officer.status_' . $row['status'])) ?></span>
                                            <?php if (! empty($row['officer_note'])): ?>
                                                <p class="mt-1.5 text-xs leading-relaxed text-white/60"><?= esc($row['officer_note']) ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-white/70"><?= esc(date('j M Y', strtotime((string) $row['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-2xl border border-line bg-surface p-6 text-sm">
                <p class="font-semibold"><?= esc($officer['name'] ?? '') ?></p>
                <p class="mt-1 break-words text-white/60"><?= esc($officer['email'] ?? '') ?></p>
                <?php if ($office !== null): ?>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.officer.your_office')) ?></p>
                    <p class="mt-1 text-white/80"><?= esc(t_field($office['name'])) ?></p>
                <?php endif; ?>
                <a href="<?= esc(locale_url('field-officer/logout')) ?>" class="mt-5 inline-block text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.officer.sign_out')) ?></a>
            </div>

            <?php if ($forms !== []): ?>
                <div class="rounded-2xl border border-line bg-surface p-6">
                    <h2 class="text-sm font-semibold"><?= esc(lang('Site.officer.resources')) ?></h2>
                    <ul class="mt-3 space-y-2 text-sm" role="list">
                        <?php foreach ($forms as $doc): ?>
                            <li><a href="<?= esc(locale_url('downloads/' . $doc['slug'])) ?>" class="text-white/75 hover:text-brand-red"><?= esc(t_field($doc['title'])) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= esc(locale_url('downloads')) ?>" class="mt-3 inline-block text-xs font-semibold text-brand-red hover:underline"><?= esc(lang('Site.nav.downloads')) ?> &rarr;</a>
                </div>
            <?php endif; ?>

            <?php if ($programmes !== []): ?>
                <div class="rounded-2xl border border-line bg-surface p-6">
                    <h2 class="text-sm font-semibold"><?= esc(lang('Site.officer.upcoming_training')) ?></h2>
                    <ul class="mt-3 space-y-2 text-sm" role="list">
                        <?php foreach ($programmes as $programme): ?>
                            <li>
                                <a href="<?= esc(locale_url('hantana/' . $programme['slug'])) ?>" class="text-white/75 hover:text-brand-red"><?= esc(t_field($programme['title'])) ?></a>
                                <?php if (! empty($programme['starts_on'])): ?>
                                    <span class="block text-xs text-white/50"><?= esc(date('j M Y', strtotime((string) $programme['starts_on']))) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($services !== []): ?>
                <div class="rounded-2xl border border-line bg-surface p-6">
                    <h2 class="text-sm font-semibold"><?= esc(lang('Site.officer.services_ref')) ?></h2>
                    <ul class="mt-3 space-y-2 text-sm" role="list">
                        <?php foreach (array_slice($services, 0, 8) as $service): ?>
                            <li>
                                <a href="<?= esc(locale_url('services/' . $service['slug'])) ?>" class="text-white/75 hover:text-brand-red"><?= esc(t_field($service['title'])) ?></a>
                                <?php if ((int) $service['window_open'] !== 1): ?>
                                    <span class="ml-1 text-[10px] font-semibold uppercase tracking-wider text-white/45"><?= esc(lang('Site.services.closed')) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?= $this->endSection() ?>
