<?php
helper('norlanka');

/**
 * A block of prose.
 *
 * The payload key is `text`. It is worth saying so, because for the whole of
 * this build the seed files wrote `body` and this partial returned early on
 * every one of them: the terms of service, the privacy notice, the refund,
 * reschedule and accessibility policies, About and Why MyLearnPlus were all
 * live, all returning 200 with the right heading, and all empty. Six of the
 * seven block types had the same disagreement, each dropping whatever it did
 * not recognise.
 *
 * The partials are the contract — they are shared with the other sites in this
 * repository and they are the vocabulary the admin's block editor writes — so
 * the seed files were brought to them rather than the other way round. A page
 * seeded in one dialect and then edited in the admin would otherwise change
 * shape on its first save.
 *
 * `spark check:cms` compares what each page stores against what it renders, so
 * a block that silently drops its payload fails a check rather than a reader.
 */
if (empty($content['title']) && empty($content['text'])) {
    return;
}
?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <div class="max-w-3xl" data-gsap="reveal">
            <?php if (! empty($content['eyebrow'])): ?>
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc(t_field($content['eyebrow'])) ?></p>
            <?php endif; ?>
            <?php if (! empty($content['title'])): ?>
                <h2 class="text-2xl font-semibold sm:text-3xl"><?= esc(t_field($content['title'])) ?></h2>
            <?php endif; ?>
            <?php if (! empty($content['text'])): ?>
                <div class="mt-4 leading-relaxed text-white/80"><?= rich_text($content['text']) ?></div>
            <?php endif; ?>
            <?php // Optional citation. Renders only when the block carries one, so
                  // every page already using this block is unchanged. ?>
            <?php if (! empty($content['link'])): ?>
                <p class="mt-5 text-sm"><?= source_link($content['link']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
