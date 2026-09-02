<?php
/**
 * Sri Lanka locator. Expects $points — each with lat, lon, name, role, hq.
 * Outline: Natural Earth 1:10m (public domain), prepared by
 * scripts/build-sri-lanka-map.py into resources/data/sri-lanka.json.
 *
 * Pins are emitted server-side rather than with x-for. <template> has no
 * meaning inside <svg> — the HTML parser treats it as a foreign element and
 * renders its children as real SVG, so Alpine never binds them and the
 * placeholder attributes reach the renderer verbatim. The coordinates are
 * known at render time anyway; Alpine only carries the selection.
 */
$geoFile = ROOTPATH . 'resources/data/sri-lanka.json';
$geo     = is_file($geoFile) ? json_decode((string) file_get_contents($geoFile), true) : null;

if (! is_array($geo) || empty($points)) {
    return;
}

$b     = $geo['bounds'];
$scale = $geo['width'] / ($b['east'] - $b['west']);

$place = static function (float $lat, float $lon) use ($b, $scale, $geo): array {
    return [
        ($lon - $b['west']) * $scale,
        ($b['north'] - $lat) * $scale / $geo['lonSqueeze'],
    ];
};
?>
<div class="relative">
    <!-- The zoom scales the island past the viewBox, so the frame has to clip;
         an SVG only does that when overflow is left alone. -->
    <svg viewBox="<?= esc($geo['viewBox'], 'attr') ?>"
         class="mx-auto h-auto w-full max-w-[360px] rounded-3xl"
         style="overflow: hidden"
         role="img" aria-label="<?= esc(lang('Site.locations.map_alt'), 'attr') ?>">
        <defs>
            <radialGradient id="lk-pin-glow">
                <stop offset="0%"   stop-color="rgb(var(--accent))" stop-opacity="0.5"/>
                <stop offset="100%" stop-color="rgb(var(--accent))" stop-opacity="0"/>
            </radialGradient>
        </defs>

        <!-- One composited transform carries the zoom; the viewBox never moves. -->
        <g class="lk-zoom" :style="`transform:${transform}`">
            <?php foreach ($geo['paths'] as $d): ?>
                <path d="<?= esc($d, 'attr') ?>"
                      fill="rgb(var(--fg) / 0.07)"
                      stroke="rgb(var(--fg) / 0.22)"
                      stroke-width="2"
                      vector-effect="non-scaling-stroke"/>
            <?php endforeach; ?>

            <?php foreach ($points as $i => $p): ?>
                <?php [$px, $py] = $place((float) $p['lat'], (float) $p['lon']); ?>
                <g class="lk-pin cursor-pointer"
                   style="transform: translate(<?= round($px, 1) ?>px, <?= round($py, 1) ?>px)"
                   :style="`transform: translate(<?= round($px, 1) ?>px, <?= round($py, 1) ?>px) scale(${counterScale})`"
                   @click="select(<?= $i ?>)"
                   role="button" tabindex="0"
                   @keydown.enter.prevent="select(<?= $i ?>)"
                   @keydown.space.prevent="select(<?= $i ?>)"
                   aria-label="<?= esc($p['name'], 'attr') ?>">
                    <circle r="48" fill="url(#lk-pin-glow)"
                            class="transition-opacity duration-300"
                            :class="isSelected(<?= $i ?>) ? 'opacity-100' : 'opacity-0'"/>
                    <circle r="<?= ! empty($p['hq']) ? 15 : 11 ?>"
                            fill="rgb(var(--accent))"
                            stroke="rgb(var(--bg))" stroke-width="5"
                            vector-effect="non-scaling-stroke"
                            class="transition-all duration-300"
                            :class="isSelected(<?= $i ?>) ? 'lk-pin--on' : ''"/>
                </g>
            <?php endforeach; ?>
        </g>
    </svg>

    <button type="button" x-show="selected !== null" x-transition x-cloak @click="reset()"
            class="absolute right-0 top-0 rounded-full border border-white/20 bg-brand-black/70 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-widest text-white/70 backdrop-blur transition hover:border-brand-red/60 hover:text-white">
        <?= esc(lang('Site.locations.show_all')) ?>
    </button>
</div>
