<?php helper('norlanka');

/**
 * The site's own video player.
 *
 * Replaces a YouTube embed, which brought a channel header, an end-card grid of
 * unrelated videos, a red progress bar and a logo onto a page that is supposed
 * to be the hotel's. The film is the hotel's own file, so it is served from the
 * hotel's own domain with the hotel's own controls over it.
 *
 * Nothing loads until somebody presses play: preload="none" plus a poster means
 * a visitor who never reaches this section pays nothing for it, which is the
 * one thing the YouTube facade did get right.
 *
 * @var string      $src      path to the MP4
 * @var string|null $webm     optional VP9 fallback for builds without H.264
 * @var string|null $poster   still shown before playback
 * @var string      $label    accessible name for the film
 * @var bool        $autoplay start as soon as the player is rendered
 */
$src      = (string) ($src ?? '');
$webm     = (string) ($webm ?? '');
$poster   = (string) ($poster ?? '');
$label    = (string) ($label ?? lang('Site.video.play'));
$autoplay = (bool) ($autoplay ?? false);

// A path in the database is not a film on disk. Rendering a <source> with
// nothing behind it costs a round trip on every visit and shows an empty black
// box at the end of it.
if ($src === '' || ! is_file(FCPATH . ltrim($src, '/'))) { return; }
$hasWebm = $webm !== '' && is_file(FCPATH . ltrim($webm, '/'));
?>
<div class="vp group relative aspect-video w-full overflow-hidden rounded-sm bg-black"
     x-data="videoPlayer()" x-ref="shell"
     @mousemove="showControls()" @touchstart="showControls()"
     @keydown.space.prevent="toggle()" @keydown.left.prevent="skip(-5)" @keydown.right.prevent="skip(5)"
     x-on:film-stop.window="stop()">

    <video x-ref="v" class="absolute inset-0 h-full w-full bg-black object-contain"
           playsinline preload="<?= $autoplay ? 'auto' : 'none' ?>"<?= $autoplay ? ' autoplay' : '' ?>
           <?php if ($poster !== ''): ?>poster="<?= esc(media_src($poster), 'attr') ?>"<?php endif; ?>
           @click="toggle()">
        <?php // MP4 first: measured against a common reference on this project's
              // own footage x264 beat VP9 on both size and SSIM. The WebM is a
              // codec fallback, not an optimisation — Chromium builds without
              // the proprietary H.264 decoder cannot play the MP4 at all. ?>
        <source src="<?= esc(media_src($src), 'attr') ?>" type="video/mp4">
        <?php if ($hasWebm): ?>
            <source src="<?= esc(media_src($webm), 'attr') ?>" type="video/webm">
        <?php endif; ?>
    </video>

    <!-- Poster overlay: one big target, gone the moment the film starts. -->
    <button type="button" x-show="!playing && current === 0" @click="play()"
            class="absolute inset-0 z-10 flex h-full w-full cursor-pointer items-center justify-center bg-black/25 transition hover:bg-black/35"
            aria-label="<?= esc($label, 'attr') ?>">
        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-white/95 shadow-xl transition group-hover:scale-105">
            <svg class="ml-1 h-8 w-8 text-[rgb(var(--forest))]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M7 5l12 7-12 7z"/>
            </svg>
        </span>
    </button>

    <!-- Control bar -->
    <div class="absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-black/85 via-black/45 to-transparent px-3 pb-2.5 pt-8 transition-opacity duration-300 sm:px-4"
         :class="controlsVisible || !playing ? 'opacity-100' : 'pointer-events-none opacity-0'">

        <?php // A range input is the scrubber, so dragging, arrow keys, Home and
              // End all work for free and a screen reader is told a value.
              // The filled and buffered bars are drawn behind it. ?>
        <div class="vp-track relative flex h-4 items-center">
            <span class="absolute inset-x-0 h-1 rounded-full bg-white/25"></span>
            <span class="absolute left-0 h-1 rounded-full bg-white/35" :style="`width:${bufferedPercent}%`"></span>
            <?php // The played portion, in the brand accent rather than the red
                  // bar the embed used to draw. ?>
            <span class="absolute left-0 h-1 rounded-full bg-[rgb(var(--accent))]" :style="`width:${percent}%`"></span>
            <input type="range" class="vp-range absolute inset-x-0 w-full" min="0" step="0.1"
                   :max="duration || 0" :value="current"
                   @input="scrubbing = true; seek($event)"
                   @change="scrubbing = false; seek($event)"
                   :aria-valuetext="`${clock(current)} of ${clock(duration)}`"
                   aria-label="<?= esc(lang('Site.video.seek'), 'attr') ?>">
        </div>

        <div class="mt-1 flex items-center gap-2 text-white sm:gap-3">
            <button type="button" @click="toggle()" class="vp-btn"
                    :aria-label="playing ? '<?= esc(lang('Site.video.pause'), 'attr') ?>' : '<?= esc(lang('Site.video.play'), 'attr') ?>'">
                <svg x-show="!playing" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5l12 7-12 7z"/></svg>
                <svg x-show="playing" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
            </button>

            <button type="button" @click="toggleMute()" class="vp-btn"
                    :aria-label="muted ? '<?= esc(lang('Site.video.unmute'), 'attr') ?>' : '<?= esc(lang('Site.video.mute'), 'attr') ?>'">
                <svg x-show="!muted" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4zm12.5 3a4.5 4.5 0 00-2.5-4v8a4.5 4.5 0 002.5-4z"/></svg>
                <svg x-show="muted" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4zm14.6 1.6l-1.4-1.4-1.7 1.7-1.7-1.7-1.4 1.4 1.7 1.7-1.7 1.7 1.4 1.4 1.7-1.7 1.7 1.7 1.4-1.4-1.7-1.7z"/></svg>
            </button>

            <p class="ml-1 select-none font-mono text-xs tabular-nums text-white/85">
                <span x-text="clock(current)">0:00</span><span class="text-white/45"> / </span><span x-text="clock(duration)">0:00</span>
            </p>

            <span class="flex-1"></span>

            <button type="button" @click="toggleFullscreen()" class="vp-btn"
                    :aria-label="fullscreen ? '<?= esc(lang('Site.video.exit_fullscreen'), 'attr') ?>' : '<?= esc(lang('Site.video.fullscreen'), 'attr') ?>'">
                <svg x-show="!fullscreen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
                <svg x-show="fullscreen" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/></svg>
            </button>
        </div>
    </div>
</div>
