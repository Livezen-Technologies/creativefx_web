<?php
/**
 * The brand's social accounts as icon links.
 *
 * One list, rendered wherever the accounts are wanted, so adding an account is
 * a settings change rather than an edit in two places. Each entry is emitted
 * only when its setting holds a value — a brand that does not use a network
 * should show no icon for it rather than a dead link.
 *
 * These were hairline outlines: every mark shrunk down and set inside a shared
 * ring, drawn in a single stroke weight at 70% opacity. The ring existed to
 * stop a bare "f" reading lighter than Instagram's frame, which it did, but it
 * solved that by making all five equally faint — five thin circles that at a
 * glance are a row of Os, and at any real viewing distance are grey specks.
 *
 * They are now each network's own mark at full size, solid, on a soft chip that
 * lifts on hover. The weights match because the marks themselves are drawn to
 * match, not because a ring was added to hold them together. Instagram keeps
 * its outlined camera because that is its mark; solid, it would be a blob.
 *
 * $compact (bool) — true for the header's tight row, false for larger targets.
 * $only (string[]) — labels to show, in case a placement wants fewer than all
 *   of them. The header carries the two accounts guests actually message the
 *   hotel on; the full set lives in the footer.
 */
$compact ??= true;
$only ??= [];

$whatsapp = preg_replace('/\D+/', '', (string) setting('whatsapp', '', 'contact'));

// Each network's published monochrome mark, on its own 24-unit grid, unedited.
$accounts = [
    [
        'label' => 'Facebook',
        'url'   => (string) setting('facebook', '', 'social'),
        'path'  => 'M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.96h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z',
    ],
    [
        'label' => 'Instagram',
        'url'   => (string) setting('instagram', '', 'social'),
        'path'  => 'M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.13 1.38C1.35 2.68.93 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.13.67.66 1.34 1.08 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.13-1.38.66-.67 1.08-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.93 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 100 12.32 6.16 6.16 0 000-12.32zM12 16a4 4 0 110-8 4 4 0 010 8zm7.85-10.4a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z',
    ],
    [
        'label' => 'X',
        'url'   => (string) setting('x', '', 'social'),
        'path'  => 'M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.41l-5.8-7.58-6.64 7.58H.47l8.6-9.83L0 1.15h7.59l5.24 6.93 6.07-6.93zm-1.29 19.5h2.04L6.49 3.24H4.3l13.31 17.41z',
    ],
    [
        'label' => 'TikTok',
        'url'   => (string) setting('tiktok', '', 'social'),
        'path'  => 'M16.6 5.82A4.28 4.28 0 0115.54 3h-3.09v12.4a2.59 2.59 0 01-2.59 2.5 2.59 2.59 0 01-2.59-2.59 2.59 2.59 0 013.39-2.47V9.66a5.72 5.72 0 00-.8-.05A5.7 5.7 0 004.15 15.3a5.7 5.7 0 005.71 5.7 5.7 5.7 0 005.71-5.7V9.01a7.35 7.35 0 004.3 1.38V7.3a4.35 4.35 0 01-3.27-1.48z',
    ],
    [
        'label' => 'WhatsApp',
        'url'   => $whatsapp === '' ? '' : 'https://wa.me/' . $whatsapp,
        'path'  => 'M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.06 2.87 1.21 3.07.15.2 2.09 3.2 5.07 4.49.71.31 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.05 21.8h-.02a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.9-9.88a9.82 9.82 0 016.99 2.9 9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.88-9.88 9.88zM20.52 3.45A11.78 11.78 0 0012.05 0C5.5 0 .18 5.32.18 11.86c0 2.09.55 4.13 1.59 5.93L.08 24l6.34-1.66a11.85 11.85 0 005.67 1.44h.01c6.54 0 11.86-5.32 11.87-11.86a11.8 11.8 0 00-3.45-8.47z',
    ],
];

$accounts = array_values(array_filter(
    $accounts,
    static fn (array $a): bool => $a['url'] !== '' && ($only === [] || in_array($a['label'], $only, true)),
));

if ($accounts === []) {
    return;
}

$chip = $compact ? 'h-9 w-9' : 'h-11 w-11';
$icon = $compact ? 'h-4 w-4' : 'h-[18px] w-[18px]';
?>
<ul class="flex flex-wrap items-center <?= $compact ? 'gap-1.5' : 'gap-2.5' ?>">
    <?php foreach ($accounts as $a): ?>
        <li>
            <a href="<?= esc($a['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
               aria-label="<?= esc(trim(setting('site_name', '') . ' on ' . $a['label'], ' '), 'attr') ?>"
               title="<?= esc($a['label'], 'attr') ?>"
               class="social-chip <?= $chip ?>">
                <svg class="<?= $icon ?>" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                    <path d="<?= esc($a['path'], 'attr') ?>"/>
                </svg>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
