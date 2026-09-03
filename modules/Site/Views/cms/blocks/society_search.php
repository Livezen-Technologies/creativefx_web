<?php
helper(['norlanka', 'url', 'form']);

/**
 * The society register, searchable in place on the Societies page.
 *
 * Filtering happens on the server and the form submits normally, so the
 * directory works with JavaScript disabled and every result set has its own
 * address that can be linked to or bookmarked — which a client-side filter
 * over a pre-rendered list would not give.
 */
$request  = service('request');
$filters  = [
    'q'        => trim((string) $request->getGet('society')),
    'district' => trim((string) $request->getGet('district')),
];

$rows = $districts = [];
try {
    $model     = model('Modules\Tshda\Models\SocietyModel');
    $districts = $model->districts();
    $rows      = $model->search($filters, 200);
} catch (\Throwable $e) {
    $rows = $districts = [];
}
?>
<section id="societies" class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-2xl font-semibold sm:text-3xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>

        <form method="get" action="#societies" class="mt-6 flex flex-wrap items-end gap-3">
            <label class="flex-1 min-w-[12rem]">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-white/60"><?= esc(lang('Site.societies.search')) ?></span>
                <input type="search" name="society" value="<?= esc($filters['q'], 'attr') ?>"
                       class="field" placeholder="<?= esc(lang('Site.societies.search_hint'), 'attr') ?>">
            </label>
            <label class="min-w-[10rem]">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-white/60"><?= esc(lang('Site.societies.district')) ?></span>
                <select name="district" class="field">
                    <option value=""><?= esc(lang('Site.societies.all_districts')) ?></option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?= esc($district, 'attr') ?>" <?= $filters['district'] === $district ? 'selected' : '' ?>><?= esc($district) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn-brand"><?= esc(lang('Site.societies.filter')) ?></button>
        </form>

        <?php if ($rows === []): ?>
            <p class="mt-8 rounded-2xl border border-line bg-surface p-6 text-sm text-white/70"><?= esc(lang('Site.societies.none')) ?></p>
        <?php else: ?>
            <p class="mt-6 text-sm text-white/60"><?= esc(lang('Site.societies.count', [count($rows)])) ?></p>
            <div class="mt-3 overflow-x-auto rounded-2xl border border-line">
                <table class="w-full min-w-[40rem] border-collapse text-left text-sm">
                    <thead class="bg-surface">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.societies.registration')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.societies.name')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.societies.district')) ?></th>
                            <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Site.societies.members')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-mono text-xs text-white/70"><?= esc($row['registration']) ?></td>
                                <td class="px-4 py-3"><?= esc($row['name']) ?></td>
                                <td class="px-4 py-3 text-white/70"><?= esc($row['district'] ?? '—') ?></td>
                                <td class="px-4 py-3 text-white/70"><?= (int) $row['members'] > 0 ? esc((string) (int) $row['members']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
