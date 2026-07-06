<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

// --- Hero content (from the seeded CMS 'hero' block, with sensible fallbacks) ---
$heroRaw      = $sections['hero']['blocks'][0]['content'] ?? null;
$hero         = $heroRaw ? json_decode($heroRaw, true) : [];
$heroHeadline = $hero['headline'] ?? ['en' => "We craft the world's apparel, responsibly."];
$heroSubhead  = $hero['subhead']  ?? ['en' => 'Design. Innovation. Responsible sourcing — at global scale.'];

// Kinetic-typography headline parts (fall back to the plain headline split into words).
$kPre      = t_field($hero['pre'] ?? []);
$kPost     = t_field($hero['post'] ?? []);
$kRotators = array_values(array_filter(array_map(static fn ($r) => t_field($r), $hero['rotators'] ?? [])));
if ($kPre === '' && $kPost === '' && $kRotators === []) {
    $kPre = t_field($heroHeadline); // no kinetic data seeded → animate the whole headline
}
$splitWords = static function (string $text): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $html = '';
    foreach (preg_split('/\s+/u', $text) as $w) {
        $html .= '<span class="kw"><span class="kw-i">' . esc($w) . '</span></span> ';
    }
    return $html;
};

// Capabilities ("What we do") — Hirdaramani-style services grid (localized).
$capabilities = [
    ['title' => lang('Site.home.cap.apparel_t'),  'text' => lang('Site.home.cap.apparel_d'),  'icon' => 'M6 3l-2 4 3 2v12h10V9l3-2-2-4-3 2a4 4 0 01-6 0L6 3z'],
    ['title' => lang('Site.home.cap.washing_t'),  'text' => lang('Site.home.cap.washing_d'),  'icon' => 'M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z'],
    ['title' => lang('Site.home.cap.printing_t'), 'text' => lang('Site.home.cap.printing_d'), 'icon' => 'M4 20h16M5 16l9-9 3 3-9 9H5v-3zM14 7l3-3 3 3-3 3'],
    ['title' => lang('Site.home.cap.design_t'),   'text' => lang('Site.home.cap.design_d'),   'icon' => 'M12 20h9M3 20l2-6 11-11 4 4L9 18l-6 2zM14 6l4 4'],
];

// Sustainability pillars — the heart of a responsible-manufacturing story.
$impact = [
    ['title' => lang('Site.home.impact.p1_t'), 'text' => lang('Site.home.impact.p1_d')],
    ['title' => lang('Site.home.impact.p2_t'), 'text' => lang('Site.home.impact.p2_d')],
    ['title' => lang('Site.home.impact.p3_t'), 'text' => lang('Site.home.impact.p3_d')],
];

// Global footprint regions (localized list).
$regions = lang('Site.home.footprint.regions');
if (! is_array($regions)) { $regions = ['Sri Lanka', 'South Asia', 'South-East Asia', 'Europe', 'North America', 'Global brands']; }

// Interactive presence map — normalized coordinates (0..1) computed with the same
// plate-carrée projection as the land-dot field (scripts/gen-dotmap.mjs), so the
// markers sit exactly on the map. Labels/roles are localized; `hq` anchors arcs.
$pointNames = lang('Site.home.footprint.points');
$pointRoles = lang('Site.home.footprint.roles');
$mapPoints = [];
foreach ([
    ['key' => 'hq',      'x' => 0.7218, 'y' => 0.5473, 'role' => 'hq',     'hq' => true],
    ['key' => 'india',   'x' => 0.7167, 'y' => 0.4353, 'role' => 'hub'],
    ['key' => 'sea',     'x' => 0.7964, 'y' => 0.5194, 'role' => 'hub'],
    ['key' => 'mideast', 'x' => 0.6536, 'y' => 0.4158, 'role' => 'hub'],
    ['key' => 'europe',  'x' => 0.5241, 'y' => 0.2366, 'role' => 'market'],
    ['key' => 'america', 'x' => 0.2944, 'y' => 0.3043, 'role' => 'market'],
] as $p) {
    $mapPoints[] = [
        'key'  => $p['key'],
        'x'    => $p['x'],
        'y'    => $p['y'],
        'hq'   => ! empty($p['hq']),
        'name' => is_array($pointNames) ? ($pointNames[$p['key']] ?? $p['key']) : $p['key'],
        'role' => is_array($pointRoles) ? ($pointRoles[$p['role']] ?? '') : '',
    ];
}
$hqPoint = $mapPoints[0];
?>

