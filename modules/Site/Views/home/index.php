<?php
helper('norlanka');
$this->extend('Modules\Core\Views\layouts\main');

// --- Hero content (from the seeded CMS 'hero' block) ---
$heroRaw      = $sections['hero']['blocks'][0]['content'] ?? null;
$hero         = $heroRaw ? json_decode($heroRaw, true) : [];
$heroHeadline = $hero['headline'] ?? ['en' => 'Norlanka'];
$heroSubhead  = $hero['subhead'] ?? [];

// --- Launch video: audio tracks + subtitle files ---
$tracks       = $video['tracks'] ?? [];
$subsByLocale = [];
foreach ($video['subtitles'] ?? [] as $s) {
    $subsByLocale[$s['locale']] = $s;
}
$trackCodes  = array_column($tracks, 'locale');
$defaultExp  = in_array(current_locale(), $trackCodes, true) ? current_locale() : ($trackCodes[0] ?? 'en');
$expConfig   = [
    'defaultLocale' => $defaultExp,
    'locales'       => array_map(static fn ($t) => ['code' => $t['locale'], 'label' => $t['label']], $tracks),
];
?>

<?= $this->section('content') ?>
<section
    id="hero"
    data-gsap="hero-out"
    x-data="videoExperience(<?= esc(json_encode($expConfig, JSON_UNESCAPED_UNICODE), 'attr') ?>)"
    class="relative flex min-h-screen items-center overflow-hidden"
>
    <!-- Animated brand visual (placeholder until a launch film is set in the CMS) -->
    <div class="hero-aurora absolute inset-0 -z-20"></div>
    <div data-three-hero class="absolute inset-0 -z-10 opacity-70"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-black/30 via-black/20 to-brand-black"></div>

    <?php if (! empty($video['src_path'])): ?>
        <video x-ref="video" class="absolute inset-0 -z-10 h-full w-full object-cover"
               muted loop playsinline preload="auto"
               poster="<?= esc($video['poster_path'] ?? '') ?>">
            <source src="<?= esc($video['src_path']) ?>" type="video/mp4">
            <?php if (! empty($video['src_path_webm'])): ?>
                <source src="<?= esc($video['src_path_webm']) ?>" type="video/webm">
            <?php endif; ?>
        </video>
    <?php else: ?>
        <video x-ref="video" class="hidden"></video>
    <?php endif; ?>

    <!-- One <audio> per language, each carrying its WebVTT subtitle track -->
    <?php foreach ($tracks as $t): $sub = $subsByLocale[$t['locale']] ?? null; ?>
        <audio x-ref="audio_<?= esc($t['locale'], 'attr') ?>" preload="auto">
            <source src="<?= esc($t['audio_path']) ?>" type="audio/wav">
            <?php if ($sub): ?>
                <track kind="subtitles" src="<?= esc($sub['vtt_path']) ?>"
                       srclang="<?= esc($t['locale'], 'attr') ?>" label="<?= esc($t['label']) ?>">
            <?php endif; ?>
        </audio>
    <?php endforeach; ?>

    <!-- Overlay content -->
    <div class="container-x relative w-full pt-28">
        <p class="mb-4 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red" data-gsap="reveal">Norlanka</p>
        <h1 class="max-w-4xl text-4xl font-bold leading-[1.05] sm:text-6xl" data-gsap="reveal"><?= esc(t_field($heroHeadline)) ?></h1>
        <p class="mt-6 max-w-2xl text-lg text-white/70" data-gsap="reveal"><?= esc(t_field($heroSubhead)) ?></p>

        <div class="mt-10 flex flex-wrap items-center gap-4" data-gsap="reveal">
            <button type="button" class="btn-brand" @click="togglePlay()">
                <span x-show="!started"><?= esc(lang('Site.experience.play')) ?></span>
                <span x-show="started &amp;&amp; playing" x-cloak><?= esc(lang('Site.experience.pause')) ?></span>
                <span x-show="started &amp;&amp; !playing" x-cloak><?= esc(lang('Site.experience.resume')) ?></span>
            </button>
            <button type="button" class="btn-ghost" @click="toggleSubtitles()" :class="subtitlesOn ? '' : 'opacity-50'">
                <?= esc(lang('Site.experience.subtitles')) ?>
            </button>
        </div>

        <!-- Netflix-style audio language switch -->
        <div class="mt-10" data-gsap="reveal">
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.3em] text-white/40"><?= esc(lang('Site.experience.language')) ?></p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($tracks as $t): ?>
                    <button type="button"
                            class="lang-pill border border-white/15"
                            @click="switchLanguage('<?= esc($t['locale'], 'attr') ?>')"
                            :class="current === '<?= esc($t['locale'], 'attr') ?>' ? 'lang-pill-active' : ''"><?= esc($t['label']) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Manually-rendered subtitle caption (works without a <video> surface) -->
    <div class="pointer-events-none absolute inset-x-0 bottom-28 flex justify-center px-6" x-show="caption" x-cloak x-transition.opacity>
        <p class="rounded-lg bg-black/70 px-5 py-2 text-center text-lg font-medium backdrop-blur" x-text="caption"></p>
    </div>

    <div class="absolute inset-x-0 bottom-8 flex justify-center">
        <span class="animate-bounce text-[10px] uppercase tracking-[0.3em] text-white/40"><?= esc(lang('Site.experience.scroll')) ?></span>
    </div>
</section>

<?= $this->include('Modules\Site\Views\home\sections\pillars', ['section' => $sections['pillars'] ?? null]) ?>
<?= $this->include('Modules\Site\Views\home\sections\stats', ['section' => $sections['stats'] ?? null]) ?>
<?= $this->include('Modules\Site\Views\home\sections\cta', ['section' => $sections['cta'] ?? null]) ?>
<?= $this->endSection() ?>
