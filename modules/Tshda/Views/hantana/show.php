<?php
helper(['norlanka', 'url', 'form']);
$this->extend('Modules\Core\Views\layouts\main');

$errors    = session()->getFlashdata('booking_errors') ?? [];
$reference = session()->getFlashdata('booking_ok');
$status    = session()->getFlashdata('booking_status');
$districts = ['Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota', 'Ratnapura', 'Kegalle', 'Badulla', 'Monaragala', 'Kurunegala', 'Puttalam'];
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [
        ['label' => lang('Site.hantana.title'), 'url' => locale_url('hantana')],
        ['label' => t_field($programme['title'])],
    ],
    'eyebrow' => lang('Site.nav.hantana'),
    'heading' => t_field($programme['title']),
    'intro'   => t_field($programme['summary']),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x grid max-w-5xl gap-12 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="min-w-0">
            <?php if ($reference): ?>
                <div role="status" class="rounded-2xl border border-brand-red/50 bg-brand-red/[0.08] p-6">
                    <h2 class="text-lg font-semibold"><?= esc(lang('Site.hantana.ok_title')) ?></h2>
                    <p class="mt-2 text-sm leading-relaxed text-white/80"><?= esc(lang('Site.hantana.ok_body')) ?></p>
                    <?php if ($status === 'waitlisted'): ?>
                        <p class="mt-2 text-sm font-medium leading-relaxed"><?= esc(lang('Site.hantana.waitlisted')) ?></p>
                    <?php endif; ?>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.hantana.reference')) ?></p>
                    <p class="mt-1 font-mono text-xl font-bold text-brand-red"><?= esc($reference) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($description = t_field($programme['description'])): ?>
                <div class="<?= $reference ? 'mt-10' : '' ?> leading-relaxed text-white/75"><?= rich_text($description) ?></div>
            <?php endif; ?>

            <h2 id="apply" class="mt-12 scroll-mt-28 text-xl font-semibold"><?= esc(lang('Site.hantana.form_title')) ?></h2>

            <?php if (! $open): ?>
                <p class="mt-4 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.hantana.err_closed')) ?></p>
            <?php else: ?>
                <?php if ($msg = session()->getFlashdata('booking_error')): ?>
                    <p role="alert" class="mt-4 rounded-lg border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= esc(locale_url('hantana/' . $programme['slug'] . '/apply')) ?>" class="mt-6 space-y-5">
                    <?= csrf_field() ?>
                    <div class="hidden" aria-hidden="true">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label>
                            <span class="field-label"><?= esc(lang('Site.hantana.name')) ?> *</span>
                            <input type="text" name="name" required maxlength="191" class="field" autocomplete="name" value="<?= esc(old('name'), 'attr') ?>">
                            <?php if (isset($errors['name'])): ?><span class="field-error"><?= esc($errors['name']) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span class="field-label"><?= esc(lang('Site.hantana.nic')) ?></span>
                            <input type="text" name="nic" maxlength="32" class="field" value="<?= esc(old('nic'), 'attr') ?>">
                        </label>
                        <label>
                            <span class="field-label"><?= esc(lang('Site.hantana.phone')) ?> *</span>
                            <input type="tel" name="phone" required maxlength="64" class="field" autocomplete="tel" value="<?= esc(old('phone'), 'attr') ?>">
                            <?php if (isset($errors['phone'])): ?><span class="field-error"><?= esc($errors['phone']) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span class="field-label"><?= esc(lang('Site.hantana.email')) ?></span>
                            <input type="email" name="email" maxlength="128" class="field" autocomplete="email" value="<?= esc(old('email'), 'attr') ?>">
                            <?php if (isset($errors['email'])): ?><span class="field-error"><?= esc($errors['email']) ?></span><?php endif; ?>
                        </label>
                        <label class="sm:col-span-2">
                            <span class="field-label"><?= esc(lang('Site.hantana.address')) ?></span>
                            <input type="text" name="address" maxlength="255" class="field" autocomplete="street-address" value="<?= esc(old('address'), 'attr') ?>">
                        </label>
                        <label>
                            <span class="field-label"><?= esc(lang('Site.hantana.district')) ?></span>
                            <select name="district" class="field">
                                <option value=""></option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= esc($district, 'attr') ?>" <?= old('district') === $district ? 'selected' : '' ?>><?= esc($district) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span class="field-label"><?= esc(lang('Site.hantana.society')) ?></span>
                            <input type="text" name="society" maxlength="191" class="field" value="<?= esc(old('society'), 'attr') ?>">
                        </label>
                        <label>
                            <span class="field-label"><?= esc(lang('Site.hantana.participants')) ?> *</span>
                            <input type="number" name="participants" min="1" max="50" required class="field" value="<?= esc(old('participants') ?: '1', 'attr') ?>">
                            <?php if (isset($errors['participants'])): ?><span class="field-error"><?= esc($errors['participants']) ?></span><?php endif; ?>
                        </label>
                        <?php if ((int) $programme['residential'] === 1): ?>
                            <label class="flex items-center gap-2.5 self-end pb-2.5 text-sm">
                                <input type="checkbox" name="residential" value="1" class="h-4 w-4 rounded border-line text-brand-red focus-visible:ring-2 focus-visible:ring-brand-red">
                                <span><?= esc(lang('Site.hantana.want_residential')) ?></span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <label class="block">
                        <span class="field-label"><?= esc(lang('Site.hantana.notes')) ?></span>
                        <textarea name="notes" rows="4" maxlength="1000" class="field"><?= esc(old('notes')) ?></textarea>
                    </label>

                    <button type="submit" class="btn-brand"><?= esc(lang('Site.hantana.submit')) ?></button>
                </form>
            <?php endif; ?>
        </div>

        <aside>
            <dl class="rounded-2xl border border-line bg-surface p-6 text-sm">
                <?php
                $rows = [
                    'Site.hantana.dates' => (! empty($programme['starts_on']))
                        ? date('j M Y', strtotime((string) $programme['starts_on'])) . (! empty($programme['ends_on']) && $programme['ends_on'] !== $programme['starts_on'] ? ' – ' . date('j M Y', strtotime((string) $programme['ends_on'])) : '')
                        : '',
                    'Site.hantana.applications_close' => ! empty($programme['closes_on']) ? date('j M Y', strtotime((string) $programme['closes_on'])) : '',
                    'Site.hantana.venue'    => t_field($programme['venue']),
                    'Site.hantana.audience' => t_field($programme['audience']),
                    'Site.hantana.fee'      => t_field($programme['fee']),
                    'Site.hantana.capacity' => (int) $programme['capacity'] > 0 ? (string) (int) $programme['capacity'] : '',
                ];
                foreach ($rows as $key => $value):
                    if ($value === '') { continue; }
                ?>
                    <dt class="mt-5 text-xs font-semibold uppercase tracking-wider text-white/50 first:mt-0"><?= esc(lang($key)) ?></dt>
                    <dd class="mt-1 leading-relaxed text-white/80"><?= esc($value) ?></dd>
                <?php endforeach; ?>
                <?php if ($seatsLeft !== null): ?>
                    <dt class="mt-5 text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.hantana.seats_left', [''])) ?></dt>
                    <dd class="mt-1 font-semibold <?= $seatsLeft === 0 ? 'text-brand-red' : '' ?>"><?= esc($seatsLeft === 0 ? lang('Site.hantana.full') : (string) $seatsLeft) ?></dd>
                <?php endif; ?>
            </dl>

            <?php if ($open): ?>
                <a href="#apply" class="btn-brand mt-5 flex justify-center"><?= esc(lang('Site.hantana.apply')) ?></a>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?= $this->endSection() ?>
