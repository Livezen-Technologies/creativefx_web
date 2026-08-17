<?php
/**
 * The offline page (HTTP 503).
 *
 * Deliberately standalone — no layout, no Vite bundle, no webfonts, no image
 * that has to exist. Maintenance mode is often on *because* something below
 * is broken, so this page draws with nothing but the markup it carries.
 *
 * @var string       $locale
 * @var string       $brand
 * @var string       $headline
 * @var string       $message
 * @var string       $until
 * @var string       $email
 * @var list<string> $locales
 */

// Labels for the two optional lines, so the page stays in one language.
$labels = [
    'en' => ['Expected back', 'Need us sooner?', 'Language'],
    'si' => ['නැවත එන වේලාව', 'ඉක්මනින් අවශ්‍යද?', 'භාෂාව'],
    'ta' => ['திரும்பும் நேரம்', 'உடனடியாகத் தேவையா?', 'மொழி'],
];
[$untilLabel, $contactLabel, $langLabel] = $labels[$locale] ?? $labels['en'];

$names = ['en' => 'English', 'si' => 'සිංහල', 'ta' => 'தமிழ்'];

// Shown only if the deployment actually ships it.
$symbol = is_file(FCPATH . 'media/brand/cfx-symbol.svg') ? '/media/brand/cfx-symbol.svg' : null;
?>
<!DOCTYPE html>
<html lang="<?= esc($locale, 'attr') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($headline) ?> — <?= esc($brand) ?></title>
<meta name="theme-color" content="#CF2030">
<link rel="icon" type="image/png" sizes="32x32" href="/media/brand/cfx-32.png">
<style>
    :root {
        --red: #CF2030;
        --ink: #ffffff;
        --muted: rgba(255, 255, 255, .55);
        --line: rgba(255, 255, 255, .12);
    }

    * { box-sizing: border-box; }

    html, body { height: 100%; }

    body {
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.5rem;
        /* A soft brand glow so the page is not a flat black box. Painted on
           the body itself — a fixed-position overlay only covers the viewport,
           which leaves a visible edge on a taller page. rgba(...,0) rather than
           `transparent`, which fades through grey in some engines. */
        background-color: #000;
        background-image:
            radial-gradient(60rem 40rem at 50% -10%, rgba(207, 32, 48, .22), rgba(207, 32, 48, 0) 60%),
            radial-gradient(40rem 30rem at 100% 110%, rgba(207, 32, 48, .10), rgba(207, 32, 48, 0) 60%);
        background-repeat: no-repeat;
        background-attachment: fixed;
        color: var(--ink);
        /* System stack only — this page never fetches a webfont. The Noto
           entries let a Sinhala or Tamil outage message render with real
           glyphs wherever the OS ships them, instead of tofu boxes. */
        font-family: "K2D", "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans Sinhala", "Noto Sans Tamil", sans-serif;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    main {
        position: relative;
        width: 100%;
        max-width: 34rem;
        text-align: center;
    }

    .symbol {
        width: 56px;
        height: 56px;
        object-fit: contain;
        margin: 0 auto 1.5rem;
        display: block;
    }

    .wordmark {
        margin: 0 0 2.5rem;
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .38em;
        text-transform: uppercase;
        color: var(--muted);
    }

    h1 {
        margin: 0 0 1rem;
        font-size: clamp(1.75rem, 6vw, 2.75rem);
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -.01em;
    }

    /* The copy is editable in the admin, so a pasted URL must not push the
       page sideways on a phone. */
    h1, p.message { overflow-wrap: break-word; }

    p.message {
        margin: 0 auto;
        max-width: 30rem;
        font-size: 1rem;
        line-height: 1.7;
        color: var(--muted);
    }

    /* Indeterminate sweep — a sign of life, not a real progress figure. */
    .bar {
        position: relative;
        overflow: hidden;
        width: 8rem;
        height: 2px;
        margin: 2.5rem auto;
        border-radius: 2px;
        background: var(--line);
    }

    .bar::after {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 45%;
        border-radius: 2px;
        background: var(--red);
        animation: sweep 1.9s cubic-bezier(.65, .05, .36, 1) infinite;
    }

    @keyframes sweep {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(322%); }
    }

    .meta {
        margin: 0;
        font-size: .8125rem;
        line-height: 1.9;
        color: var(--muted);
    }

    .meta strong {
        color: var(--ink);
        font-weight: 600;
    }

    .meta a { color: var(--red); text-decoration: none; }
    .meta a:hover { text-decoration: underline; }

    .langs {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        justify-content: center;
        margin-top: 2.5rem;
        padding-top: 1.75rem;
        border-top: 1px solid var(--line);
    }

    .langs a {
        padding: .35rem .8rem;
        border: 1px solid var(--line);
        border-radius: 999px;
        font-size: .75rem;
        color: var(--muted);
        text-decoration: none;
        transition: border-color .2s, color .2s;
    }

    .langs a:hover { border-color: var(--red); color: var(--ink); }
    .langs a[aria-current="true"] { border-color: var(--red); color: var(--ink); }

    @media (prefers-reduced-motion: reduce) {
        .bar::after { animation: none; width: 100%; }
    }
</style>
</head>
<body>
<main>
    <?php if ($symbol !== null): ?>
        <img class="symbol" src="<?= esc($symbol, 'attr') ?>" alt="" width="56" height="56">
    <?php endif; ?>

    <p class="wordmark"><?= esc($brand) ?></p>

    <h1><?= esc($headline) ?></h1>
    <p class="message"><?= esc($message) ?></p>

    <div class="bar" role="presentation"></div>

    <p class="meta">
        <?php if (trim($until) !== ''): ?>
            <?= esc($untilLabel) ?>: <strong><?= esc($until) ?></strong><br>
        <?php endif; ?>
        <?php if (trim($email) !== ''): ?>
            <?= esc($contactLabel) ?> <a href="mailto:<?= esc($email, 'attr') ?>"><?= esc($email) ?></a>
        <?php endif; ?>
    </p>

    <nav class="langs" aria-label="<?= esc($langLabel, 'attr') ?>">
        <?php foreach ($locales as $code): ?>
            <a href="?lang=<?= esc($code, 'attr') ?>"<?= $code === $locale ? ' aria-current="true"' : '' ?>>
                <?= esc($names[$code] ?? strtoupper($code)) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</main>
</body>
</html>
