<?php helper(['norlanka', 'url']);

/**
 * The first-visit language chooser.
 *
 * The welcome page at `/` already asks this (Clause 3.9 A) and lets a returning
 * reader through. The people it never reaches are the ones who arrive deep —
 * from a search result, a shared link, a QR code on a circular — straight onto
 * /en/services/replanting-subsidy without passing the front door. Today the
 * site simply decides for them, in English, and a Tamil-speaking smallholder
 * has to notice a globe in the header to find out otherwise. This asks them.
 *
 * Three rules keep it from becoming the thing everybody hates:
 *
 *   1. Once. Choosing writes the preference the switcher and the welcome page
 *      already share, so it is never shown again — and so does dismissing it.
 *      A dialog that reappears because you closed it is not asking, it is
 *      nagging.
 *   2. Escapable. Escape closes it, the backdrop closes it, there is a real
 *      close button. Trapping somebody in a modal to force a choice they can
 *      make from the header at any time is hostile.
 *   3. Never on the welcome page, whose entire job is this question.
 *
 * It is rendered hidden and only shown by JS after reading the preference, so
 * a reader who has already chosen never sees a flash of it, and a crawler with
 * no JavaScript sees the page rather than a dialog.
 *
 * Every word appears in all three languages at once, because a chooser written
 * in one of them cannot be read by the people it is asking. That is why these
 * strings are literals rather than lang() calls.
 */

if (setting('enabled', '1', 'language_modal') === '0') { return; }

$locales = supported_locales();

// The Authority's name in each language: recognition, not translation. Somebody
// who reads only Sinhala should see the organisation they were looking for.
// The school's own name, in each language it is offered in. Hard-coded rather
// than read from a language file on purpose: this dialog is shown before a
// locale has been chosen, so it has to speak all of them at once — a translated
// string would render only in whichever language the browser happened to
// negotiate, which is the very question the dialog is asking.
$names = [
    'en' => 'MyLearnPlus',
    'si' => 'MyLearnPlus',
];
$ask = [
    'en' => 'Choose your language',
    'si' => 'ඔබේ භාෂාව තෝරන්න',
];
$note = [
    'en' => 'You can change this at any time from the globe in the header.',
    'si' => 'ශීර්ෂකයේ ඇති ගෝලය ඔස්සේ ඔබට මෙය ඕනෑම විටෙක වෙනස් කළ හැක.',
];
$dismiss = ['en' => 'Close', 'si' => 'වසන්න'];
?>
<div x-data="languageModal(<?= esc(json_encode([
        'current' => current_locale(),
        'codes'   => array_column($locales, 'code'),
     ]), 'attr') ?>)"
     x-show="open" x-cloak
     @keydown.escape.window="dismiss()"
     class="lang-modal" role="dialog" aria-modal="true" aria-labelledby="lang-modal-title">

    <div class="lang-modal-backdrop" @click="dismiss()" aria-hidden="true"></div>

    <div class="lang-modal-card" x-ref="card" @keydown.tab="trap($event)">
        <img src="/media/learnplus/emblem.svg" alt="" width="56" height="56" class="lang-modal-mark">

        <?php // The heading carries all three languages. A screen reader will
              // read them in sequence, which is the right outcome: the reader
              // does not yet know which language this site is going to use. ?>
        <h2 id="lang-modal-title" class="lang-modal-title">
            <?php foreach (supported_locales_codes() as $i => $code): ?>
                <span lang="<?= $code ?>"><?= esc($ask[$code]) ?></span><?= $i < 2 ? '<span class="lang-modal-dot" aria-hidden="true">·</span>' : '' ?>
            <?php endforeach; ?>
        </h2>

        <?php // A list, not a row of buttons: three options of equal weight, and
              // arrow keys move between them the way a radio group does. ?>
        <ul class="lang-modal-options" role="list">
            <?php foreach ($locales as $i => $l): $code = $l['code']; ?>
                <li>
                    <button type="button" class="lang-option"
                            lang="<?= esc($code, 'attr') ?>"
                            data-lang="<?= esc($code, 'attr') ?>"
                            @click="choose('<?= esc($code, 'attr') ?>')"
                            @keydown.arrow-down.prevent="move(1)"
                            @keydown.arrow-up.prevent="move(-1)"
                            :aria-current="current === '<?= esc($code, 'attr') ?>' ? 'true' : 'false'">
                        <span class="lang-option-native"><?= esc($l['native']) ?></span>
                        <span class="lang-option-name"><?= esc($names[$code] ?? '') ?></span>
                        <span class="lang-option-go" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="lang-modal-note">
            <?php foreach (supported_locales_codes() as $code): ?>
                <span lang="<?= $code ?>"><?= esc($note[$code]) ?></span>
            <?php endforeach; ?>
        </p>

        <button type="button" class="lang-modal-x" x-ref="close" @click="dismiss()"
                aria-label="<?= esc(implode(' · ', $dismiss), 'attr') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
    </div>
</div>