<?= $this->section('content') ?>

<!-- ===================== HERO ===================== -->
<?php $heroPoster = $video['poster_path'] ?? '/media/video/home-hero-poster.jpg'; ?>
<section
    id="hero"
    data-gsap="hero-out"
    x-data="{ playing: true, toggleVid() { const v = $refs.bgv; if (!v) return; if (v.paused) { delete v.dataset.userPaused; v.play(); this.playing = true; } else { v.dataset.userPaused = '1'; v.pause(); this.playing = false; } } }"
    class="on-dark relative flex min-h-screen items-center overflow-hidden"
>
    <!-- Background: real launch film if set in the CMS, else the animated brand visual -->
    <?php if (! empty($video['src_path'])): ?>
        <!-- Background film. Poster paints instantly (LCP) while it buffers; JS
             force-plays it (see heroVideo.js) so it loops continuously. -->
        <video x-ref="bgv"
               class="absolute inset-0 -z-30 h-full w-full object-cover"
               autoplay muted loop playsinline preload="auto"
               poster="<?= esc($heroPoster) ?>">
            <source src="<?= esc($video['src_path']) ?>" type="video/mp4">
            <?php if (! empty($video['src_path_webm'])): ?>
                <source src="<?= esc($video['src_path_webm']) ?>" type="video/webm">
            <?php endif; ?>
        </video>
        <!-- Subtle corner control (kept far from the CTAs so it never competes). -->
        <button type="button" @click="toggleVid()"
                class="absolute bottom-8 right-6 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-black/30 text-white/80 backdrop-blur transition hover:border-white hover:text-white lg:right-10"
                :aria-label="playing ? 'Pause background video' : 'Play background video'">
            <svg x-show="playing" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
            <svg x-show="!playing" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5l12 7-12 7z"/></svg>
        </button>
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-30"></div>
        <div data-three-hero class="absolute inset-0 -z-20 opacity-70"></div>
    <?php endif; ?>

    <!-- Lighter, directional scrim (legible text on the left, the film stays visible on the right) -->
    <div class="absolute inset-0 -z-20 bg-gradient-to-r from-black/85 via-black/45 to-transparent"></div>
    <div class="absolute inset-0 -z-20 bg-gradient-to-t from-brand-black via-brand-black/10 to-transparent"></div>
    <!-- Brand-red glow to break the monochrome -->
    <div class="hero-red-glow absolute inset-0 -z-10"></div>

    <div class="container-x relative w-full pt-28">
        <p class="eyebrow mb-6" data-gsap="reveal"><?= esc(lang('Site.home.hero.eyebrow')) ?></p>

        <h1 class="kinetic-hero max-w-4xl text-4xl font-bold leading-[1.05] sm:text-5xl lg:text-6xl xl:text-7xl" aria-label="<?= esc(t_field($heroHeadline), 'attr') ?>">
            <?php if ($kPre !== ''): ?><span class="kline" data-kinetic><?= $splitWords($kPre) ?></span><?php endif; ?>
            <?php if ($kRotators !== []): ?>
                <span class="rotator-wrap text-brand-red" data-rotator aria-hidden="true">
                    <span class="rotator-list">
                        <?php foreach ($kRotators as $rw): ?><span class="rotator-word"><?= esc($rw) ?></span><?php endforeach; ?>
                        <span class="rotator-word"><?= esc($kRotators[0]) ?></span>
                    </span>
                </span>
            <?php endif; ?>
            <?php if ($kPost !== ''): ?><span class="kline" data-kinetic><?= $splitWords($kPost) ?></span><?php endif; ?>
        </h1>

        <p class="mt-6 max-w-xl text-lg leading-relaxed text-white/75" data-gsap="reveal"><?= esc(t_field($heroSubhead)) ?></p>

        <!-- CTA hierarchy: one dominant primary, one quiet secondary -->
        <div class="mt-10 flex flex-wrap items-center gap-6" data-gsap="reveal">
            <a href="<?= esc(locale_url('our-expertise')) ?>" class="btn-brand btn-lg group">
                <?= esc(lang('Site.home.hero.primary')) ?>
                <svg class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="<?= esc(locale_url('showroom')) ?>" class="group inline-flex items-center gap-3 text-sm font-semibold uppercase tracking-widest text-white/85 transition hover:text-white">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/30 transition group-hover:border-brand-red group-hover:bg-brand-red/10">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5l11 7-11 7z"/></svg>
                </span>
                <?= esc(lang('Site.home.hero.secondary')) ?>
            </a>
        </div>

        <div class="mt-14 flex items-center gap-4" data-gsap="reveal">
            <span class="h-px w-10 bg-white/20"></span>
            <p class="text-[11px] uppercase tracking-[0.3em] text-white/45">
                <?= esc(lang('Site.home.hero.trust')) ?>
            </p>
        </div>
    </div>

    <div class="absolute inset-x-0 bottom-8 flex justify-center">
        <span class="flex flex-col items-center gap-2 text-[10px] uppercase tracking-[0.3em] text-white/40">
            <?= esc(lang('Site.experience.scroll')) ?>
            <svg class="h-4 w-4 animate-bounce text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M6 13l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
    </div>
