<?php helper(['norlanka', 'url']); if (empty($section['blocks'])) { return; }

/**
 * The hotel's film.
 *
 * This was a YouTube embed, which is how the source site carries it. The embed
 * works, but everything around the picture belongs to YouTube: a channel header
 * over the top of the frame, a red progress bar, a logo in the corner and, when
 * the film ends, a grid of unrelated videos to click on. On the hotel's own
 * page that reads as somebody else's product playing inside it, and the last
 * thing a visitor sees is an invitation to leave.
 *
 * The film is the hotel's own footage, so it is served from the hotel's own
 * domain through the site's own player. Nothing loads until somebody presses
 * play — the poster is one of the hotel's photographs and the <video> preloads
 * nothing — which is the one thing the facade did get right and is kept.
 */
$c      = json_decode($section['blocks'][0]['content'] ?? '[]', true) ?: [];
$src    = (string) ($c['video'] ?? '');
$webm   = (string) ($c['video_webm'] ?? '');
$poster = (string) ($c['poster'] ?? '');
$label  = t_field($c['title'] ?? []) ?: lang('Site.video.play');

if ($src === '') { return; }
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

        <div class="mx-auto mt-12 max-w-5xl" data-gsap="reveal">
            <?= view('Modules\\Core\\Views\\partials\\video_player', [
                'src'    => $src,
                'webm'   => $webm,
                'poster' => $poster,
                'label'  => $label,
            ]) ?>
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
