<?php helper('norlanka');

/**
 * The wordmark, in whichever of its two colourways can actually be seen.
 *
 * The hotel supplies the mark drawn in the brand green and a white knockout of
 * it. Which one is correct depends on the ground it lands on, and that is not
 * known when the template is written: the same header is transparent over a
 * dark photograph at the top of a page and solid cream once it is scrolled, and
 * a visitor can switch the whole site to dark at any moment. Picking one in PHP
 * gets it wrong half the time — and the failure is silent, because an invisible
 * logo still occupies its space and still passes every check that asks whether
 * the image loaded.
 *
 * So both are rendered and CSS shows the one that reads, keyed on the same
 * .on-dark / html.dark scopes the colour tokens already use. Only one is ever
 * displayed, and the second is a 17KB PNG the browser fetches once for the life
 * of the session.
 *
 * @var string $class  utility classes for the sizing of this instance
 */
$class = $class ?? 'h-9 w-auto';
$alt   = setting('site_name', '');
?>
<span class="brand-logo">
    <img class="brand-logo__on-light <?= esc($class, 'attr') ?>"
         src="<?= esc(media_src('/media/giantforests/Kukuleganga-Giants-Forest-Logo-1.png'), 'attr') ?>"
         alt="<?= esc($alt, 'attr') ?>" width="300" height="200">
    <img class="brand-logo__on-dark <?= esc($class, 'attr') ?>"
         src="<?= esc(media_src('/media/giantforests/Kukuleganga-Giants-Forest-Logo-white.png'), 'attr') ?>"
         alt="" aria-hidden="true" width="300" height="200">
</span>