</section>

<!-- ===================== LEGACY / INTRO ===================== -->
<section class="bg-brand-black py-24 sm:py-28">
    <div class="container-x grid gap-12 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-7" data-gsap="reveal">
            <p class="eyebrow"><?= esc(lang('Site.home.intro.eyebrow')) ?></p>
            <h2 class="mt-5 text-3xl font-bold leading-tight sm:text-5xl">
                <?= esc(lang('Site.home.intro.title')) ?>
            </h2>
        </div>
        <div class="lg:col-span-5" data-gsap="reveal">
            <p class="text-lg leading-relaxed text-white/65">
                <?= esc(lang('Site.home.intro.body')) ?>
            </p>
            <a href="<?= esc(locale_url('our-story')) ?>"
               class="mt-6 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-widest text-white transition hover:text-brand-red">
                <?= esc(lang('Site.cta.our_story')) ?>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ===================== STATS (CMS-driven) ===================== -->
<?= $this->include('Modules\Site\Views\home\sections\stats', ['section' => $sections['stats'] ?? null]) ?>

<!-- ===================== CAPABILITIES / WHAT WE DO ===================== -->
<section class="bg-brand-black py-24 sm:py-28">
    <div class="container-x">
        <div class="max-w-2xl" data-gsap="reveal">
            <p class="eyebrow"><?= esc(lang('Site.home.cap.eyebrow')) ?></p>
            <h2 class="mt-5 text-3xl font-bold sm:text-5xl"><?= esc(lang('Site.home.cap.title')) ?></h2>
            <p class="mt-4 text-white/60"><?= esc(lang('Site.home.cap.intro')) ?></p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($capabilities as $cap): ?>
                <article class="group flex h-full flex-col rounded-2xl border border-white/10 bg-white/[0.02] p-8 transition hover:border-brand-red/60 hover:bg-white/[0.04]" data-gsap="reveal">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl border border-white/10 text-brand-red transition group-hover:border-brand-red/60">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="<?= esc($cap['icon'], 'attr') ?>"/></svg>
                    </span>
                    <h3 class="mt-6 text-xl font-semibold"><?= esc($cap['title']) ?></h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/60"><?= esc($cap['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== VALUES / PILLARS (CMS-driven) ===================== -->
