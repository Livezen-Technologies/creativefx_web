<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

/**
 * A link to this same search with one thing changed.
 *
 * Every facet on the page is a link rather than a script, so a filtered or
 * sorted result set has its own address — which is what makes it linkable,
 * bookmarkable and reachable with the back button. Passing null for a key drops
 * it, which is how "all types" and "page 1" are expressed.
 */
$q = static function (array $overrides) use ($query, $type, $sort): string {
    $params = array_merge(['q' => $query, 'type' => $type, 'sort' => $sort], $overrides);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);

    return locale_url('search') . '?' . http_build_query($params);
};
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.search.title')]],
    'eyebrow' => lang('Site.nav.search'),
    'heading' => $query !== '' ? lang('Site.search.results_for', [$query]) : lang('Site.search.title'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x grid gap-10 lg:grid-cols-[15rem_minmax(0,1fr)]">
        <aside class="order-2 lg:order-1">
            <?php if ($counts !== []): ?>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.search.all_types')) ?></h2>
                <ul class="mt-4 space-y-1 text-sm" role="list">
                    <li>
                        <a href="<?= esc($q(['type' => null, 'page' => null])) ?>"
                           class="flex items-center justify-between rounded-lg px-3 py-2 font-medium transition <?= $type === '' ? 'bg-brand-red/10 text-brand-red' : 'hover:bg-surface' ?>">
                            <span><?= esc(lang('Site.search.all_types')) ?></span>
                            <span class="text-xs text-white/50"><?= esc((string) array_sum($counts)) ?></span>
                        </a>
                    </li>
                    <?php foreach ($counts as $t => $n): ?>
                        <li>
                            <a href="<?= esc($q(['type' => $t, 'page' => null])) ?>"
                               class="flex items-center justify-between rounded-lg px-3 py-2 font-medium transition <?= $type === $t ? 'bg-brand-red/10 text-brand-red' : 'hover:bg-surface' ?>">
                                <span><?= esc(lang('Site.search.type_' . $t)) ?></span>
                                <span class="text-xs text-white/50"><?= esc((string) $n) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>

        <div class="order-1 min-w-0 lg:order-2">
            <form method="get" role="search" class="flex flex-wrap items-end gap-3">
                <label class="min-w-[16rem] flex-1">
                    <span class="sr-only"><?= esc(lang('Site.search.label')) ?></span>
                    <input type="search" name="q" value="<?= esc($query, 'attr') ?>" class="field" placeholder="<?= esc(lang('Site.search.placeholder'), 'attr') ?>">
                </label>
                <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= esc($type, 'attr') ?>"><?php endif; ?>
                <label class="min-w-[10rem]">
                    <span class="field-label"><?= esc(lang('Site.search.sort')) ?></span>
                    <select name="sort" class="field">
                        <?php foreach (['relevance', 'date', 'title', 'type'] as $s): ?>
                            <option value="<?= esc($s, 'attr') ?>" <?= $sort === $s ? 'selected' : '' ?>><?= esc(lang('Site.search.sort_' . $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn-brand"><?= esc(lang('Site.search.button')) ?></button>
            </form>

            <?php if ($query === ''): ?>
                <p class="mt-8 text-sm text-white/60"><?= esc(lang('Site.search.placeholder')) ?></p>
            <?php elseif ($results === []): ?>
                <p class="mt-8 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.search.none', [$query])) ?></p>
            <?php else: ?>
                <p class="mt-6 text-sm text-white/60"><?= esc(lang('Site.search.count', [$total])) ?></p>
                <ol class="mt-4 divide-y divide-line rounded-2xl border border-line" role="list">
                    <?php foreach ($results as $result): ?>
                        <li class="p-5">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-brand-red"><?= esc(lang('Site.search.type_' . $result['type'])) ?></p>
                            <h2 class="mt-1 text-base font-semibold leading-snug">
                                <a href="<?= esc($result['url'], 'attr') ?>" class="transition hover:text-brand-red"><?= esc($result['title']) ?></a>
                            </h2>
                            <?php if (! empty($result['excerpt'])): ?>
                                <p class="mt-1.5 text-sm leading-relaxed text-white/65"><?= esc($result['excerpt']) ?></p>
                            <?php endif; ?>
                            <?php if (! empty($result['date'])): ?>
                                <p class="mt-2 text-xs text-white/45"><?= esc(date('j M Y', strtotime((string) $result['date']))) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <?php if ($pageCount > 1): ?>
                    <nav aria-label="<?= esc(lang('Site.search.title'), 'attr') ?>" class="mt-8 flex items-center justify-between gap-4 text-sm">
                        <?php if ($page > 1): ?>
                            <a href="<?= esc($q(['page' => $page - 1])) ?>" class="font-semibold text-brand-red hover:underline">&larr; <?= esc(lang('Site.search.prev')) ?></a>
                        <?php else: ?><span></span><?php endif; ?>
                        <span class="text-white/55"><?= esc(lang('Site.search.page_of', [$page, $pageCount])) ?></span>
                        <?php if ($page < $pageCount): ?>
                            <a href="<?= esc($q(['page' => $page + 1])) ?>" class="font-semibold text-brand-red hover:underline"><?= esc(lang('Site.search.next')) ?> &rarr;</a>
                        <?php else: ?><span></span><?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
