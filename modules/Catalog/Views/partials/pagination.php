<?php
helper(['norlanka', 'url']);

/**
 * Numbered pagination for a listing.
 *
 * Every control is a plain link with a real href, for the same reason the
 * facets are: page four of the catalogue has to be an address. A listing that
 * pages by script has one URL for all of it, so a crawler sees a twelfth of the
 * courses and a visitor cannot send anybody past the first screen.
 *
 * Renders nothing at all when there is one page. A bar reading "Page 1 of 1" is
 * furniture that tells the reader what they can already see, and it costs a
 * keyboard user two tab stops to get past it.
 *
 * @var int    $page     the page asked for, 1-based
 * @var int    $total    rows across every page, not on this one
 * @var int    $perPage
 * @var string $base     the listing's own URL, with no query string on it
 * @var array<string,string> $params  facets to carry on to the next page
 */
$base    = $base ?? current_url();
$params  = $params ?? [];
$perPage = max(1, (int) ($perPage ?? 12));
$pages   = (int) ceil(max(0, (int) $total) / $perPage);

if ($pages < 2) {
    return;
}

// A hand-typed ?page=99 lands past the end. The grid above has already shown
// its empty state; this bar should offer the way back rather than mark a page
// that does not exist as current.
$page = min(max(1, (int) $page), $pages);

$href = static function (int $number) use ($base, $params): string {
    $query = $params;
    // Page one is the bare address. /courses and /courses?page=1 are the same
    // listing, and publishing both invites a crawler to index it twice.
    unset($query['page']);
    if ($number > 1) {
        $query['page'] = $number;
    }

    return $query === [] ? $base : $base . '?' . http_build_query($query);
};

// First, last, and the current page with one either side; the gaps between
// them become ellipses. Without the window a catalogue of forty pages puts
// forty links on every page of itself, which is both unreadable and a lot of
// crawl budget spent on the same twelve courses.
$numbers = [];
for ($n = 1; $n <= $pages; $n++) {
    if ($n === 1 || $n === $pages || abs($n - $page) <= 1) {
        $numbers[] = $n;
    }
}

$numberClass  = 'inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-full border px-3 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red ';
$restingClass = $numberClass . 'border-line text-white/65 hover:border-brand-red/50 hover:text-white';
$currentClass = $numberClass . 'border-brand-red bg-brand-red/10 font-semibold text-brand-red';
?>
<nav aria-labelledby="pagination-status" class="mt-12 border-t border-line pt-8">
    <?php // The status line doubles as the accessible name of the navigation,
          // so the whole widget announces as "Page 2 of 5" without a label that
          // exists only for screen readers and can therefore drift from what
          // the page actually shows. ?>
    <p id="pagination-status" class="text-center text-sm text-white/55">
        <?= esc(lang('Site.search.page_of', [$page, $pages])) ?>
    </p>

    <ol class="mt-4 flex flex-wrap items-center justify-center gap-2">
        <?php if ($page > 1): ?>
            <li>
                <?php // rel="prev"/rel="next" tell a user agent that these pages
                      // are one sequence rather than three unrelated documents.
                      // Search engines no longer index on it, but readers,
                      // prefetchers and reading-mode tools still follow it. ?>
                <a href="<?= esc($href($page - 1)) ?>" rel="prev" class="btn-ghost !px-5 !py-2.5 !text-xs">
                    <span aria-hidden="true">&larr;</span> <?= esc(lang('Site.search.prev')) ?>
                </a>
            </li>
        <?php endif; ?>

        <?php $previous = 0; ?>
        <?php foreach ($numbers as $number): ?>
            <?php if ($number - $previous > 1): ?>
                <?php // A gap in the run of numbers, not a link and not a page:
                      // hidden from assistive technology because "ellipsis"
                      // read aloud between two page numbers is noise. ?>
                <li aria-hidden="true" class="px-1 text-white/35">&hellip;</li>
            <?php endif; ?>
            <li>
                <?php if ($number === $page): ?>
                    <a href="<?= esc($href($number)) ?>" aria-current="page" class="<?= $currentClass ?>"><?= $number ?></a>
                <?php else: ?>
                    <a href="<?= esc($href($number)) ?>"
                       aria-label="<?= esc(lang('Site.search.page_of', [$number, $pages]), 'attr') ?>"
                       class="<?= $restingClass ?>"><?= $number ?></a>
                <?php endif; ?>
            </li>
            <?php $previous = $number; ?>
        <?php endforeach; ?>

        <?php if ($page < $pages): ?>
            <li>
                <a href="<?= esc($href($page + 1)) ?>" rel="next" class="btn-ghost !px-5 !py-2.5 !text-xs">
                    <?= esc(lang('Site.search.next')) ?> <span aria-hidden="true">&rarr;</span>
                </a>
            </li>
        <?php endif; ?>
    </ol>
</nav>
