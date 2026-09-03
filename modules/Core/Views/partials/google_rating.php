<?php helper('norlanka');

/**
 * Google rating badge — stars, score and review count, linking to the listing.
 *
 * The three values are settings rather than constants because they go out of
 * date on their own: the count climbs every time a guest writes something, and
 * the score moves with it. An editor can correct both in the admin console
 * without a deploy.
 *
 * Nothing renders unless a rating and a link are both set. A star row with no
 * score behind it, or a score with nowhere to check it, is a claim a visitor
 * cannot verify — worse than no badge at all.
 */
$url    = trim((string) setting('google_reviews_url', '', 'social'));
$rating = (float) setting('google_rating', 0, 'social');
$count  = (int) setting('google_review_count', 0, 'social');

if ($url === '' || $rating <= 0) {
    return;
}

$rating = min(5.0, $rating);
$score  = rtrim(rtrim(number_format($rating, 1), '0'), '.');
$pct    = ($rating / 5) * 100;

// One star path, drawn twice: once dim for the track, once gold and clipped to
// the score. A fraction of a star is the honest way to show 4.7 — rounding it
// up to five full stars overstates the rating by a tenth of a point.
$star = '<svg viewBox="0 0 20 19" class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4" fill="currentColor" aria-hidden="true">'
      . '<path d="M10 0l2.9 6.2 6.6.9-4.8 4.7 1.2 6.8L10 15.4 4.1 18.6l1.2-6.8L.5 7.1l6.6-.9z"/></svg>';
$stars = str_repeat($star, 5);

$label = $count > 0
    ? sprintf(lang('Site.reviews.badge_count'), $score, number_format($count))
    : sprintf(lang('Site.reviews.badge'), $score);
?>
<a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener noreferrer"
<?php // At full size the pill measures 375px, which is wider than a 390px
      // phone's content column — it was the one thing in the hero that could
      // not fit. Everything in it steps down a size below sm rather than any
      // of it being dropped: a rating badge that hides its count is not much
      // of a rating badge. ?>
   class="group inline-flex max-w-full items-center gap-2 rounded-full border border-white/20 bg-brand-black/40 py-2 pl-2 pr-3 backdrop-blur transition hover:border-white/45 hover:bg-brand-black/60 sm:gap-3 sm:pl-2.5 sm:pr-4"
   aria-label="<?= esc($label, 'attr') ?> <?= esc(lang('Site.reviews.badge_hint'), 'attr') ?>">

    <?php // Google's mark keeps its own four colours, which need a light disc
          // behind them to read on the hero. ?>
    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-white sm:h-7 sm:w-7">
        <svg viewBox="0 0 48 48" class="h-3.5 w-3.5 sm:h-4 sm:w-4" aria-hidden="true" focusable="false">
            <path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.2-.4-4.7H24v8.9h11.8c-.5 2.7-2 5-4.4 6.6v5.5h7.1c4.2-3.8 6.6-9.5 6.6-16.3z"/>
            <path fill="#34A853" d="M24 46c6 0 11-2 14.5-5.2l-7.1-5.5c-2 1.3-4.5 2.1-7.4 2.1-5.7 0-10.5-3.8-12.3-9H4.4v5.7C7.9 41.1 15.4 46 24 46z"/>
            <path fill="#FBBC05" d="M11.7 28.4c-.5-1.3-.7-2.8-.7-4.4s.3-3 .7-4.4v-5.7H4.4A22 22 0 0 0 2 24c0 3.6.9 6.9 2.4 9.9l7.3-5.5z"/>
            <path fill="#EA4335" d="M24 10.2c3.2 0 6.1 1.1 8.4 3.3l6.3-6.3C34.9 3.6 30 1.6 24 1.6 15.4 1.6 7.9 6.5 4.4 13.7l7.3 5.7c1.8-5.3 6.6-9.2 12.3-9.2z"/>
        </svg>
    </span>

    <span class="flex shrink-0 items-center gap-1.5 sm:gap-2">
        <span class="text-sm font-bold leading-none text-white"><?= esc($score) ?></span>
        <span class="relative inline-flex" aria-hidden="true">
            <span class="flex gap-0.5 text-white/25"><?= $stars ?></span>
            <span class="absolute inset-y-0 left-0 overflow-hidden"
                  style="width: <?= esc(number_format($pct, 2, '.', ''), 'attr') ?>%">
                <span class="flex gap-0.5 text-[#FBBC05]"><?= $stars ?></span>
            </span>
        </span>
    </span>

    <?php if ($count > 0): ?>
        <span class="whitespace-nowrap text-[10px] font-semibold uppercase tracking-[0.1em] text-white/70 transition group-hover:text-white/90 sm:text-[11px] sm:tracking-[0.18em]">
            <?= esc(sprintf(lang('Site.reviews.count'), number_format($count))) ?>
        </span>
    <?php endif; ?>
</a>
