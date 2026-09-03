<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.contact.title')]],
    'eyebrow' => lang('Site.nav.contact'),
    'heading' => lang('Site.contact.title'),
    'intro'   => lang('Site.contact.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0">
            <?php if ($head !== null): ?>
                <h2 class="text-xl font-semibold"><?= esc(lang('Site.contact.head_office')) ?></h2>
                <div class="mt-4 rounded-2xl border border-line bg-surface p-6">
                    <p class="font-semibold"><?= esc(t_field($head['name'])) ?></p>
                    <?php if ($addr = t_field($head['address'])): ?>
                        <p class="mt-2 leading-relaxed text-white/75"><?= esc($addr) ?></p>
                    <?php endif; ?>
                    <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                        <?php if (! empty($head['phone']) || $phone = setting('phone', '', 'contact')): ?>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.contact.phone')) ?></dt>
                                <dd class="mt-1"><a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $head['phone'] ?: setting('phone', '', 'contact')), 'attr') ?>" class="text-brand-red hover:underline"><?= esc($head['phone'] ?: setting('phone', '', 'contact')) ?></a></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($mail = ($head['email'] ?: setting('email', '', 'contact'))): ?>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.contact.email')) ?></dt>
                                <dd class="mt-1"><a href="mailto:<?= esc($mail, 'attr') ?>" class="break-words text-brand-red hover:underline"><?= esc($mail) ?></a></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($hours = setting('hours', '', 'contact')): ?>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.contact.hours')) ?></dt>
                                <dd class="mt-1 text-white/80"><?= esc($hours) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>

            <?php // The map (Clause 3.9 J.a.ii). OpenStreetMap in an iframe rather
                  // than Google's JavaScript API: no key to leak or expire, no
                  // third-party script on a government page, and it still shows
                  // the offices with directions a click away. The list beneath is
                  // the accessible equivalent — a map alone is unusable to a
                  // screen reader, and to anyone the iframe fails to load for. ?>
            <?php if ($mappable !== []): ?>
                <?php
                $lats = array_map(static fn ($o) => (float) $o['latitude'], $mappable);
                $lngs = array_map(static fn ($o) => (float) $o['longitude'], $mappable);
                $bbox = sprintf('%.4f,%.4f,%.4f,%.4f', min($lngs) - 0.4, min($lats) - 0.3, max($lngs) + 0.4, max($lats) + 0.3);
                ?>
                <h2 class="mt-12 text-xl font-semibold"><?= esc(lang('Site.contact.map')) ?></h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-line">
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox=<?= esc($bbox, 'attr') ?>&amp;layer=mapnik"
                        title="<?= esc(lang('Site.contact.map'), 'attr') ?>"
                        class="h-[26rem] w-full border-0" loading="lazy"></iframe>
                </div>
                <p class="mt-2 text-xs text-white/50"><?= esc(lang('Site.contact.map_note')) ?></p>
            <?php endif; ?>

            <h2 class="mt-12 text-xl font-semibold"><?= esc(lang('Site.contact.offices')) ?></h2>
            <ul class="mt-5 grid gap-4 sm:grid-cols-2" role="list">
                <?php foreach ($offices as $office): ?>
                    <li class="rounded-2xl border border-line bg-surface p-5">
                        <h3 class="text-sm font-semibold leading-snug"><?= esc(t_field($office['name'])) ?></h3>
                        <?php if ($addr = t_field($office['address'])): ?>
                            <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc($addr) ?></p>
                        <?php endif; ?>
                        <?php if (! empty($office['phone'])): ?>
                            <p class="mt-2 text-sm"><a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $office['phone']), 'attr') ?>" class="text-brand-red hover:underline"><?= esc($office['phone']) ?></a></p>
                        <?php endif; ?>
                        <?php if (! empty($office['latitude']) && ! empty($office['longitude'])): ?>
                            <p class="mt-2">
                                <a href="https://www.openstreetmap.org/?mlat=<?= esc((string) $office['latitude'], 'attr') ?>&amp;mlon=<?= esc((string) $office['longitude'], 'attr') ?>#map=15/<?= esc((string) $office['latitude'], 'attr') ?>/<?= esc((string) $office['longitude'], 'attr') ?>"
                                   target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-white/60 hover:text-brand-red">
                                    <?= esc(lang('Site.contact.map')) ?> &#8599;
                                </a>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <aside class="space-y-6">
            <div class="rounded-2xl border border-line bg-surface p-6">
                <h2 class="text-sm font-semibold"><?= esc(lang('Site.nav.feedback')) ?></h2>
                <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.contact.feedback_cta')) ?></p>
                <a href="<?= esc(locale_url('feedback')) ?>" class="btn-brand mt-4 flex justify-center"><?= esc(lang('Site.feedback.submit')) ?></a>
            </div>
            <div class="rounded-2xl border border-line bg-surface p-6">
                <h2 class="text-sm font-semibold"><?= esc(lang('Site.contact.senior')) ?></h2>
                <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.directory.intro')) ?></p>
                <a href="<?= esc(locale_url('directory')) ?>" class="mt-4 inline-block text-sm font-semibold text-brand-red hover:underline"><?= esc(lang('Site.nav.directory')) ?> &rarr;</a>
            </div>
        </aside>
    </div>
</section>

<?= $this->endSection() ?>
