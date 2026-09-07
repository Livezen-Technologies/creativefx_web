<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The catalogue: /courses, and every category page beneath it.
 *
 * One view serves both, because they are the same page with a different scope —
 * the category simply arrives with its subtree already folded into the filters.
 * Splitting them would mean maintaining two grids, two empty states and two
 * paginations, and they would drift.
 *
 * The whole page is addressable. Facets are links, the search is a GET form,
 * and pagination is links, so every state of this catalogue is a URL that can
 * be shared, bookmarked, crawled and returned to with the back button. Nothing
 * here needs JavaScript to work, which is also what makes it fast on the phone
 * on a slow connection that most of this traffic arrives on.
 *
 * @var list<array>          $courses  each decorated with from_cents and next_date
 * @var int                  $total    matches across every page
 * @var int                  $page
 * @var int                  $perPage
 * @var list<array>          $tree     the category tree
 * @var array<string,mixed>  $filters
 * @var string               $currency
 * @var array|null           $category
 * @var list<array>          $children immediate sub-categories, on a category page
 * @var list<array>          $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$category = $category ?? null;
$children = $children ?? [];
$filters  = $filters ?? [];

// Both partials build links off this, so it is settled once. On a category page
// it is the category's own address, which is what keeps a facet click inside
// the category instead of throwing the visitor back to the whole catalogue.
$base = $category !== null
    ? locale_url('courses/' . $category['slug'])
    : locale_url('courses');

// The facets as they appear in a query string. `category_ids` is deliberately
// not among them: it is derived from the route, and echoing it back as
// ?category_ids[]=7 would put an internal primary key into an address that is
// meant to be shared and would survive a re-seed of the taxonomy.
$params = [];
foreach (['pillar', 'level', 'mode', 'q'] as $key) {
    if (isset($filters[$key]) && (string) $filters[$key] !== '') {
        $params[$key] = (string) $filters[$key];
    }
}

$heading = $category !== null ? t_field($category['name']) : lang('Catalog.courses.title');
$intro   = $category !== null ? t_field($category['summary']) : lang('Catalog.courses.meta');

// lang() returns the key itself when it misses, so an unset or unexpected
// pillar would print "Catalog.pillar." above the heading. Checked rather than
// trusted.
$eyebrow = '';
if ($category !== null && in_array((string) ($category['pillar'] ?? ''), ['adobe', 'ai', 'design'], true)) {
    $eyebrow = lang('Catalog.pillar.' . $category['pillar']);
}

// The count belongs beside the heading rather than above the grid: it is the
// first thing somebody checks after applying a filter, and it answers "did that
// do anything" without a scroll.
$aside = $total > 0
    ? '<p class="text-sm text-white/60">' . esc(lang('Catalog.courses.showing', [count($courses), $total])) . '</p>'
    : '';
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => $eyebrow,
    'heading' => $heading,
    'intro'   => $intro,
    'crumbs'  => $crumbs,
    'aside'   => $aside,
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-12 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-14">
    <?php // The rail comes first in the document as well as on the screen:
          // filters precede the results they filter, which is the order a
          // keyboard or screen-reader user needs them in. ?>
    <?= view('Modules\Catalog\Views\partials\facets', [
        'filters'  => $filters,
        'tree'     => $tree,
        'category' => $category,
        'base'     => $base,
        'params'   => $params,
    ], ['saveData' => false]) ?>

    <div class="min-w-0">
        <?php if ($children !== []): ?>
            <?php // Narrowing down at the point of decision. The rail's tree is
                  // navigation for the whole site; this row is the one question
                  // somebody on this particular page is most likely to have,
                  // which is "is there a more specific list than this one". ?>
            <ul class="mb-8 flex flex-wrap gap-2">
                <?php foreach ($children as $child): ?>
                    <li>
                        <a href="<?= esc(locale_url('courses/' . $child['slug'])) ?>"
                           class="chip transition hover:bg-brand-red/10 hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                            <?= esc(t_field($child['name'])) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($courses === []): ?>
            <?php // Never a blank column. A catalogue that answers a filter with
                  // nothing at all reads as broken rather than as empty, and the
                  // visitor's next move is the back button out of the site. ?>
            <div class="rounded-3xl border border-line bg-surface p-8 text-center sm:p-12">
                <p class="text-lg font-semibold"><?= esc(lang('Catalog.courses.none')) ?></p>
                <p class="mx-auto mt-3 max-w-md leading-relaxed text-white/65"><?= esc(lang('Catalog.courses.none_hint')) ?></p>

                <?php if ($params !== [] || $category !== null): ?>
                    <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand mt-6"><?= esc(lang('Catalog.courses.all')) ?></a>
                <?php else: ?>
                    <?php // The bare catalogue with nothing published in it yet:
                          // "all courses" would point at the page already being
                          // read, so the way out is the schedule, which lists the
                          // same thing by date and is honest about being empty
                          // in its own words. ?>
                    <a href="<?= esc(locale_url('schedule')) ?>" class="btn-brand mt-6"><?= esc(lang('Catalog.schedule.title')) ?></a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($courses as $course): ?>
                    <?= view('Modules\Catalog\Views\partials\course_card', [
                        'course' => $course + ['currency' => $currency],
                    ], ['saveData' => false]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= view('Modules\Catalog\Views\partials\pagination', [
            'page'    => $page,
            'total'   => $total,
            'perPage' => $perPage,
            'base'    => $base,
            'params'  => $params,
        ], ['saveData' => false]) ?>
    </div>
</div>

<?= $this->endSection() ?>
