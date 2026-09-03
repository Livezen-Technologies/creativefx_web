<?php
helper(['norlanka', 'url', 'form']);
$this->extend('Modules\Core\Views\layouts\main');

$errors    = session()->getFlashdata('errors') ?? [];
$reference = session()->getFlashdata('reference');
$districts = ['Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota', 'Ratnapura', 'Kegalle', 'Badulla', 'Monaragala', 'Kurunegala', 'Puttalam'];
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.feedback.title')]],
    'eyebrow' => lang('Site.nav.contact'),
    'heading' => lang('Site.feedback.title'),
    'intro'   => lang('Site.feedback.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x grid max-w-5xl gap-12 lg:grid-cols-[minmax(0,1fr)_16rem]">
        <div class="min-w-0">
            <?php if ($reference): ?>
                <div role="status" class="rounded-2xl border border-brand-red/50 bg-brand-red/[0.08] p-6">
                    <h2 class="text-lg font-semibold"><?= esc(lang('Site.feedback.ok_title')) ?></h2>
                    <p class="mt-2 font-mono text-2xl font-bold text-brand-red"><?= esc($reference) ?></p>
                    <p class="mt-3 text-sm leading-relaxed text-white/80"><?= esc(lang('Site.feedback.ok_body')) ?></p>
                    <a href="<?= esc(locale_url('feedback/track?reference=' . rawurlencode($reference))) ?>"
                       class="mt-4 inline-block text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.feedback.track_title')) ?> &rarr;</a>
                </div>
            <?php endif; ?>

            <?php if ($msg = session()->getFlashdata('error')): ?>
                <p role="alert" class="<?= $reference ? 'mt-6' : '' ?> rounded-lg border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
            <?php endif; ?>

            <form method="post" action="<?= esc(locale_url('feedback')) ?>" enctype="multipart/form-data"
                  class="<?= $reference ? 'mt-10' : '' ?> space-y-5"
                  x-data="{ left: <?= (int) $maxMessage ?> }">
                <?= csrf_field() ?>
                <input type="hidden" name="page_url" value="<?= esc(session()->get('_ci_previous_url') ?? '', 'attr') ?>">
                <div class="hidden" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <fieldset>
                    <legend class="field-label"><?= esc(lang('Site.feedback.kind')) ?></legend>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <?php foreach ($kinds as $kind): ?>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="kind" value="<?= esc($kind, 'attr') ?>"
                                       <?= (old('kind') ?: 'feedback') === $kind ? 'checked' : '' ?>
                                       class="h-4 w-4 border-line text-brand-red focus-visible:ring-2 focus-visible:ring-brand-red">
                                <span><?= esc(lang('Site.feedback.kind_' . $kind)) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="grid gap-5 sm:grid-cols-2">
                    <label>
                        <span class="field-label"><?= esc(lang('Site.feedback.name')) ?> *</span>
                        <input type="text" name="name" required maxlength="191" class="field" autocomplete="name" value="<?= esc(old('name'), 'attr') ?>">
                        <?php if (isset($errors['name'])): ?><span class="field-error"><?= esc($errors['name']) ?></span><?php endif; ?>
                    </label>
                    <label>
                        <span class="field-label"><?= esc(lang('Site.feedback.district')) ?></span>
                        <select name="district" class="field">
                            <option value=""></option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= esc($district, 'attr') ?>" <?= old('district') === $district ? 'selected' : '' ?>><?= esc($district) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span class="field-label"><?= esc(lang('Site.feedback.email')) ?></span>
                        <input type="email" name="email" maxlength="128" class="field" autocomplete="email" value="<?= esc(old('email'), 'attr') ?>">
                        <?php if (isset($errors['email'])): ?><span class="field-error"><?= esc($errors['email']) ?></span><?php endif; ?>
                    </label>
                    <label>
                        <span class="field-label"><?= esc(lang('Site.feedback.phone')) ?></span>
                        <input type="tel" name="phone" maxlength="64" class="field" autocomplete="tel" value="<?= esc(old('phone'), 'attr') ?>">
                    </label>
                </div>
                <p class="text-xs text-white/55"><?= esc(lang('Site.feedback.contact_note')) ?></p>

                <label class="block">
                    <span class="field-label"><?= esc(lang('Site.feedback.subject')) ?> *</span>
                    <input type="text" name="subject" required maxlength="255" class="field" value="<?= esc(old('subject'), 'attr') ?>">
                    <?php if (isset($errors['subject'])): ?><span class="field-error"><?= esc($errors['subject']) ?></span><?php endif; ?>
                </label>

                <label class="block">
                    <span class="field-label"><?= esc(lang('Site.feedback.message')) ?> *</span>
                    <?php // The character limit Clause 3.9 J.a.iii asks for, and a
                          // live count so it is not discovered by being cut off. ?>
                    <textarea name="message" required rows="7" maxlength="<?= (int) $maxMessage ?>" class="field"
                              @input="left = <?= (int) $maxMessage ?> - $el.value.length"><?= esc(old('message')) ?></textarea>
                    <span class="mt-1.5 block text-xs text-white/50" x-text="left + ' <?= esc(mb_substr(lang('Site.feedback.chars_left', ['']), 1), 'attr') ?>'">
                        <?= esc(lang('Site.feedback.chars_left', [$maxMessage])) ?>
                    </span>
                    <?php if (isset($errors['message'])): ?><span class="field-error"><?= esc($errors['message']) ?></span><?php endif; ?>
                </label>

                <label class="block">
                    <span class="field-label"><?= esc(lang('Site.feedback.attachment')) ?></span>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="field file:mr-3 file:rounded-full file:border-0 file:bg-brand-red/10 file:px-4 file:py-1.5 file:text-xs file:font-semibold file:text-brand-red">
                </label>

                <button type="submit" class="btn-brand"><?= esc(lang('Site.feedback.submit')) ?></button>
            </form>
        </div>

        <aside>
            <div class="rounded-2xl border border-line bg-surface p-6">
                <h2 class="text-sm font-semibold"><?= esc(lang('Site.feedback.track_title')) ?></h2>
                <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.feedback.track_intro')) ?></p>
                <form method="get" action="<?= esc(locale_url('feedback/track')) ?>" class="mt-4 space-y-3">
                    <label class="block">
                        <span class="sr-only"><?= esc(lang('Site.feedback.track_label')) ?></span>
                        <input type="text" name="reference" class="field font-mono" placeholder="FBK-0000-XXXXXX">
                    </label>
                    <button type="submit" class="btn-brand w-full"><?= esc(lang('Site.feedback.track_go')) ?></button>
                </form>
            </div>
        </aside>
    </div>
</section>

<?= $this->endSection() ?>
