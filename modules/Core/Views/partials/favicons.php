<?php
helper('norlanka');

/**
 * The icon set, shared by the site layout and the welcome page.
 *
 * The two pages are separate documents — the welcome page deliberately does not
 * extend the layout, because it must not have already chosen a language — and
 * this partial is what keeps them from drifting into showing different icons.
 *
 * Sizes are rendered from the emblem by scripts/make-favicon.mjs. An uploaded
 * favicon replaces the whole set rather than one size of it: mixing a custom
 * 32px with a generated 192px shows two different marks depending on where the
 * browser happens to look.
 */
$favicon = (string) setting('favicon', '', 'brand');
?>
<?php if ($favicon !== ''): ?>
    <link rel="icon" href="<?= esc(media_src($favicon), 'attr') ?>">
    <link rel="apple-touch-icon" href="<?= esc(media_src($favicon), 'attr') ?>">
<?php else: ?>
    <link rel="icon" href="<?= esc(media_src('/media/tshda/tshda-emblem.svg'), 'attr') ?>" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= esc(media_src('/favicon-32.png'), 'attr') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= esc(media_src('/favicon-48.png'), 'attr') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= esc(media_src('/favicon-192.png'), 'attr') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= esc(media_src('/apple-touch-icon.png'), 'attr') ?>">
<?php endif; ?>
