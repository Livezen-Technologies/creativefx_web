<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

// Which columns carry numbers, and what the largest of them is. Both are needed
// to draw the bars, and both are computed once here rather than inside the loop.
$numeric = [];
$max     = 0.0;
foreach ($rows as $row) {
    foreach ($row as $i => $cell) {
        if ($i === 0 || $cell === null || $cell === '') {
            continue;
        }
        if (is_numeric($cell)) {
            $numeric[$i] = true;
            $max = max($max, (float) $cell);
        }
    }
}
$hasFigures = $max > 0;
$chart      = $dataset['chart'] ?? 'table';
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [
        ['label' => lang('Site.statistics.title'), 'url' => locale_url('statistics')],
        ['label' => t_field($dataset['title'])],
    ],
    'eyebrow' => lang('Site.nav.statistics'),
    'heading' => t_field($dataset['title']),
    'intro'   => t_field($dataset['description']),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-5xl">
        <div class="flex flex-wrap items-center gap-x-8 gap-y-2 text-sm text-white/60">
            <?php if (! empty($dataset['period'])): ?>
                <p><span class="font-semibold"><?= esc(lang('Site.statistics.period')) ?>:</span> <?= esc($dataset['period']) ?></p>
            <?php endif; ?>
            <?php if ($unit = t_field($dataset['unit'])): ?>
                <p><span class="font-semibold"><?= esc(lang('Site.statistics.unit')) ?>:</span> <?= esc($unit) ?></p>
            <?php endif; ?>
            <?php if ($source = t_field($dataset['source'])): ?>
                <p><span class="font-semibold"><?= esc(lang('Site.statistics.source')) ?>:</span> <?= esc($source) ?></p>
            <?php endif; ?>
            <a href="<?= esc(locale_url('statistics/' . $dataset['slug'] . '/csv')) ?>" class="font-semibold text-brand-red hover:underline"><?= esc(lang('Site.statistics.download')) ?></a>
        </div>

        <?php if (! $hasFigures): ?>
            <p class="mt-8 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.statistics.no_data')) ?></p>
        <?php elseif ($chart !== 'table'): ?>
            <?php // The chart is CSS, not a library: bars sized by inline width
                  // against the largest value in the set. It needs no JavaScript,
                  // it prints, it scales with the text, and it degrades to the
                  // table below rather than to an empty box. A charting library
                  // for a bar chart of eight rows is a 60KB download to draw
                  // something the browser can already draw. ?>
            <figure class="mt-8 rounded-2xl border border-line bg-surface p-6">
                <figcaption class="sr-only"><?= esc(t_field($dataset['title'])) ?></figcaption>
                <ul class="space-y-3" role="list">
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $label = (string) ($row[0] ?? '');
                        $value = null;
                        foreach ($row as $i => $cell) {
                            if ($i !== 0 && is_numeric($cell)) { $value = (float) $cell; break; }
                        }
                        if ($value === null) { continue; }
                        $pct = $max > 0 ? max(1.5, ($value / $max) * 100) : 0;
                        ?>
                        <li class="grid grid-cols-[10rem_1fr_5rem] items-center gap-3 text-sm">
                            <span class="truncate text-white/70" title="<?= esc($label, 'attr') ?>"><?= esc($label) ?></span>
                            <span class="h-3 rounded-full bg-white/10">
                                <span class="block h-3 rounded-full bg-brand-red" style="width: <?= esc(number_format($pct, 2, '.', ''), 'attr') ?>%"></span>
                            </span>
                            <span class="text-right font-semibold tabular-nums"><?= esc(number_format($value)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </figure>
        <?php endif; ?>

        <?php // The table is always present, chart or no chart: it is the
              // accessible form of the same data, and it is what a screen
              // reader, a printer and a copy-paste actually get. ?>
        <div class="mt-8 overflow-x-auto rounded-2xl border border-line">
            <table class="w-full min-w-[32rem] border-collapse text-left text-sm">
                <caption class="sr-only"><?= esc(lang('Site.statistics.table_of', [t_field($dataset['title'])])) ?></caption>
                <thead class="bg-surface">
                    <tr>
                        <?php foreach ($columns as $i => $column): ?>
                            <th scope="col" class="px-4 py-3 font-semibold <?= isset($numeric[$i]) ? 'text-right' : '' ?>"><?= esc(t_field($column)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr class="border-t border-line">
                            <?php foreach ($columns as $i => $column): ?>
                                <?php $cell = $row[$i] ?? null; ?>
                                <?php if ($i === 0): ?>
                                    <th scope="row" class="px-4 py-3 font-medium"><?= esc((string) $cell) ?></th>
                                <?php else: ?>
                                    <td class="px-4 py-3 <?= isset($numeric[$i]) ? 'text-right tabular-nums' : '' ?> text-white/75">
                                        <?= $cell === null || $cell === '' ? '<span class="text-white/40">—</span>' : esc(is_numeric($cell) ? number_format((float) $cell) : (string) $cell) ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="mt-6 text-sm">
            <a href="<?= esc(locale_url('statistics')) ?>" class="font-semibold text-brand-red hover:underline">&larr; <?= esc(lang('Site.statistics.back')) ?></a>
        </p>
    </div>
</section>

<?= $this->endSection() ?>
