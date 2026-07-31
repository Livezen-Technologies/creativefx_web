<?php helper('norlanka');

/**
 * Location block. Items carrying lat/lon are plotted on the interactive world
 * map (same projection and component as the home page footprint); an `embed`
 * still renders an external map iframe (used on Contact).
 *
 * Projection (matches resources/data/world-dots.json):
 *   x = (lon + 180) / 360          full 360° of longitude
 *   y = (83 - lat) / 139           cropped to 83°N … 56°S
 */
$items  = (! empty($content['items']) && is_array($content['items'])) ? $content['items'] : [];
$points = [];
foreach ($items as $item) {
    if (! isset($item['lat'], $item['lon'])) {
        continue;
    }
    $lat = (float) $item['lat'];
    $lon = (float) $item['lon'];
    $points[] = [
        // nudge_* separates markers that sit within a few pixels of each other
        // at world scale (e.g. two cities on the same small island).
        'x'           => ((($lon + 180) / 360) + (float) ($item['nudge_x'] ?? 0)),
        'y'           => (((83 - $lat) / 139) + (float) ($item['nudge_y'] ?? 0)),
        'hq'          => ! empty($item['hq']),
        'name'        => t_field($item['region'] ?? []),
        'role'        => t_field($item['detail'] ?? []),
        'label_below' => ! empty($item['label_below']),
    ];
}
$hubIndex = 0;
foreach ($points as $i => $p) {
    if ($p['hq']) { $hubIndex = $i; break; }
}
?>
<section class="relative overflow-hidden bg-brand-black py-20"
         <?= $points !== [] ? 'x-data="worldMap(' . esc(json_encode($points), 'attr') . ')" x-init="init()"' : '' ?>>
    <div class="container-x">
        <?php if ($points !== []): ?>
            <div class="grid gap-10 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-4" data-gsap="reveal">
                    <?php if (! empty($content['title'])): ?>
                        <h2 class="text-3xl font-bold sm:text-4xl"><?= esc(t_field($content['title'])) ?></h2>
                    <?php endif; ?>
                    <?php if (! empty($content['intro'])): ?>
                        <p class="mt-3 text-white/60"><?= esc(t_field($content['intro'])) ?></p>
                    <?php endif; ?>

                    <ul class="mt-7 flex flex-col gap-1">
                        <?php foreach ($points as $i => $p): ?>
                            <li>
                                <button type="button"
                                        class="nl-region group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition"
                                        :class="isActive(<?= $i ?>) ? 'bg-white/[0.06]' : 'hover:bg-white/[0.03]'"
                                        @mouseenter="setActive(<?= $i ?>)" @mouseleave="clearActive()"
                                        @focus="setActive(<?= $i ?>)" @blur="clearActive()">
                                    <span class="nl-region__dot <?= $p['hq'] ? 'nl-region__dot--hq' : '' ?>"
                                          :class="isActive(<?= $i ?>) ? 'scale-125' : ''"></span>
                                    <span class="flex-1">
                                        <span class="block text-sm font-semibold text-white"><?= esc($p['name']) ?></span>
                                        <?php if ($p['role'] !== ''): ?>
                                            <span class="block text-xs text-white/45"><?= esc($p['role']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="lg:col-span-8" data-gsap="reveal">
                    <?= view('Modules\Site\Views\partials\world_map', ['points' => $points, 'hubIndex' => $hubIndex]) ?>
                </div>
            </div>
        <?php else: ?>
            <?php if (! empty($content['title'])): ?>
                <h2 class="text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
            <?php endif; ?>
            <?php if (! empty($content['intro'])): ?>
                <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (! empty($content['embed'])): ?>
            <div class="mt-8 overflow-hidden rounded-2xl border border-white/10" data-gsap="reveal">
                <iframe src="<?= esc($content['embed']) ?>" class="h-80 w-full" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        <?php endif; ?>
    </div>
</section>
