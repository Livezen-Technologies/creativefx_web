<?php
helper(['norlanka', 'url']);

/**
 * Breadcrumbs — the trail back up.
 *
 * A real <nav> with an ordered list, because that is what a screen reader can
 * announce as a trail, and with aria-current on the last item so it is read as
 * "current page" rather than as a link that goes nowhere.
 *
 * @var list<array{label:string,url?:string}> $crumbs
 */
$crumbs = $crumbs ?? [];
if ($crumbs === []) {
    return;
}
$last = count($crumbs) - 1;
?>
<nav aria-label="<?= esc(lang('Site.nav.breadcrumb'), 'attr') ?>" class="text-sm">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-white/55">
        <li>
            <a href="<?= esc(locale_url('')) ?>" class="transition hover:text-brand-red"><?= esc(lang('Site.nav.home')) ?></a>
        </li>
        <?php foreach ($crumbs as $i => $crumb): ?>
            <li aria-hidden="true" class="text-white/30">/</li>
            <li>
                <?php if ($i === $last || empty($crumb['url'])): ?>
                    <span aria-current="page" class="font-medium text-white/80"><?= esc($crumb['label']) ?></span>
                <?php else: ?>
                    <a href="<?= esc($crumb['url']) ?>" class="transition hover:text-brand-red"><?= esc($crumb['label']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
