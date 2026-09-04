<?php helper('norlanka');

/**
 * The wordmark, in whichever of its two colourways can actually be seen.
 *
 * Which colourway is correct depends on the ground it lands on, and that is not
 * known when the template is written: the same header is transparent over a
 * photograph at the top of a page and solid once scrolled, and a visitor can
 * switch the whole site to dark at any moment. Picking one in PHP gets it wrong
 * half the time — and the failure is silent, because an invisible logo still
 * occupies its space and still passes every check that asks whether the image
 * loaded.
 *
 * The two shipped files differ in exactly three colours, and those three are
 * the theme's own tokens: the green is --accent, the gold is --gold, the ink is
 * --fg. So the mark is drawn inline from those tokens rather than fetched
 * twice. That is not only fewer bytes:
 *
 *   - No request at all, where before every page fetched both colourways and
 *     showed one.
 *   - No aspect ratio to declare, and so none to get wrong. The <img> version
 *     said 300×200 for artwork that is 460×120, so the browser reserved a box
 *     of the wrong shape and the mark jumped the moment the file arrived — on
 *     every page, in the header, the footer and the loading screen.
 *   - Switching to dark recolours it in the same frame as everything else,
 *     rather than fetching a second file to catch up.
 *
 * A logo uploaded under Settings → Brand cannot be inlined — it may be a PNG,
 * and it is not ours to recolour — so that path still renders the two images,
 * now with the aspect ratio the artwork actually has.
 *
 * @var string $class  utility classes for the sizing of this instance
 */
$class = $class ?? 'h-9 w-auto';
$alt   = setting('site_name', '');

$light = trim((string) setting('logo_color', '', 'brand'));
$dark  = trim((string) setting('logo_white', '', 'brand'));

// Both blank means nobody has replaced the mark, so the shipped one is in use
// and can be drawn from the tokens.
if ($light === '' && $dark === ''):
?>
<svg class="brand-logo brand-logo--inline <?= esc($class, 'attr') ?>"
     viewBox="0 0 460 120" role="img" aria-label="<?= esc($alt, 'attr') ?>"
     xmlns="http://www.w3.org/2000/svg" focusable="false">
    <g transform="translate(6 10)">
        <circle cx="50" cy="50" r="48" fill="none" stroke="rgb(var(--accent))" stroke-width="3"/>
        <circle cx="50" cy="50" r="41" fill="none" stroke="rgb(var(--gold))" stroke-width="1.5"/>
        <?php // Two tea leaves and a bud: the plucking standard, and the sector's own emblem. ?>
        <path d="M50 22c0 12-4 20-11 27-5 5-11 8-16 9 0-12 4-21 11-28 5-5 11-7 16-8Z" fill="rgb(var(--accent))"/>
        <path d="M50 22c0 12 4 20 11 27 5 5 11 8 16 9 0-12-4-21-11-28-5-5-11-7-16-8Z" fill="rgb(var(--accent))" opacity="0.75"/>
        <path d="M50 44c4 5 6 12 6 19 0 6-1 11-3 15-2-4-3-9-3-15 0-7 2-14 3-19Z" fill="rgb(var(--gold))"/>
        <path d="M28 74c8 6 15 8 22 8s14-2 22-8" fill="none" stroke="rgb(var(--accent))" stroke-width="3" stroke-linecap="round"/>
    </g>
    <?php // rgb(var(--fg)) rather than currentColor: the surrounding text is
          // often set at an alpha — the nav links are --fg at 0.9 — and the
          // wordmark would quietly inherit the transparency with it. ?>
    <g transform="translate(118 0)" fill="rgb(var(--fg))" font-family="'Noto Sans','Segoe UI',system-ui,sans-serif">
        <text x="0" y="52" font-size="40" font-weight="700" letter-spacing="1.5">TSHDA</text>
        <text x="2" y="76" font-size="12.5" font-weight="600" letter-spacing="0.6" opacity="0.85">Tea Small Holdings</text>
        <text x="2" y="93" font-size="12.5" font-weight="600" letter-spacing="0.6" opacity="0.85">Development Authority</text>
    </g>
</svg>
<?php else:
    // A replaced mark. Both colourways are settings, so swapping the logo is an
    // upload rather than a deploy; the shipped files remain the fallback for
    // whichever half has not been replaced.
    $light = $light ?: '/media/tshda/tshda-logo.svg';
    $dark  = $dark ?: '/media/tshda/tshda-logo-white.svg';
?>
<span class="brand-logo">
    <?php // 460×120 is the shipped artwork's own shape. It is still a guess for
          // an uploaded file of unknown proportions, but a guess close to the
          // shape most wordmarks have beats the 300×200 that was here, which
          // was the shape of nothing. ?>
    <img class="brand-logo__on-light <?= esc($class, 'attr') ?>"
         src="<?= esc(media_src($light), 'attr') ?>"
         alt="<?= esc($alt, 'attr') ?>" width="460" height="120">
    <img class="brand-logo__on-dark <?= esc($class, 'attr') ?>"
         src="<?= esc(media_src($dark), 'attr') ?>"
         alt="" aria-hidden="true" width="460" height="120">
</span>
<?php endif; ?>
