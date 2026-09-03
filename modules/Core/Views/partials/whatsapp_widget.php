<?php helper('norlanka');

/**
 * A WhatsApp chat button, floating bottom-right on every page.
 *
 * WhatsApp is how a small hotel in Sri Lanka is actually reached — the number
 * is on their own site twice — so this is the shortest path from reading the
 * page to asking a question, and it costs nothing to load: a link, an icon and
 * a little Alpine state. No third-party script, no cookie, no network request
 * until the visitor taps it.
 *
 * It renders only when a WhatsApp number is set, so an unconfigured site shows
 * nothing rather than a button that opens an empty chat.
 *
 * The prefilled message names the hotel, because a guest arriving in WhatsApp
 * with a blank compose box has to work out who they are messaging and why.
 */
$number = preg_replace('/\D+/', '', (string) setting('whatsapp', '', 'contact'));
if ($number === '') { return; }

// Everything below is editable under Settings → WhatsApp button, and every
// default is what the button did before the settings existed.
if (setting('enabled', '1', 'whatsapp') === '0') { return; }

// Pages the button is kept off, by slug. A hotel might not want a chat bubble
// over its gallery, or on the contact page that already offers three ways to
// get in touch.
$hideOn = array_filter(array_map(
    static fn (string $s): string => strtolower(trim($s)),
    explode(',', (string) setting('hide_on', '', 'whatsapp')),
));
if ($hideOn !== []) {
    $parts = explode('/', trim(uri_string(), '/'));
    $slug  = strtolower($parts[1] ?? 'home');
    if (in_array($slug, $hideOn, true)) { return; }
}

$greeting = trim((string) setting('site_name', ''));
$prefill  = trim((string) setting('message', '', 'whatsapp'));
if ($prefill === '') {
    $prefill = $greeting !== ''
        ? sprintf('Hello %s, I would like to ask about a room.', $greeting)
        : 'Hello, I would like to ask about a room.';
}

$label = trim((string) setting('label', '', 'whatsapp')) ?: lang('Site.whatsapp.hint');

// Bottom-left by default: the panel engine's section dots run down the
// right-hand edge of the home page, and a floating button there sits on them.
$side = setting('position', 'left', 'whatsapp') === 'right' ? 'right-5' : 'left-5';
// The label sits on whichever side has room for it.
$flow = $side === 'right-5' ? 'flex-row' : 'flex-row-reverse';

$href = 'https://wa.me/' . $number . '?text=' . rawurlencode($prefill);
?>
<div x-data="{ hint: false }" class="fixed bottom-5 <?= $side ?> z-40 flex <?= $flow ?> items-center gap-3 print:hidden">

    <!-- The label is not a tooltip: it is readable without hover, on the side
         where there is room for it, and it is hidden from assistive tech
         because the link below already carries the same words. -->
    <span x-show="hint" x-cloak x-transition.opacity aria-hidden="true"
          class="hidden whitespace-nowrap rounded-full bg-brand-black/95 px-4 py-2 text-xs font-semibold
                 uppercase tracking-widest shadow-lg backdrop-blur sm:block">
        <?= esc($label) ?>
    </span>

    <a href="<?= esc($href, 'attr') ?>" target="_blank" rel="noopener"
       @mouseenter="hint = true" @mouseleave="hint = false"
       @focus="hint = true" @blur="hint = false"
       class="flex h-14 w-14 items-center justify-center rounded-full shadow-xl transition
              hover:scale-105 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/60"
       style="background:#25D366"
       aria-label="<?= esc(lang('Site.whatsapp.aria'), 'attr') ?>">
        <!-- WhatsApp's mark, drawn rather than fetched, so the button costs no
             request and stays crisp at any size. -->
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="#fff" aria-hidden="true">
            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.33 4.97L2 22l5.25-1.38a9.87 9.87 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm0 18.15h-.01a8.2 8.2 0 01-4.18-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.2 8.2 0 01-1.26-4.38c0-4.54 3.7-8.23 8.24-8.23a8.2 8.2 0 015.82 2.42 8.18 8.18 0 012.41 5.82c0 4.54-3.7 8.23-8.24 8.23zm4.52-6.16c-.25-.13-1.47-.72-1.69-.8-.23-.09-.39-.13-.56.12s-.64.8-.79.97c-.14.16-.29.19-.54.06-.25-.12-1.05-.38-1.99-1.23-.74-.65-1.23-1.46-1.38-1.71-.14-.25-.01-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.41.08-.17.04-.31-.02-.44-.06-.12-.56-1.35-.76-1.84-.2-.49-.41-.42-.56-.43h-.48c-.16 0-.43.06-.65.31s-.86.84-.86 2.05.88 2.38 1 2.54c.12.17 1.73 2.64 4.19 3.7.59.25 1.04.4 1.4.52.59.19 1.12.16 1.54.1.47-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.17-.47-.29z"/>
        </svg>
    </a>
</div>
