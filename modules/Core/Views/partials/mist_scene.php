<?php helper('norlanka');

/**
 * A jungle photograph with mist drifting across it.
 *
 * The mist is three soft gradient clouds moving very slowly against each other
 * on long, mismatched cycles, so the loop never lands in the same place twice
 * and never reads as a repeat. It is CSS, not video and not a canvas: nothing
 * to download beyond the photograph, and nothing running on the main thread.
 *
 * A wash sits over the whole thing, keyed to the page's own --hero-wash, so the
 * copy laid on top keeps its contrast whichever theme the reader is in — the
 * band this replaces was a flat gradient precisely because a photograph behind
 * text is only safe if something guarantees the ratio.
 *
 * Honours prefers-reduced-motion: the scene stays, the drift stops.
 *
 * @var string $image  site-absolute path to the photograph
 * @var string $alt    describes the photograph, or '' to mark it decorative
 */
$image = (string) ($image ?? '');
if ($image === '' || ! is_file(FCPATH . ltrim($image, '/'))) { return; }
$alt = (string) ($alt ?? '');
?>
<img src="<?= esc(media_src($image), 'attr') ?>"
     <?= $alt === '' ? 'alt="" aria-hidden="true"' : 'alt="' . esc($alt, 'attr') . '"' ?>
     loading="lazy" decoding="async" width="1920" height="1080" class="mist-photo">
<span class="mist-veil" aria-hidden="true"></span>
<span class="mist-veil mist-veil--slow" aria-hidden="true"></span>
<span class="mist-floor" aria-hidden="true"></span>
