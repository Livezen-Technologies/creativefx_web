<?php helper('norlanka');

/**
 * Guest-rating badges — one per review site — linking to the reviews themselves.
 *
 * Every number here goes out of date on its own: a count climbs whenever a guest
 * writes something and the score moves with it. So they are settings an editor
 * corrects in the console, not constants that need a deploy.
 *
 * A badge renders only when its link and its headline figure are both set. A
 * star row with no score behind it, or a score with nowhere to go and check it,
 * is a claim a visitor cannot verify — worse than showing nothing.
 *
 * Google publishes a five-star average. Facebook stopped doing that years ago
 * and publishes the share of reviewers who recommend the place instead, so the
 * two badges do not carry the same shape of figure and this does not pretend
 * they do: a percentage is shown as a percentage rather than converted into
 * stars it was never measured in.
 *
 * @var string $layout  'row' (side by side, wrapping) or 'stack' (one per line)
 * @var string $size    'md' for the hero, 'sm' for the footer
 */
$layout = $layout ?? 'row';
$size   = $size ?? 'md';

$badges = [];

// --- Google: a five-star average -------------------------------------------
$gUrl    = trim((string) setting('google_reviews_url', '', 'social'));
$gRating = (float) setting('google_rating', 0, 'social');
if ($gUrl !== '' && $gRating > 0) {
    $gRating  = min(5.0, $gRating);
    $gCount   = (int) setting('google_review_count', 0, 'social');
    $badges[] = [
        'url'     => $gUrl,
        'mark'    => 'google',
        'figure'  => rtrim(rtrim(number_format($gRating, 1), '0'), '.'),
        'stars'   => ($gRating / 5) * 100,
        'label'   => $gCount > 0 ? sprintf(lang('Site.reviews.count_google'), number_format($gCount)) : '',
        'aria'    => $gCount > 0
            ? sprintf(lang('Site.reviews.aria_google_count'), rtrim(rtrim(number_format($gRating, 1), '0'), '.'), number_format($gCount))
            : sprintf(lang('Site.reviews.aria_google'), rtrim(rtrim(number_format($gRating, 1), '0'), '.')),
    ];
}

// --- Facebook: the share of reviewers who recommend -------------------------
$fUrl     = trim((string) setting('facebook_reviews_url', '', 'social'));
$fPercent = (int) setting('facebook_recommend', 0, 'social');
if ($fUrl !== '' && $fPercent > 0) {
    $fPercent = min(100, $fPercent);
    $fCount   = (int) setting('facebook_review_count', 0, 'social');
    $badges[] = [
        'url'    => $fUrl,
        'mark'   => 'facebook',
        'figure' => $fPercent . '%',
        'stars'  => null,
        'label'  => $fCount > 0
            ? sprintf(lang('Site.reviews.count_facebook'), number_format($fCount))
            : lang('Site.reviews.recommend'),
        'aria'   => $fCount > 0
            ? sprintf(lang('Site.reviews.aria_facebook_count'), $fPercent, number_format($fCount))
            : sprintf(lang('Site.reviews.aria_facebook'), $fPercent),
    ];
}

if ($badges === []) { return; }

// One star path, drawn twice per badge: dim for the track, gold and clipped to
// the score on top. A fraction of a star is the honest way to show 4.7 —
// rounding up to five full stars overstates the rating by a tenth of a point.
$starBox = $size === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5 sm:h-4 sm:w-4';
$star    = '<svg viewBox="0 0 20 19" class="' . $starBox . ' shrink-0" fill="currentColor" aria-hidden="true">'
         . '<path d="M10 0l2.9 6.2 6.6.9-4.8 4.7 1.2 6.8L10 15.4 4.1 18.6l1.2-6.8L.5 7.1l6.6-.9z"/></svg>';
$stars   = str_repeat($star, 5);

