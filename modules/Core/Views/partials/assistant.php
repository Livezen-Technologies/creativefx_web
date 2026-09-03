<?php helper(['norlanka', 'url']);

/**
 * The help assistant: a floating button that opens a panel answering questions
 * out of this site's own FAQ and pages.
 *
 * What it deliberately is not: a live chat. Nobody is on the other end, so it
 * does not say "Online" or "replies instantly" — it says it is automated and
 * where its answers come from, and when it cannot answer it hands over the ways
 * to reach a person rather than improvising. A government portal that implies a
 * duty officer is reading, at eleven at night, has made a promise the Authority
 * did not make.
 *
 * Nothing third-party loads and nothing leaves the server: the lookup is this
 * site's own search endpoint. Somebody asking about their subsidy application
 * is not thereby telling a chat vendor about it.
 */

if (setting('enabled', '1', 'assistant') === '0') { return; }

// Pages to keep it off, by slug — same convention as the WhatsApp button.
$hideOn = array_filter(array_map(
    static fn (string $s): string => strtolower(trim($s)),
    explode(',', (string) setting('hide_on', '', 'assistant'))
));
if ($hideOn !== []) {
    $parts = explode('/', trim(uri_string(), '/'));
    if (in_array(strtolower($parts[1] ?? 'home'), $hideOn, true)) { return; }
}

// The WhatsApp button defaults to the left. If an admin has moved it to the
// right it would sit under this one, so step up out of its way rather than
// stacking two floating buttons in the same corner.
$whatsappNumber = preg_replace('/\D+/', '', (string) setting('whatsapp', '', 'contact'));
$clash = $whatsappNumber !== ''
    && setting('enabled', '1', 'whatsapp') !== '0'
    && setting('position', 'left', 'whatsapp') === 'right';
$bottom = $clash ? 'bottom-24' : 'bottom-5';

// The three things most people arrive wanting. Real destinations, not canned
// replies — the fastest useful answer to "how do I apply" is the page that
// tells you, so the topic buttons are links and behave like links.
$topics = [
    ['label' => lang('Site.assistant.topic_subsidy'),      'url' => locale_url('services/replanting-subsidy')],
    ['label' => lang('Site.assistant.topic_office'),       'url' => locale_url('directory')],
    ['label' => lang('Site.assistant.topic_registration'), 'url' => locale_url('services/smallholder-registration')],
];
?>
<div x-data="assistant(<?= esc(json_encode([
        'endpoint' => locale_url('assistant/ask'),
        'labels'   => [
            'open'  => lang('Site.assistant.launcher'),
            'close' => lang('Site.assistant.close_short'),
        ],
        'strings'  => [
            'thinking' => lang('Site.assistant.thinking'),
            'related'  => lang('Site.assistant.related'),
            'source'   => lang('Site.assistant.source'),
            'searchAll'=> lang('Site.assistant.search_all'),
            'none'     => lang('Site.assistant.none'),
            'noneHelp' => lang('Site.assistant.none_help'),
            'error'    => lang('Site.assistant.error'),
            'contact'      => locale_url('contact'),
            'contactLabel' => lang('Site.nav.contact'),
        ],
     ]), 'attr') ?>)"
     class="fixed <?= $bottom ?> right-5 z-40 flex flex-col items-end gap-3 print:hidden">

    <!-- ── The panel ──────────────────────────────────────────────────────── -->
    <!-- Not aria-modal: the page behind stays readable and usable, which is the
         point of a helper. Escape still closes it and focus still returns to
         the button that opened it. -->
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         x-ref="panel" id="assistant-panel" role="dialog" aria-labelledby="assistant-title"
         @keydown.escape.window="escape()"
         class="assistant-panel">

        <header class="assistant-head">
            <span class="assistant-avatar" aria-hidden="true">T</span>
            <div class="min-w-0 flex-1">
                <p id="assistant-title" class="truncate font-semibold"><?= esc(lang('Site.assistant.title')) ?></p>
                <p class="assistant-status"><?= esc(lang('Site.assistant.status')) ?></p>
            </div>
            <button type="button" @click="close()" class="assistant-x"
                    aria-label="<?= esc(lang('Site.assistant.close'), 'attr') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </header>

        <!-- The thread. aria-live so a reply is announced when it arrives:
             without it a screen-reader user sends a question and is told
             nothing came back. -->
        <div class="assistant-thread" x-ref="thread" aria-live="polite" aria-atomic="false">
            <p class="assistant-day"><span><?= esc(lang('Site.assistant.today')) ?></span></p>

            <div class="assistant-turn">
                <span class="assistant-avatar assistant-avatar--sm" aria-hidden="true">T</span>
                <p class="assistant-bubble"><?= esc(lang('Site.assistant.greeting')) ?></p>
            </div>

            <p class="assistant-prompt"><?= esc(lang('Site.assistant.prompt')) ?></p>

            <nav class="assistant-topics" aria-label="<?= esc(lang('Site.assistant.prompt'), 'attr') ?>">
                <?php foreach ($topics as $topic): ?>
                    <a href="<?= esc($topic['url'], 'attr') ?>" class="assistant-topic">
                        <span><?= esc($topic['label']) ?></span>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Everything said since. Rendered as text and bound hrefs, never
                 as markup: the answers are editorial content and a bubble is
                 not a place to start interpreting HTML. -->
            <template x-for="(m, i) in messages" :key="i">
                <div>
                    <div class="assistant-turn" :class="m.from === 'you' ? 'assistant-turn--you' : ''">
                        <span class="assistant-avatar assistant-avatar--sm" x-show="m.from !== 'you'" aria-hidden="true">T</span>
                        <p class="assistant-bubble" :class="m.from === 'you' ? 'assistant-bubble--you' : ''" x-text="m.text"></p>
                    </div>

                    <template x-if="m.links && m.links.length">
                        <div class="assistant-links">
                            <p class="assistant-links-label" x-show="m.linksLabel" x-text="m.linksLabel"></p>
                            <template x-for="(l, j) in m.links" :key="j">
                                <a :href="l.url" class="assistant-topic">
                                    <span x-text="l.title"></span>
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <form class="assistant-compose" @submit.prevent="send()">
            <label class="min-w-0 flex-1">
                <span class="sr-only"><?= esc(lang('Site.assistant.placeholder')) ?></span>
                <input type="text" x-model="draft" x-ref="input" :disabled="busy"
                       autocomplete="off" maxlength="300"
                       placeholder="<?= esc(lang('Site.assistant.placeholder'), 'attr') ?>"
                       class="assistant-input">
            </label>
            <button type="submit" class="assistant-send" :disabled="busy || !draft.trim()"
                    aria-label="<?= esc(lang('Site.assistant.send'), 'attr') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 20l18-8L3 4v6l12 2-12 2z" fill="currentColor"/></svg>
            </button>
        </form>
        <p class="assistant-note"><?= esc(lang('Site.assistant.note')) ?></p>
    </div>

    <!-- ── The launcher ───────────────────────────────────────────────────── -->
    <button type="button" x-ref="launcher" @click="toggle()"
            :aria-expanded="open ? 'true' : 'false'" aria-controls="assistant-panel"
            class="assistant-launcher">
        <span class="assistant-spark" aria-hidden="true">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2l2.2 6.3L20.5 10l-6.3 2.2L12 18.5l-2.2-6.3L3.5 10l6.3-1.7z" fill="currentColor"/></svg>
        </span>
        <span x-text="open ? labels.close : labels.open"></span>
    </button>
</div>
