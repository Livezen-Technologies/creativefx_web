<?php helper(['norlanka', 'url']); if (empty($section['blocks'])) { return; }

/**
 * The hotel's film, as the source site carries it: a YouTube video rather than
 * a file. It is embedded rather than downloaded, which is both what the hotel
 * already does and the only way it is theirs to publish.
 *
 * Two details decide whether this works:
 *
 * 1. loop=1 does nothing on its own for a single video. YouTube only repeats a
 *    playlist, so the video has to name itself as a one-item playlist. Miss the
 *    playlist parameter and the film plays once and stops on an end card, which
 *    looks like the loop was never asked for.
 *
 * 2. Nothing is loaded from YouTube until somebody asks for it. A bare iframe
 *    pulls several hundred kilobytes and sets cookies on every visit to the
 *    home page, including the visits that never reach this far down. The poster
 *    is one of the hotel's own photographs, so the section is complete before
 *    any third party is involved.
 *
 * autoplay=1 is honest here: it follows the visitor's click, so it is not the
 * autoplay a browser blocks, and without it the click would only load a player
 * they then have to press again.
 */
$c  = json_decode($section['blocks'][0]['content'] ?? '[]', true) ?: [];
$id = trim((string) ($c['youtube_id'] ?? ''));
if ($id === '' || ! preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) { return; }

$src = 'https://www.youtube-nocookie.com/embed/' . $id . '?' . http_build_query([
    'autoplay'       => 1,
    'loop'           => 1,
    'playlist'       => $id,   // the one-item playlist that makes loop=1 mean something
    'rel'            => 0,
    'modestbranding' => 1,
    'playsinline'    => 1,
]);
$label = t_field($c['title'] ?? []) ?: 'Play the hotel film';
?>
<section id="film" class="scroll-mt-24 bg-brand-black py-20 sm:py-28">
    <div class="container-x">

        <div class="text-center" data-gsap="reveal">
            <?php if (! empty($c['eyebrow'])): ?>
                <p class="eyebrow justify-center"><?= esc(t_field($c['eyebrow'])) ?></p>
            <?php endif; ?>
            <?php if (! empty($c['title'])): ?>
                <h2 class="mt-5 text-3xl font-bold sm:text-5xl"><?= esc(t_field($c['title'])) ?></h2>
            <?php endif; ?>
        </div>

        <!-- The hero's film link scrolls here and starts it in one go, so the
             reader is not asked to press play a second time on arrival. -->
        <div class="mx-auto mt-12 max-w-5xl" data-gsap="reveal"
             x-data="{ playing: false }"
             x-on:film-play.window="playing = true">
            <div class="relative aspect-video w-full overflow-hidden rounded-sm bg-black/40" data-parallax="8">

                <template x-if="playing">
                    <iframe class="absolute inset-0 h-full w-full"
                            src="<?= esc($src, 'attr') ?>"
                            title="<?= esc($label, 'attr') ?>"
                            loading="lazy" frameborder="0" allowfullscreen
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </template>

                <button type="button" x-show="!playing" @click="playing = true"
                        class="group absolute inset-0 h-full w-full cursor-pointer"
                        aria-label="<?= esc($label, 'attr') ?>">
                    <?php if (! empty($c['poster'])): ?>
                        <img src="<?= esc(media_src($c['poster']), 'attr') ?>" alt="" aria-hidden="true"
                             loading="lazy" width="1280" height="720"
                             class="absolute inset-0 h-full w-full object-cover">
                    <?php endif; ?>
                    <span class="absolute inset-0 bg-black/25 transition group-hover:bg-black/35"></span>
                    <span class="absolute left-1/2 top-1/2 flex h-20 w-20 -translate-x-1/2 -translate-y-1/2
                                 items-center justify-center rounded-full bg-white/95 shadow-xl transition
                                 group-hover:scale-105">
                        <svg class="ml-1 h-8 w-8 text-[rgb(var(--forest))]" viewBox="0 0 24 24" fill="currentColor"
                             aria-hidden="true"><path d="M7 5l12 7-12 7z"/></svg>
                    </span>
                </button>

            </div>
        </div>

        <?php if (! empty($c['button'])): ?>
            <div class="mt-12 text-center" data-gsap="reveal">
                <a href="<?= esc(locale_url($c['url'] ?? 'gallery')) ?>" class="btn-brand">
                    <?= esc(t_field($c['button'])) ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