// At full size a badge measures around 375px, wider than a 390px phone's
// content column, so everything in it steps down below sm rather than any of it
// being dropped: a rating badge that hides its count is not much of one.
$pill = $size === 'sm'
    ? 'gap-2 py-1.5 pl-1.5 pr-3'
    : 'gap-2 py-2 pl-2 pr-3 sm:gap-3 sm:pl-2.5 sm:pr-4';
$disc     = $size === 'sm' ? 'h-5 w-5' : 'h-6 w-6 sm:h-7 sm:w-7';
$markBox  = $size === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5 sm:h-4 sm:w-4';
$figure   = $size === 'sm' ? 'text-xs' : 'text-sm';
$caption  = $size === 'sm'
    ? 'text-[10px] tracking-[0.1em]'
    : 'text-[10px] tracking-[0.1em] sm:text-[11px] sm:tracking-[0.18em]';
?>
<div class="flex <?= $layout === 'stack' ? 'flex-col items-start gap-2.5' : 'flex-wrap items-center justify-center gap-3' ?>">
    <?php foreach ($badges as $b): ?>
        <a href="<?= esc($b['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
           class="group inline-flex max-w-full items-center rounded-full border border-white/20 bg-brand-black/40 backdrop-blur transition hover:border-white/45 hover:bg-brand-black/60 <?= $pill ?>"
           aria-label="<?= esc($b['aria'], 'attr') ?> <?= esc(lang('Site.reviews.badge_hint'), 'attr') ?>">

            <?php // Each network's mark keeps its own colours, which need a light
                  // disc behind them to read on a dark ground. ?>
            <span class="grid <?= $disc ?> shrink-0 place-items-center rounded-full bg-white">
                <?php if ($b['mark'] === 'google'): ?>
                    <svg viewBox="0 0 48 48" class="<?= $markBox ?>" aria-hidden="true" focusable="false">
                        <path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.2-.4-4.7H24v8.9h11.8c-.5 2.7-2 5-4.4 6.6v5.5h7.1c4.2-3.8 6.6-9.5 6.6-16.3z"/>
                        <path fill="#34A853" d="M24 46c6 0 11-2 14.5-5.2l-7.1-5.5c-2 1.3-4.5 2.1-7.4 2.1-5.7 0-10.5-3.8-12.3-9H4.4v5.7C7.9 41.1 15.4 46 24 46z"/>
                        <path fill="#FBBC05" d="M11.7 28.4c-.5-1.3-.7-2.8-.7-4.4s.3-3 .7-4.4v-5.7H4.4A22 22 0 0 0 2 24c0 3.6.9 6.9 2.4 9.9l7.3-5.5z"/>
                        <path fill="#EA4335" d="M24 10.2c3.2 0 6.1 1.1 8.4 3.3l6.3-6.3C34.9 3.6 30 1.6 24 1.6 15.4 1.6 7.9 6.5 4.4 13.7l7.3 5.7c1.8-5.3 6.6-9.2 12.3-9.2z"/>
                    </svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" class="<?= $markBox ?>" aria-hidden="true" focusable="false">
                        <path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.96h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/>
                    </svg>
                <?php endif; ?>
            </span>

            <span class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <span class="<?= $figure ?> font-bold leading-none text-white"><?= esc($b['figure']) ?></span>
                <?php if ($b['stars'] !== null): ?>
                    <span class="relative inline-flex" aria-hidden="true">
                        <span class="flex gap-0.5 text-white/25"><?= $stars ?></span>
                        <span class="absolute inset-y-0 left-0 overflow-hidden"
                              style="width: <?= esc(number_format($b['stars'], 2, '.', ''), 'attr') ?>%">
                            <span class="flex gap-0.5 text-[#FBBC05]"><?= $stars ?></span>
                        </span>
                    </span>
                <?php endif; ?>
            </span>

            <?php if ($b['label'] !== ''): ?>
                <span class="whitespace-nowrap font-semibold uppercase text-white/70 transition group-hover:text-white/90 <?= $caption ?>">
                    <?= esc($b['label']) ?>
                </span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
