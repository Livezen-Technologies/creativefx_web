<?php
helper(['norlanka', 'url']);

/**
 * The services in one functional area, pulled from the catalogue rather than
 * copied into the page.
 *
 * A subject-area page that listed its own services would go stale the first
 * time a scheme changed, and would then disagree with the Services section
 * about the same scheme. Naming the area instead means both read one record.
 */
$area = (string) ($content['area'] ?? '');
$rows = [];

try {
    $rows = $area === ''
        ? model('Modules\Tshda\Models\ServiceModel')->live()->findAll()
        : model('Modules\Tshda\Models\ServiceModel')->byArea($area);
} catch (\Throwable $e) {
    // No table yet (a checkout before migrations have run): render nothing
    // rather than an error on a public page.
    $rows = [];
}

if ($rows === []) {
    return;
}
?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-2xl font-semibold sm:text-3xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($rows as $row): ?>
                <a href="<?= esc(locale_url('services/' . $row['slug'])) ?>"
                   class="group flex flex-col rounded-2xl border border-line bg-surface p-6 transition hover:border-brand-red focus-visible:border-brand-red"
                   data-gsap="reveal">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-base font-semibold leading-snug group-hover:text-brand-red"><?= esc(t_field($row['title'])) ?></h3>
                        <?php // The status of the application window is the one thing a
                              // smallholder is actually checking, so it is on the card
                              // rather than one click inside it. ?>
                        <span class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider <?= (int) $row['window_open'] === 1 ? 'bg-brand-red/10 text-brand-red' : 'bg-white/10 text-white/50' ?>">
                            <?= (int) $row['window_open'] === 1 ? esc(lang('Site.services.open')) : esc(lang('Site.services.closed')) ?>
                        </span>
                    </div>
                    <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-white/70"><?= esc(t_field($row['summary'])) ?></p>
                    <span class="mt-4 text-sm font-semibold text-brand-red"><?= esc(lang('Site.services.details')) ?> &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
