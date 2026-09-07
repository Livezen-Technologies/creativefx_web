<?php helper('norlanka');

/**
 * The wordmark, drawn rather than fetched.
 *
 * Which colourway is correct depends on the ground it lands on, and that is not
 * known when the template is written: the same header is transparent over a
 * photograph at the top of a page and solid once scrolled, and a visitor can
 * switch the whole site to dark at any moment. Picking one in PHP gets it wrong
 * half the time, and the failure is silent — an invisible logo still occupies
 * its space and still passes every check that asks whether the image loaded.
 *
 * So the mark is inlined from the theme's own tokens. That is not only fewer
 * bytes:
 *
 *   - No request at all, where an <img> pair means fetching both colourways on
 *     every page and showing one.
 *   - No aspect ratio to declare, and so none to get wrong. Declaring the wrong
 *     one makes the mark jump the moment the file arrives, on every page, in
 *     the header, the footer and the loading screen.
 *   - Switching to dark recolours it in the same frame as everything else,
 *     rather than fetching a second file to catch up.
 *
 * A logo uploaded under Settings → Brand cannot be inlined — it may be a PNG,
 * and it is not ours to recolour — so that path still renders two images.
 *
 * The mark comes in two crops. `full` is the badge and the wordmark; `mark` is
 * the badge alone, for the places too narrow to carry a name — the header on a
 * phone, where the full lockup is 216px wide beside 173px of controls on a
 * 390px screen and pushed the menu button off the edge of the display.
 *
 * Both crops render the same drawing and differ only in the viewBox, so there
 * is one set of paths to keep correct rather than two that can drift.
 *
 * @var string $class    utility classes for the sizing of this instance
 * @var string $variant  'full' (default) or 'mark'
 */
$class   = $class ?? 'h-9 w-auto';
$variant = ($variant ?? 'full') === 'mark' ? 'mark' : 'full';
$alt     = setting('site_name', '');

// 480×120 is the whole lockup. 104×120 is the badge with its own margin, which
// is where the wordmark begins.
$viewBox = $variant === 'mark' ? '0 0 104 120' : '0 0 480 120';

$light = trim((string) setting('logo_color', '', 'brand'));
$dark  = trim((string) setting('logo_white', '', 'brand'));

// Both blank means nobody has replaced the mark, so the shipped one is in use
// and can be drawn from the tokens.
if ($light === '' && $dark === ''):
?>
<svg class="brand-logo brand-logo--inline <?= esc($class, 'attr') ?>"
     viewBox="<?= esc($viewBox, 'attr') ?>" role="img" aria-label="<?= esc($alt, 'attr') ?>"
     xmlns="http://www.w3.org/2000/svg" focusable="false">
    <?php // The badge. A rounded square rather than a circle: it sits beside a
          // wordmark whose letterforms are square-ish, and a circle floats. ?>
    <rect x="8" y="16" width="88" height="88" rx="26"
          fill="none" stroke="rgb(var(--accent))" stroke-width="5"/>

    <?php // A plus, because the name is "learn *plus*" — and because a plus is
          // the one glyph that means "more" in both of the things this school
          // teaches. The four short rays turn it into a spark, which is the
          // nod to the AI half without drawing a robot. ?>
    <g stroke="rgb(var(--accent))" stroke-width="9" stroke-linecap="round">
        <path d="M52 40v40"/>
        <path d="M32 60h40"/>
    </g>
    <g stroke="rgb(var(--gold))" stroke-width="5" stroke-linecap="round" opacity="0.9">
        <path d="M36.5 44.5l-6-6"/>
        <path d="M67.5 44.5l6-6"/>
        <path d="M36.5 75.5l-6 6"/>
        <path d="M67.5 75.5l6 6"/>
    </g>

    <?php // rgb(var(--fg)) rather than currentColor: the surrounding text is
          // often set at an alpha — the nav links are --fg at 0.9 — and the
          // wordmark would quietly inherit the transparency with it. ?>
    <?php if ($variant === 'full'): ?>
    <g transform="translate(118 0)" font-family="'Noto Sans','Segoe UI',system-ui,sans-serif">
        <text x="0" y="58" font-size="40" font-weight="700" letter-spacing="-0.4">
            <tspan fill="rgb(var(--fg))">MyLearn</tspan><tspan fill="rgb(var(--accent))">Plus</tspan>
        </text>
        <text x="2" y="84" font-size="12.5" font-weight="600" letter-spacing="1.6"
              fill="rgb(var(--fg))" opacity="0.7">ADOBE &amp; AI TRAINING</text>
    </g>
    <?php endif; ?>
</svg>
<?php else:
    // A replaced mark. Both colourways are settings, so swapping the logo is an
    // upload rather than a deploy; the shipped files remain the fallback for
    // whichever half has not been replaced.
    $light = $light ?: '/media/learnplus/logo.svg';
    $dark  = $dark ?: '/media/learnplus/logo-white.svg';
?>
<span class="brand-logo">
    <?php // 480×120 is the shipped artwork's own shape. It is still a guess for
          // an uploaded file of unknown proportions, but a guess close to the
          // shape most wordmarks have beats a square, which is the shape of
          // nothing. ?>
    <img class="brand-logo__on-light <?= esc($class, 'attr') ?>"
         src="<?= esc(media_src($light), 'attr') ?>"
         alt="<?= esc($alt, 'attr') ?>" width="480" height="120">
    <img class="brand-logo__on-dark <?= esc($class, 'attr') ?>"
         src="<?= esc(media_src($dark), 'attr') ?>"
         alt="" aria-hidden="true" width="480" height="120">
</span>
<?php endif; ?>
