<?php
/**
 * Interactive world-map stage (dotted land canvas + hub arcs + markers).
 *
 * Shared by the home page footprint section and the CMS "map" block so both
 * use the same projection and behaviour.
 *
 * Expects:
 *   $points  array of ['x','y','hq','name','role'] — x/y are 0..1 in the same
 *            plate-carrée space as resources/data/world-dots.json.
 *   $hubIndex (optional) index the connection arcs radiate from; default 0.
 */
$points   = $points ?? [];
$hubIndex = $hubIndex ?? 0;
if ($points === []) { return; }

// Arc geometry in the SVG viewBox space, bowed away from the hub.
$vbW = 1000; $vbH = 386;
$hub = $points[$hubIndex] ?? $points[0];
$hx  = $hub['x'] * $vbW; $hy = $hub['y'] * $vbH;
$arcs = [];
foreach ($points as $i => $mp) {
    if ($i === $hubIndex) { continue; }
    $mx = $mp['x'] * $vbW; $my = $mp['y'] * $vbH;
    // Short hops get a flatter bow so nearby points don't loop oddly.
    $span = hypot($mx - $hx, $my - $hy);
    $lift = min(0.35, 30 / max($span, 1)) * $span;
    $cx   = ($hx + $mx) / 2;
    $cy   = ($hy + $my) / 2 - $lift;
    $arcs[] = sprintf('M%.1f %.1f Q%.1f %.1f %.1f %.1f', $hx, $hy, $cx, $cy, $mx, $my);
}
?>
<div class="nl-map" x-ref="stage" style="aspect-ratio: <?= esc($vbW / $vbH) ?>;">
    <canvas class="nl-map__dots" x-ref="canvas" aria-hidden="true"></canvas>

    <svg class="nl-map__arcs" viewBox="0 0 <?= $vbW ?> <?= $vbH ?>" preserveAspectRatio="none" aria-hidden="true">
        <?php foreach ($arcs as $d): ?>
            <path class="nl-arc" d="<?= esc($d, 'attr') ?>" fill="none"></path>
        <?php endforeach; ?>
    </svg>

    <?php foreach ($points as $i => $mp): ?>
        <button type="button"
                class="nl-marker <?= ! empty($mp['hq']) ? 'nl-marker--hq' : '' ?> <?= ! empty($mp['label_below']) ? 'nl-marker--label-below' : '' ?>"
                style="left: <?= esc($mp['x'] * 100) ?>%; top: <?= esc($mp['y'] * 100) ?>%;"
                :class="isActive(<?= $i ?>) ? 'is-active' : ''"
                @mouseenter="setActive(<?= $i ?>)" @mouseleave="clearActive()"
                @focus="setActive(<?= $i ?>)" @blur="clearActive()"
                aria-label="<?= esc($mp['name'] . ($mp['role'] !== '' ? ' — ' . $mp['role'] : ''), 'attr') ?>">
            <span class="nl-marker__pulse" aria-hidden="true"></span>
            <span class="nl-marker__dot" aria-hidden="true"></span>
            <span class="nl-marker__label">
                <span class="nl-marker__name"><?= esc($mp['name']) ?></span>
                <?php if (($mp['role'] ?? '') !== ''): ?>
                    <span class="nl-marker__role"><?= esc($mp['role']) ?></span>
                <?php endif; ?>
            </span>
        </button>
    <?php endforeach; ?>
</div>
