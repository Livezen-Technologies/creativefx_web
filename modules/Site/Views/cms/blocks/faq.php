<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; }

// Plain-text projection for the structured data. Answers are editor HTML, so
// the markup has to go — and with it any stray < or >, because one of those
// would close the ld+json <script> early (the payload keeps slashes unescaped).
$faqPlain = static function ($value): string {
    // Block ends become spaces before the tags are dropped, or the last word of
    // one paragraph fuses with the first word of the next.
    $text = preg_replace('#<(?:br[^>]*|/(?:p|div|li|ul|ol|h[1-6]|blockquote|tr|td|th))\s*/?>#i', ' ', t_field($value));
    $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return str_replace(['<', '>'], '', trim((string) preg_replace('/\s+/', ' ', $text)));
};

// Resolve every row once: the same pairs feed both the accordion and the JSON-LD.
$faqs = [];
foreach ($content['items'] as $item) {
    $question = trim(t_field($item['question'] ?? []));
    $answer   = rich_text($item['answer'] ?? []);
    // A half-filled row would render a disclosure that opens onto nothing.
    if ($question === '' || trim($answer) === '') {
        continue;
    }
    $faqs[] = [
        'question'      => $question,
        'answer'        => $answer,
        'plainQuestion' => $faqPlain($item['question'] ?? []),
        'plainAnswer'   => $faqPlain($item['answer'] ?? []),
    ];
}
if ($faqs === []) { return; }

$schema = json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(static fn (array $faq): array => [
        '@type'          => 'Question',
        'name'           => $faq['plainQuestion'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['plainAnswer']],
    ], $faqs),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!-- Service-page FAQ. Native <details>/<summary> keeps it operable with no
     JavaScript at all, so no aria-expanded is hand-written here: the browser
     already exposes the open state, and a hard-coded attribute would go stale
     the moment the reader opens a row. -->
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="eyebrow" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <?php if (! empty($content['title'])): ?>
            <h2 class="mt-5 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>

        <div class="mt-10 max-w-3xl space-y-4">
            <?php foreach ($faqs as $faq): ?>
                <details class="group rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/60 open:border-brand-red/40" data-gsap="reveal">
                    <!-- Padding lives on the summary, not the card, so the whole
                         header row is the hit target. The native marker is styled
                         away (Safari needs the -webkit- pseudo-element too). -->
                    <summary class="cursor-pointer list-none select-none rounded-2xl px-6 py-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red sm:px-7 [&::-webkit-details-marker]:hidden">
                        <h3 class="flex items-start justify-between gap-4 text-base font-semibold sm:text-lg">
                            <?php // min-w-0 + break-words: a long unbroken question must wrap inside the row, never widen the page. ?>
                            <span class="min-w-0 break-words"><?= esc($faq['question']) ?></span>
                            <!-- One plus, rotated 45° when open: it reads as a
                                 close cross without a second icon to keep in sync. -->
                            <span class="mt-0.5 inline-flex h-8 w-8 flex-none items-center justify-center rounded-full border border-white/15 text-brand-red transition duration-300 group-hover:border-brand-red/60 group-open:rotate-45 motion-reduce:transition-none" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </h3>
                    </summary>
                    <div class="px-6 pb-6 text-sm leading-relaxed text-white/60 sm:px-7"><?= $faq['answer'] ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($schema !== false): ?>
        <!-- FAQPage rich result. Emitted with the block so the markup can never
             drift from the questions actually on the page. -->
        <script type="application/ld+json"><?= $schema ?></script>
    <?php endif; ?>
</section>
