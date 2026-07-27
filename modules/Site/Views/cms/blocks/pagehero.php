<?php helper('norlanka');
// Optional background film (set per page in the CMS). Falls back to the
// animated aurora when no video is configured, so every page hero still works.
$heroVideo  = $content['video'] ?? null;
$heroPoster = $content['poster'] ?? null;
$hasVideo   = ! empty($heroVideo) && is_file(FCPATH . ltrim((string) $heroVideo, '/'));
?>
<section class="relative overflow-hidden <?= $hasVideo ? 'hero-full flex items-end' : '' ?>"
         <?= $hasVideo ? 'x-data="{ playing: true, toggleVid() { const v = $refs.bgv; if (!v) return; if (v.paused) { delete v.dataset.userPaused; v.play(); this.playing = true; } else { v.dataset.userPaused = \'1\'; v.pause(); this.playing = false; } } }"' : '' ?>>
    <?php if ($hasVideo): ?>
        <!-- Background film. The poster paints immediately (LCP) while the
             video buffers; heroVideo.js force-plays and loops it. -->
        <video x-ref="bgv" data-hero-video
               class="absolute inset-0 -z-30 h-full w-full object-cover"
               autoplay muted loop playsinline preload="auto"
               <?= $heroPoster ? 'poster="' . esc($heroPoster, 'attr') . '"' : '' ?>>
            <source src="<?= esc($heroVideo, 'attr') ?>" type="video/mp4">
        </video>
        <!-- Scrims: keep the copy legible while the film stays visible. -->
        <div class="absolute inset-0 -z-20 bg-gradient-to-r from-brand-black/95 via-brand-black/55 to-brand-black/20"></div>
        <div class="absolute inset-0 -z-20 bg-gradient-to-t from-brand-black via-brand-black/25 to-transparent"></div>
        <div class="hero-red-glow absolute inset-0 -z-10"></div>

        <button type="button" @click="toggleVid()"
                class="absolute bottom-8 right-6 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-brand-black/50 text-white/80 backdrop-blur transition hover:border-white hover:text-white lg:right-10"
                :aria-label="playing ? 'Pause background video' : 'Play background video'">
            <svg x-show="playing" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
            <svg x-show="!playing" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5l12 7-12 7z"/></svg>
        </button>
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-20"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-black/40 via-brand-black/10 to-brand-black"></div>
    <?php endif; ?>

    <div class="container-x flex <?= $hasVideo ? 'w-full pb-28' : 'min-h-[60vh] pb-16' ?> flex-col justify-end pt-40">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="mb-4 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <h1 class="max-w-4xl text-4xl font-bold leading-[1.05] sm:text-6xl" data-gsap="reveal"><?= esc(t_field($content['title'] ?? [])) ?></h1>
        <?php if (! empty($content['subtitle'])): ?>
            <p class="mt-6 max-w-2xl text-lg text-white/70" data-gsap="reveal"><?= esc(t_field($content['subtitle'])) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($hasVideo): ?>
        <!-- A full-viewport hero hides everything below it — give the reader a cue. -->
        <button type="button"
                onclick="this.closest('section').nextElementSibling?.scrollIntoView({behavior:'smooth',block:'start'})"
                class="absolute inset-x-0 bottom-8 flex cursor-pointer justify-center bg-transparent"
                aria-label="<?= esc(lang('Site.experience.scroll'), 'attr') ?>">
            <span class="flex flex-col items-center gap-2 text-[10px] uppercase tracking-[0.3em] text-white/40 transition hover:text-white/70">
                <?= esc(lang('Site.experience.scroll')) ?>
                <svg class="h-4 w-4 animate-bounce text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M6 13l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        </button>
    <?php endif; ?>
</section>
