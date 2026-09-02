<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

// --- Hero content (from the seeded CMS 'hero' block, with sensible fallbacks) ---
$heroRaw      = $sections['hero']['blocks'][0]['content'] ?? null;
$hero         = $heroRaw ? json_decode($heroRaw, true) : [];
$heroHeadline = $hero['headline'] ?? ['en' => 'Sweet corn, served hot in a cup.'];
$heroSubhead  = $hero['subhead']  ?? ['en' => 'Sri Lanka’s original corn in a cup.'];

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
    ['title' => lang('Site.home.cap.design_t'),   'text' => lang('Site.home.cap.design_d'),   'icon' => 'M12 20h9M3 20l2-6 11-11 4 4L9 18l-6 2zM14 6l4 4'],
    ['title' => lang('Site.home.cap.mfg_t'),      'text' => lang('Site.home.cap.mfg_d'),      'icon' => 'M2 20a2 2 0 002 2h16a2 2 0 002-2V8l-7 5V8l-7 5V4a2 2 0 00-2-2H4a2 2 0 00-2 2z'],
    ['title' => lang('Site.home.cap.sourcing_t'), 'text' => lang('Site.home.cap.sourcing_d'), 'icon' => 'M11 20A7 7 0 014 13c0-4 3-8 8-10 0 0 8 3 8 10a7 7 0 01-7 7h-2zM6 21c1-3 3-6 6-8'],
    ['title' => lang('Site.home.cap.partner_t'),  'text' => lang('Site.home.cap.partner_d'),  'icon' => 'M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12s4.5 10 10 10zM2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z'],
];

// Sustainability pillars — the heart of a responsible-manufacturing story.
$impact = [
    ['title' => lang('Site.home.impact.p1_t'), 'text' => lang('Site.home.impact.p1_d')],
    ['title' => lang('Site.home.impact.p2_t'), 'text' => lang('Site.home.impact.p2_d')],
    ['title' => lang('Site.home.impact.p3_t'), 'text' => lang('Site.home.impact.p3_d')],
];

?>

<?= $this->section('content') ?>

<!-- Full-screen "fullpage" experience wrapper. Each direct-child <section> below
     becomes a snap panel (the footer is pulled in as the last one by fullpage.js).
     Without JS this is an ordinary block and the page scrolls normally. -->
<div id="fp" class="fp">

<!-- ===================== HERO ===================== -->
<?php $heroPoster = $video['poster_path'] ?? '/media/video/home-hero-poster.jpg'; ?>
<section
    id="hero"
    data-gsap="hero-out"
    x-data="{ playing: true, toggleVid() { const v = $refs.bgv; if (!v) return; if (v.paused) { delete v.dataset.userPaused; v.play(); this.playing = true; } else { v.dataset.userPaused = '1'; v.pause(); this.playing = false; } } }"
    class="relative flex min-h-screen items-center overflow-hidden"
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
                class="absolute bottom-8 right-6 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-brand-black/50 text-white/80 backdrop-blur transition hover:border-white hover:text-white lg:right-10"
                :aria-label="playing ? 'Pause background video' : 'Play background video'">
            <svg x-show="playing" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
            <svg x-show="!playing" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5l12 7-12 7z"/></svg>
        </button>
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-30"></div>
        <div data-three-hero class="absolute inset-0 -z-20 opacity-70"></div>
    <?php endif; ?>

    <!-- Lighter, directional scrim (legible text on the left, the film stays visible on the right) -->
    <div class="absolute inset-0 -z-20 bg-gradient-to-r from-brand-black/95 via-brand-black/60 to-transparent"></div>
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
            <a href="<?= esc(locale_url('our-business')) ?>" class="btn-brand btn-lg group">
                <?= esc(lang('Site.home.hero.primary')) ?>
                <svg class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="<?= esc(locale_url('our-locations')) ?>" class="group inline-flex items-center gap-3 text-sm font-semibold uppercase tracking-widest text-white/85 transition hover:text-white">
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

    <button type="button" data-fp-next
            class="absolute inset-x-0 bottom-8 flex cursor-pointer justify-center bg-transparent"
            aria-label="<?= esc(lang('Site.experience.scroll'), 'attr') ?>">
        <span class="flex flex-col items-center gap-2 text-[10px] uppercase tracking-[0.3em] text-white/40 transition hover:text-white/70">
            <?= esc(lang('Site.experience.scroll')) ?>
            <svg class="h-4 w-4 animate-bounce text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M6 13l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
    </button>
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
            <a href="<?= esc(locale_url('about-us')) ?>"
               class="mt-6 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-widest text-white transition hover:text-brand-red">
                <?= esc(lang('Site.cta.our_story')) ?>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ===================== STATS (CMS-driven) ===================== -->
<?= view('Modules\Site\Views\home\sections\stats', ['section' => $sections['stats'] ?? null]) ?>

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

<!-- ===================== SUSTAINABILITY / OUR IMPACT ===================== -->
<section class="relative overflow-hidden border-y border-white/10 py-24 sm:py-28">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-60"></div>
    <div class="container-x grid gap-14 lg:grid-cols-2 lg:items-center">
        <div data-gsap="reveal">
            <p class="eyebrow"><?= esc(lang('Site.home.impact.eyebrow')) ?></p>
            <h2 class="mt-5 text-3xl font-bold leading-tight sm:text-5xl"><?= esc(lang('Site.home.impact.title')) ?></h2>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/70">
                <?= esc(lang('Site.home.impact.body')) ?>
            </p>
            <a href="<?= esc(locale_url('about-us')) ?>" class="btn-brand mt-8"><?= esc(lang('Site.home.impact.cta')) ?></a>
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

<!-- ===================== VIRTUAL SHOWROOM (CMS-driven CTA) ===================== -->
<?= view('Modules\Site\Views\home\sections\cta', ['section' => $sections['cta'] ?? null]) ?>

<!-- ===================== PARTNER / CLOSING CTA ===================== -->
<section class="bg-brand-black pb-28">
    <div class="container-x">
        <div class="overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-brand-red/10 via-brand-black to-brand-black p-10 sm:p-16" data-gsap="reveal">
            <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold leading-tight sm:text-4xl"><?= esc(lang('Site.home.closing.title')) ?></h2>
                    <p class="mt-4 max-w-lg text-white/65"><?= esc(lang('Site.home.closing.body')) ?></p>
                </div>
                <div class="flex flex-wrap gap-4 lg:justify-end">
                    <a href="<?= esc(locale_url('contact')) ?>" class="btn-brand"><?= esc(lang('Site.cta.contact_us')) ?></a>
                    <a href="<?= esc(locale_url('our-locations')) ?>" class="btn-ghost"><?= esc(lang('Site.cta.find_outlet')) ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

</div><!-- /#fp -->

<?= $this->endSection() ?>
