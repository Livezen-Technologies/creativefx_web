<?php
/**
 * The brand's social accounts as icon links.
 *
 * One list, rendered wherever the accounts are wanted, so adding an account is
 * a settings change rather than an edit in two places. Each entry is emitted
 * only when its setting holds a value, which is how the footer already treats
 * them — a brand that does not use a network should show no icon for it rather
 * than a dead link.
 *
 * $compact (bool) — true for the header's tight row, false for larger targets.
 */
$compact ??= true;

$whatsapp = preg_replace('/\D+/', '', (string) setting('whatsapp', '', 'contact'));

$accounts = [
    [
        'label' => 'Facebook',
        'url'   => (string) setting('facebook', '', 'social'),
        // Each mark is drawn on a 24-unit grid, and each one is a ring around
        // its glyph: a bare 'f' next to Instagram's frame and WhatsApp's bubble
        // reads noticeably lighter than either, so it gets a ring too.
        'path'  => 'M12 3.5a8.5 8.5 0 100 17 8.5 8.5 0 000-17zm0 1.6a6.9 6.9 0 110 13.8A6.9 6.9 0 0112 5.1z'
                 . 'M13.4 10.2h1.5V8.5h-1.4c-1.6 0-2.5.9-2.5 2.4v.8H9.5v1.7H11v4.4h1.9v-4.4h1.5l.2-1.7h-1.7v-.6c0-.4.1-.5.5-.5z',
    ],
    [
        'label' => 'Instagram',
        'url'   => (string) setting('instagram', '', 'social'),
        'path'  => 'M12 7.6a4.4 4.4 0 100 8.8 4.4 4.4 0 000-8.8zm0 7.3a2.9 2.9 0 110-5.8 2.9 2.9 0 010 5.8zM16.9 6.3a1 1 0 100 2.1 1 1 0 000-2.1zM8.6 3.5h6.8A5.1 5.1 0 0120.5 8.6v6.8a5.1 5.1 0 01-5.1 5.1H8.6a5.1 5.1 0 01-5.1-5.1V8.6a5.1 5.1 0 015.1-5.1zm0 1.6A3.5 3.5 0 005.1 8.6v6.8a3.5 3.5 0 003.5 3.5h6.8a3.5 3.5 0 003.5-3.5V8.6a3.5 3.5 0 00-3.5-3.5H8.6z',
    ],
    [
        'label' => 'WhatsApp',
        'url'   => $whatsapp === '' ? '' : 'https://wa.me/' . $whatsapp,
        'path'  => 'M12 3.6a8.3 8.3 0 00-7.1 12.6L3.6 20.4l4.3-1.3A8.3 8.3 0 1012 3.6zm0 1.6a6.7 6.7 0 015.7 10.3l-.2.3.6 2-2.1-.6-.3.2A6.7 6.7 0 1112 5.2zm-3 3.2c-.2 0-.4 0-.6.3-.2.3-.7.8-.7 1.8s.7 2 .8 2.2c.1.2 1.4 2.4 3.6 3.3 1.8.7 2.2.6 2.6.5.4 0 1.2-.5 1.4-1s.2-1 .1-1.1l-.5-.3-1.2-.6c-.2 0-.3-.1-.5.1l-.6.8c-.1.2-.2.2-.4.1a5.4 5.4 0 01-1.6-1 6 6 0 01-1.1-1.4c-.1-.2 0-.3.1-.4l.3-.4.2-.4v-.4l-.6-1.5c-.2-.4-.3-.3-.5-.3z',
    ],
];

$accounts = array_values(array_filter($accounts, static fn (array $a): bool => $a['url'] !== ''));

if ($accounts === []) {
    return;
}

$size = $compact ? 'h-9 w-9' : 'h-11 w-11';
$icon = $compact ? 'h-[18px] w-[18px]' : 'h-5 w-5';
?>
<ul class="flex items-center <?= $compact ? 'gap-1' : 'gap-2' ?>">
    <?php foreach ($accounts as $a): ?>
        <li>
            <a href="<?= esc($a['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
               aria-label="<?= esc('Magic Corn on ' . $a['label'], 'attr') ?>"
               title="<?= esc($a['label'], 'attr') ?>"
               class="inline-flex <?= $size ?> items-center justify-center rounded-lg text-white/70 transition hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/60">
                <svg class="<?= $icon ?>" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                    <path d="<?= esc($a['path'], 'attr') ?>"/>
                </svg>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
