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

?>

<?= $this->section('content') ?>

<!-- Screen-by-screen. Each direct-child <section> becomes a panel the height of
     the viewport, and the footer is pulled in as the last one by fullpage.js.
     A panel taller than the screen — the welcome, the facilities, the reviews —
     scrolls inside itself first and only advances at its own end, so no copy is
     lost to the panel height. Depth comes from [data-parallax] on the imagery,
     which moves against the panel change instead of with it.
     app.js branches on #fp; without JavaScript this is an ordinary block and
     the page scrolls normally. -->
<div id="fp" class="fp">

<!-- ===================== HERO ===================== -->
<!-- The hero scales to 1.08 as it scrolls away, which widens its own box past
     the viewport; overflow-hidden on the section clips its children, not the
     section itself. Kept even under #fp, whose panels clip anyway, so the hero
     stays safe if the wrapper is ever removed again. -->
<div class="overflow-hidden">
<?php $heroPoster = ! empty($video['poster_path']) ? $video['poster_path'] : '/media/giantforests/Welcome-to-Giants-Forest-3.jpg'; ?>
<section
    id="hero"
    data-gsap="hero-out"
    x-data="{ playing: ! window.matchMedia('(prefers-reduced-motion: reduce)').matches, toggleVid() { const v = $refs.bgv; if (!v) return; if (v.paused) { delete v.dataset.userPaused; v.play(); this.playing = true; } else { v.dataset.userPaused = '1'; v.pause(); this.playing = false; } } }"
    <?php // The hero is a photograph with type laid over it, so it is a dark
          // surface no matter which theme the rest of the page is in — the same
          // way the hotel's own site puts white type on a dark hero above a
          // light page. .on-dark gives it the deep-forest wash and near-white
          // ink, instead of the cream veil the light theme lays over photos.
    ?>
    class="on-dark relative flex min-h-screen items-center overflow-hidden"
