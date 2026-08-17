<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; }

// One list carries two item shapes: a bare locale map ("Corporate") for simple
// capability lists, and {title, text} when a capability needs explaining. What
// picks the rendering is whether the entry actually carries a description, not
// the wrapper — so {title, text: {}} degrades to a check row rather than a card
// with an empty body, and a mixed list still lands somewhere sensible.
$cards  = [];
$checks = [];
foreach ($content['items'] as $item) {
    // 'title'/'text' can never be locale codes (en, si, ta), so the wrapper is
    // unambiguous and a bare locale map falls straight through to t_field().
    $isWrapped = is_array($item) && (array_key_exists('title', $item) || array_key_exists('text', $item));
    $label     = trim($isWrapped ? t_field($item['title'] ?? []) : t_field($item));
    $text      = $isWrapped ? trim(t_field($item['text'] ?? [])) : '';

    if ($label === '') {
        continue; // an unnamed capability offers nothing to read
    }
    if ($text === '') {
        $checks[] = $label;
    } else {
        $cards[] = ['label' => $label, 'text' => $text];
    }
}
if ($cards === [] && $checks === []) { return; }

// Literal class strings, never interpolated: Tailwind scans this file for the
// utilities it has to compile, and "lg:grid-cols-{$n}" would compile to nothing.
$columns   = (int) ($content['columns'] ?? 3);
$cardCols  = [
    2 => 'sm:grid-cols-2',
    3 => 'sm:grid-cols-2 lg:grid-cols-3',
    4 => 'sm:grid-cols-2 lg:grid-cols-4',
][$columns] ?? 'sm:grid-cols-2 lg:grid-cols-3';
// A one-line label needs far less width than a described card, so the check
// list packs one step denser than `columns` asks for.
$checkCols = [
    2 => 'sm:grid-cols-2 lg:grid-cols-3',
    3 => 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
    4 => 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
][$columns] ?? 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
?>
<!-- "What We Offer" capability grid. Described capabilities lead as cards; the
     bare labels follow as one tight checklist, because dropping a single-line
     chip into the card grid leaves ragged holes across the row. -->
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

        <?php if ($cards !== []): ?>
            <div class="mt-10 grid gap-6 <?= $cardCols ?>">
                <?php foreach ($cards as $card): ?>
                    <article class="rounded-2xl border border-white/10 bg-white/[0.02] p-7 transition hover:border-brand-red/60" data-gsap="reveal">
                        <h3 class="text-lg font-semibold text-brand-red"><?= esc($card['label']) ?></h3>
                        <p class="mt-3 text-sm leading-relaxed text-white/60"><?= esc($card['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($checks !== []): ?>
            <!-- One reveal on the grid, not on every row: a dozen single-line
                 items staggering in one by one reads as flicker, not motion. -->
            <ul class="<?= $cards === [] ? 'mt-10' : 'mt-6' ?> grid gap-3 <?= $checkCols ?>" data-gsap="reveal">
                <?php foreach ($checks as $label): ?>
                    <li class="flex items-start gap-3 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm text-white/75 transition hover:border-brand-red/60">
                        <!-- Decorative: the tick is the list's visual rhythm, the label is the content. -->
                        <svg class="mt-0.5 h-4 w-4 flex-none text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                        <!-- break-words: a long unbroken term must wrap, never widen the page. -->
                        <span class="break-words"><?= esc($label) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