<?= $this->include('Modules\Site\Views\home\sections\pillars', ['section' => $sections['pillars'] ?? null]) ?>

<!-- ===================== SUSTAINABILITY / OUR IMPACT ===================== -->
<section class="on-dark relative overflow-hidden border-y border-white/10 py-24 sm:py-28">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-50"></div>
    <div class="absolute inset-0 -z-10 bg-black/55"></div>
    <div class="container-x grid gap-14 lg:grid-cols-2 lg:items-center">
        <div data-gsap="reveal">
            <p class="eyebrow"><?= esc(lang('Site.home.impact.eyebrow')) ?></p>
            <h2 class="mt-5 text-3xl font-bold leading-tight sm:text-5xl"><?= esc(lang('Site.home.impact.title')) ?></h2>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/70">
                <?= esc(lang('Site.home.impact.body')) ?>
            </p>
            <a href="<?= esc(locale_url('impact')) ?>" class="btn-brand mt-8"><?= esc(lang('Site.home.impact.cta')) ?></a>
        </div>

        <div class="grid gap-4" data-gsap="reveal">
            <?php foreach ($impact as $i => $point): ?>
                <div class="flex items-start gap-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6 backdrop-blur">
                    <span class="text-2xl font-bold text-brand-red">0<?= $i + 1 ?></span>
                    <div>
                        <h3 class="text-lg font-semibold"><?= esc($point['title']) ?></h3>
                        <p class="mt-1 text-sm leading-relaxed text-white/60"><?= esc($point['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== GLOBAL FOOTPRINT (interactive map) ===================== -->
<?php
// Pre-compute the HQ→market connection arcs in the SVG viewBox space (0 0 1000 386).
$vbW = 1000; $vbH = 386;
$hx  = $hqPoint['x'] * $vbW; $hy = $hqPoint['y'] * $vbH;
$arcs = [];
foreach ($mapPoints as $mp) {
    if ($mp['hq']) { continue; }
    $mx = $mp['x'] * $vbW; $my = $mp['y'] * $vbH;
    $cx = ($hx + $mx) / 2;
    $dist = sqrt(($mx - $hx) ** 2 + ($my - $hy) ** 2);
    $cy = min($hy, $my) - $dist * 0.22;      // lift the control point above the pair
    $arcs[] = sprintf('M%.1f %.1f Q%.1f %.1f %.1f %.1f', $hx, $hy, $cx, $cy, $mx, $my);
}
?>
<section class="on-dark relative overflow-hidden bg-brand-black py-24 sm:py-28"
         x-data="worldMap(<?= esc(json_encode($mapPoints), 'attr') ?>)" x-init="init()">
    <div class="container-x">
        <div class="grid gap-12 lg:grid-cols-12 lg:items-center">
            <!-- Left: copy + legend + region list -->
            <div class="lg:col-span-4" data-gsap="reveal">
                <p class="eyebrow"><?= esc(lang('Site.home.footprint.eyebrow')) ?></p>
                <h2 class="mt-5 text-3xl font-bold sm:text-5xl"><?= esc(lang('Site.home.footprint.title')) ?></h2>
                <p class="mt-5 text-lg leading-relaxed text-white/65">
                    <?= esc(lang('Site.home.footprint.body')) ?>
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs uppercase tracking-widest text-white/55">
                    <span class="inline-flex items-center gap-2">
                        <span class="nl-legend-dot nl-legend-dot--hq"></span><?= esc(lang('Site.home.footprint.legend_hq')) ?>
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="nl-legend-dot"></span><?= esc(lang('Site.home.footprint.legend_market')) ?>
                    </span>
                </div>

                <ul class="mt-6 flex flex-col gap-1">
                    <?php foreach ($mapPoints as $i => $mp): ?>
                        <li>
                            <button type="button"
                                    class="nl-region group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition"
                                    :class="isActive(<?= $i ?>) ? 'bg-white/[0.06]' : 'hover:bg-white/[0.03]'"
                                    @mouseenter="setActive(<?= $i ?>)" @mouseleave="clearActive()"
                                    @focus="setActive(<?= $i ?>)" @blur="clearActive()">
                                <span class="nl-region__dot <?= $mp['hq'] ? 'nl-region__dot--hq' : '' ?>"
                                      :class="isActive(<?= $i ?>) ? 'scale-125' : ''"></span>
                                <span class="flex-1">
                                    <span class="block text-sm font-semibold text-white"><?= esc($mp['name']) ?></span>
                                    <span class="block text-xs text-white/45"><?= esc($mp['role']) ?></span>
                                </span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-4 text-xs italic text-white/35"><?= esc(lang('Site.home.footprint.note')) ?></p>
            </div>

            <!-- Right: the map stage -->
            <div class="lg:col-span-8" data-gsap="reveal">
                <div class="nl-map" x-ref="stage" style="aspect-ratio: <?= esc($vbW / $vbH) ?>;">
                    <canvas class="nl-map__dots" x-ref="canvas" aria-hidden="true"></canvas>

                    <svg class="nl-map__arcs" viewBox="0 0 <?= $vbW ?> <?= $vbH ?>" preserveAspectRatio="none" aria-hidden="true">
                        <?php foreach ($arcs as $d): ?>
                            <path class="nl-arc" d="<?= esc($d, 'attr') ?>" fill="none"></path>
                        <?php endforeach; ?>
                    </svg>

                    <?php foreach ($mapPoints as $i => $mp): ?>
                        <button type="button"
                                class="nl-marker <?= $mp['hq'] ? 'nl-marker--hq' : '' ?>"
                                style="left: <?= esc($mp['x'] * 100) ?>%; top: <?= esc($mp['y'] * 100) ?>%;"
                                :class="isActive(<?= $i ?>) ? 'is-active' : ''"
                                @mouseenter="setActive(<?= $i ?>)" @mouseleave="clearActive()"
                                @focus="setActive(<?= $i ?>)" @blur="clearActive()"
                                aria-label="<?= esc($mp['name'] . ' — ' . $mp['role'], 'attr') ?>">
                            <span class="nl-marker__pulse" aria-hidden="true"></span>
                            <span class="nl-marker__dot" aria-hidden="true"></span>
                            <span class="nl-marker__label">
                                <span class="nl-marker__name"><?= esc($mp['name']) ?></span>
                                <span class="nl-marker__role"><?= esc($mp['role']) ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== VIRTUAL SHOWROOM (CMS-driven CTA) ===================== -->
<?= $this->include('Modules\Site\Views\home\sections\cta', ['section' => $sections['cta'] ?? null]) ?>

<!-- ===================== PARTNER / CLOSING CTA ===================== -->
<section class="on-dark bg-brand-black pb-28">
    <div class="container-x">
        <div class="overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#1a0205] via-brand-black to-brand-black p-10 sm:p-16" data-gsap="reveal">
            <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold leading-tight sm:text-4xl"><?= esc(lang('Site.home.closing.title')) ?></h2>
                    <p class="mt-4 max-w-lg text-white/65"><?= esc(lang('Site.home.closing.body')) ?></p>
                </div>
                <div class="flex flex-wrap gap-4 lg:justify-end">
                    <a href="<?= esc(locale_url('contact')) ?>" class="btn-brand"><?= esc(lang('Site.cta.contact_us')) ?></a>
                    <a href="<?= esc(locale_url('careers')) ?>" class="btn-ghost"><?= esc(lang('Site.cta.view_careers')) ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
