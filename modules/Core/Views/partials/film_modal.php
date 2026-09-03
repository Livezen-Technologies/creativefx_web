<?php helper(['norlanka', 'url']);

/**
 * The hotel film, in a dialog.
 *
 * "Watch the film" used to scroll the reader down to the film section and start
 * it there, which works but takes them away from the hero they were reading.
 * Opening it over the page keeps their place: close it and they are still where
 * they were.
 *
 * Opened by dispatching `film-open` on window, so any button anywhere can open
 * it without knowing anything about this markup. Closing dispatches `film-stop`
 * rather than reaching into the player, because the player owns the <video> and
 * a dialog that only hides itself leaves a film playing behind it.
 *
 * Rendered only when a film is configured, and the whole thing costs nothing
 * until it is opened: the player inside preloads nothing.
 */
$src   = (string) setting('film_mp4', '/media/video/giants-forest-film.mp4', 'media');
$webm  = (string) setting('film_webm', '/media/video/giants-forest-film.webm', 'media');
$still = (string) setting('film_poster', '/media/video/giants-forest-film-poster.jpg', 'media');

if ($src === '' || ! is_file(FCPATH . ltrim($src, '/'))) { return; }
?>
<div x-data="{ isOpen: false,
               open() { this.isOpen = true; document.documentElement.style.overflow = 'hidden'; },
               close() { this.isOpen = false; document.documentElement.style.overflow = '';
                         window.dispatchEvent(new CustomEvent('film-stop')); } }"
     x-on:film-open.window="open()"
     x-on:keydown.escape.window="isOpen && close()">

    <div x-show="isOpen" x-cloak x-transition.opacity
         class="fixed inset-0 z-[90] bg-black/80 backdrop-blur-sm"
         @click="close()" aria-hidden="true"></div>

    <div x-show="isOpen" x-cloak
         class="fixed inset-0 z-[95] flex items-center justify-center p-4 sm:p-8"
         role="dialog" aria-modal="true" aria-label="<?= esc(lang('Site.video.play'), 'attr') ?>">

        <div x-transition @click.outside="close()" class="relative w-full max-w-5xl">
            <button type="button" @click="close()"
                    class="absolute -top-11 right-0 inline-flex h-9 w-9 items-center justify-center rounded-full
                           border border-white/30 text-white transition hover:bg-white/10
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    aria-label="<?= esc(lang('Site.video.close'), 'attr') ?>">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>

            <?php // Mounted only while the dialog is open, so the player starts
                  // from the top each time and no <video> sits in the document
                  // holding a decoder for a film nobody asked for. ?>
            <template x-if="isOpen">
                <div>
                    <?= view('Modules\\Core\\Views\\partials\\video_player', [
                        'src'      => $src,
                        'webm'     => $webm,
                        'poster'   => $still,
                        'label'    => lang('Site.video.play'),
                        'autoplay' => true,
                    ]) ?>
                </div>
            </template>
        </div>
    </div>
</div>
