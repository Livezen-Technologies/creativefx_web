<?php helper('norlanka');

/**
 * Corporate video block. Accepts either source, `embed` taking precedence:
 *
 *   src   — a self-hosted MP4, played inline with the native controls.
 *   embed — a Vimeo or YouTube link (ordinary share URLs are fine, including
 *           ?share=copy); rendered as a responsive, privacy-mode iframe.
 */
$embedUrl = null;
$provider = null;

if (! empty($content['embed'])) {
    $raw = trim((string) $content['embed']);

    if (preg_match('~(?:vimeo\.com/(?:video/)?|player\.vimeo\.com/video/)(\d+)~i', $raw, $m)) {
        // dnt=1 stops Vimeo setting tracking cookies; the chrome is hidden so
        // the player reads as part of the page rather than as an advert for Vimeo.
        $provider = 'Vimeo';
        $embedUrl = 'https://player.vimeo.com/video/' . $m[1] . '?title=0&byline=0&portrait=0&dnt=1';
    } elseif (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w-]{11})~i', $raw, $m)) {
        $provider = 'YouTube';
        $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0';
    }
}

if ($embedUrl === null && empty($content['src'])) {
    return;
}
?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-8 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <figure class="isolate overflow-hidden rounded-3xl border border-white/10" data-gsap="reveal">
            <?php if ($embedUrl !== null): ?>
                <div class="aspect-video w-full bg-black">
                    <iframe src="<?= esc($embedUrl, 'attr') ?>"
                            title="<?= esc(t_field($content['title'] ?? []) ?: $provider . ' video', 'attr') ?>"
                            class="h-full w-full" style="border:0" loading="lazy"
                            allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <video controls preload="metadata" playsinline class="aspect-video w-full bg-black object-cover"
                       <?= ! empty($content['poster']) ? 'poster="' . esc($content['poster'], 'attr') . '"' : '' ?>>
                    <source src="<?= esc($content['src'], 'attr') ?>" type="video/mp4">
                </video>
            <?php endif; ?>
            <?php if (! empty($content['caption'])): ?>
                <figcaption class="px-6 py-4 text-sm text-white/55"><?= esc(t_field($content['caption'])) ?></figcaption>
            <?php endif; ?>
        </figure>
    </div>
</section>