>
    <!-- Background: real launch film if set in the CMS, else the animated brand visual -->
    <?php // A path in the database is not a film on disk. pagehero tests both;
          // this tested only the path, so a record pointing at a file that had
          // been removed rendered a <video> that plays nothing — and because the
          // still is the *else* branch, the poster never got its turn either.
    ?>
    <?php if (! empty($video['src_path']) && is_file(FCPATH . ltrim((string) $video['src_path'], '/'))): ?>
        <!-- Background film. Poster paints instantly (LCP) while it buffers; JS
             force-plays it (see heroVideo.js) so it loops continuously. -->
        <video x-ref="bgv"
               class="hero-media absolute inset-0 -z-30 h-full w-full object-cover"
               autoplay muted loop playsinline preload="auto"
               poster="<?= esc(media_src($heroPoster)) ?>">
            <?php // A browser plays the first source it can decode and never looks
                  // at the rest, so this order decides what almost everyone gets.
                  //
                  // The usual advice is WebM first, on the assumption that VP9
                  // beats H.264. Measured against a common reference, these two
                  // files say otherwise: the MP4 is 7.5MB at SSIM 0.978, while
                  // VP9 needs 8.8MB to reach 0.948 and lands at 0.943 for 6.6MB.
                  // x264 wins on both axes here, partly because the source is
                  // already H.264 at a low bitrate. So MP4 leads.
                  //
                  // The WebM is not redundant: Chromium builds without the
                  // proprietary H.264 decoder — which is what this project's own
                  // test browser is — cannot play the MP4 at all, and fall
                  // through to it. It is a codec fallback, not an optimisation.
                  //
                  // Both are existence-checked. A path in the database with no
                  // file behind it emits a <source> the browser requests and is
                  // 404'd on before falling through: a wasted round trip on every
                  // visit, invisible because the video still plays. ?>
            <source src="<?= esc(media_src($video['src_path'])) ?>" type="video/mp4">
            <?php if (! empty($video['src_path_webm']) && is_file(FCPATH . ltrim((string) $video['src_path_webm'], '/'))): ?>
                <source src="<?= esc(media_src($video['src_path_webm'])) ?>" type="video/webm">
            <?php endif; ?>
        </video>
        <!-- Subtle corner control (kept far from the CTAs so it never competes). -->
        <button type="button" @click="toggleVid()"
                class="absolute bottom-8 right-6 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-brand-black/50 text-white/80 backdrop-blur transition hover:border-white hover:text-white lg:right-10"
                :aria-label="playing ? 'Pause background video' : 'Play background video'">
            <svg x-show="playing" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
            <svg x-show="!playing" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5l12 7-12 7z"/></svg>
        </button>
    <?php elseif (! empty($heroPoster) && is_file(FCPATH . ltrim((string) $heroPoster, '/'))): ?>
        <!-- No film supplied; the poster carries the hero as a still. -->
        <img src="<?= esc(media_src($heroPoster), 'attr') ?>" alt="" aria-hidden="true" data-parallax="12"
             class="hero-media absolute inset-0 -z-30 h-full w-full object-cover">
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-30"></div>
        <div data-three-hero class="absolute inset-0 -z-20 opacity-70"></div>
    <?php endif; ?>

    <!-- Lighter, directional scrim (legible text on the left, the film stays visible on the right) -->
    <div class="hero-wash-pool absolute inset-0 -z-20"></div>
    <div class="hero-wash-foot absolute inset-0 -z-20"></div>
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

        <!-- CTA hierarchy: one dominant primary, one quiet secondary.
             Both are real links first and behave without JavaScript: Book Now
             goes to the contact page, the film link goes to its own section.
             With JavaScript the first opens the booking dialog in place and the
             second scrolls to the film and starts it. A button that only works
             once a bundle has parsed is a button that sometimes does nothing. -->
        <div class="mt-10 flex flex-wrap items-center gap-6" data-gsap="reveal">
            <a href="<?= esc(locale_url('contact')) ?>" class="btn-brand btn-lg group"
               @click.prevent="$dispatch('booking-open')">
                <?= esc(lang('Site.home.hero.primary')) ?>
                <svg class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="#film" class="group inline-flex items-center gap-3 text-sm font-semibold uppercase tracking-widest text-white/85 transition hover:text-white"
               @click="$dispatch('film-play')">
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

    <a href="#welcome" data-fp-next
            class="absolute inset-x-0 bottom-8 flex cursor-pointer justify-center bg-transparent"
            aria-label="<?= esc(lang('Site.experience.scroll'), 'attr') ?>">
        <span class="flex flex-col items-center gap-2 text-[10px] uppercase tracking-[0.3em] text-white/40 transition hover:text-white/70">
            <?= esc(lang('Site.experience.scroll')) ?>
            <svg class="h-4 w-4 animate-bounce text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M6 13l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
    </a>
</section>
</div><!-- /hero clip -->

<!-- ===================== WELCOME ===================== -->
<?php // The anchor lives on the section itself. As its own element it was a
      // direct child of #fp, which counts direct children as panels — so the
      // scroll cue pointed at an empty screen rather than at the welcome. ?>
<?= view('Modules\\Site\\Views\\home\\sections\\welcome', ['section' => $sections['welcome'] ?? null]) ?>

<!-- ===================== OUR FACILITIES ===================== -->
<?= view('Modules\\Site\\Views\\home\\sections\\facilities', ['section' => $sections['facilities'] ?? null]) ?>

<!-- ===================== ROOMS & SUITES ===================== -->
<?= view('Modules\\Site\\Views\\home\\sections\\rooms', ['section' => $sections['rooms'] ?? null]) ?>

<!-- ===================== PEOPLE SAY ===================== -->
<?= view('Modules\\Site\\Views\\home\\sections\\testimonials', ['section' => $sections['testimonials'] ?? null]) ?>

<!-- ===================== THE FILM ===================== -->
<?= view('Modules\\Site\\Views\\home\\sections\\film', ['section' => $sections['film'] ?? null]) ?>

<!-- ===================== BOOK ===================== -->
<?= view('Modules\\Site\\Views\\home\\sections\\cta', ['section' => $sections['cta'] ?? null]) ?>

</div><!-- /#fp -->

<?php // Outside the panels on purpose: #fp is transformed, and a fixed overlay
      // inside a transformed ancestor is positioned against that ancestor
      // instead of the viewport — the dialog would be trapped in one panel. ?>
<?= view('Modules\\Core\\Views\\partials\\booking_modal') ?>

<?= $this->endSection() ?>
