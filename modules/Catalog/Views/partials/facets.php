<?php
helper(['norlanka', 'catalog', 'url']);

/**
 * The catalogue's facet rail.
 *
 * Every control here is a link or a GET form. That is the whole decision, and
 * it is worth stating plainly: it makes a filtered catalogue an *address* —
 * /courses/photoshop?level=1&mode=CLASSROOM — which somebody can send to a
 * colleague, a crawler can follow and index, a campaign can point at, and the
 * back button can return to. A rail built out of fetch() and pushState has one
 * URL for every state of itself, and the state the visitor wanted to share is
 * not in it.
 *
 * Changing one facet keeps the others. A rail that resets the level whenever
 * the mode changes cannot be used to narrow anything down, and that is the only
 * thing a rail is for.
 *
 * @var array<string,mixed>  $filters   the facets as the controller validated them
 * @var list<array>          $tree      the category tree, each node carrying 'children'
 * @var array|null           $category  the category page this is rendering on, if any
 * @var string               $base      this listing's URL, with no query string
 * @var array<string,string> $params    the active facets, as query parameters
 */
$filters  = $filters ?? [];
$tree     = $tree ?? [];
$category = $category ?? null;
$base     = $base ?? current_url();
$params   = $params ?? [];

/** The address this rail's links point at with one facet changed or cleared. */
$facetUrl = static function (string $key, ?string $value) use ($base, $params): string {
    $query = $params;
    // Page is dropped on purpose: page four of one filter is rarely page four
    // of the next, and landing on an empty page four reads as a broken filter
    // rather than as the end of a shorter list.
    unset($query['page']);

    if ($value === null || $value === '') {
        unset($query[$key]);
    } else {
        $query[$key] = $value;
    }

    // Sorted, so the same set of facets is always the same URL whatever order
    // they were clicked in. Two addresses for one page of results is a
    // duplicate for a crawler and a miss for every cache in front of this.
    ksort($query);

    return $query === [] ? $base : $base . '?' . http_build_query($query);
};

$groups = [
    [
        'key'     => 'pillar',
        'label'   => lang('Catalog.courses.pillar'),
        'options' => [
            'adobe'  => lang('Catalog.pillar.adobe'),
            'ai'     => lang('Catalog.pillar.ai'),
            'design' => lang('Catalog.pillar.design'),
        ],
    ],
    [
        'key'     => 'level',
        'label'   => lang('Catalog.courses.level'),
        'options' => ['1' => level_label(1), '2' => level_label(2), '3' => level_label(3)],
    ],
    [
        'key'   => 'mode',
        'label' => lang('Catalog.courses.mode'),
        // Read from the model's own list rather than typed out again here. The
        // controller validates against that same constant and turns anything
        // else into a 404, so a fifth value written into this rail by hand
        // would be a link that 404s instead of a link that filters.
        'options' => array_combine(
            \Modules\Catalog\Models\CourseSessionModel::MODES,
            array_map('mode_label', \Modules\Catalog\Models\CourseSessionModel::MODES)
        ),
    ],
];

$chipBase    = 'chip transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red ';
$chipResting = $chipBase . 'hover:bg-brand-red/10 hover:text-brand-red';
$chipActive  = $chipBase . 'chip-accent font-semibold';

// Something is set if any facet other than the route's own category survives.
// The category is not one of these: clearing the filters inside Photoshop
// should leave the visitor in Photoshop, not throw them back to the top of the
// catalogue, which is a different intention entirely.
$hasFilters = array_diff_key($params, ['page' => null]) !== [];

/** The subject tree, at whatever depth the taxonomy actually has. */
$branch = static function (array $nodes, int $depth = 0) use (&$branch, $category): string {
    if ($nodes === []) {
        return '';
    }

    $currentId = $category === null ? 0 : (int) $category['id'];
    $out       = '<ul class="' . ($depth === 0 ? 'mt-2 space-y-1.5' : 'mt-1.5 space-y-1.5 border-l border-line pl-3') . '">';

    foreach ($nodes as $node) {
        $isCurrent = (int) $node['id'] === $currentId;
        $out .= '<li><a href="' . esc(locale_url('courses/' . $node['slug'])) . '"'
            . ($isCurrent ? ' aria-current="page"' : '')
            . ' class="' . ($isCurrent ? 'font-semibold text-brand-red' : 'text-white/65 transition hover:text-brand-red') . '">'
            . esc(t_field($node['name']))
            . '</a>'
            . $branch($node['children'] ?? [], $depth + 1)
            . '</li>';
    }

    return $out . '</ul>';
};
?>
<aside aria-labelledby="facets-title" class="lg:sticky lg:top-28 lg:self-start">
    <div class="flex items-baseline justify-between gap-3">
        <h2 id="facets-title" class="section-title text-base sm:text-lg"><?= esc(lang('Catalog.courses.filters')) ?></h2>
        <?php if ($hasFilters): ?>
            <a href="<?= esc($base) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Catalog.courses.clear')) ?>
            </a>
        <?php endif; ?>
    </div>

    <?php // The free-text search. GET, so the query lands in the address bar
          // and stays shareable, and therefore no CSRF token: there is nothing
          // to forge in a request that only reads. ?>
    <form method="get" action="<?= esc($base) ?>" role="search" aria-labelledby="facet-q-label" class="mt-6">
        <?php // A GET form replaces the entire query string on submit, so every
              // facet that is not a control inside this form has to travel as a
              // hidden input or it is silently dropped the moment somebody
              // searches — the classic way a filtered listing loses its filters.
              // `page` is deliberately not carried: a new search starts at one. ?>
        <?php foreach ($params as $key => $value): ?>
            <?php if ($key === 'q' || $key === 'page') {
                continue;
            } ?>
            <input type="hidden" name="<?= esc($key, 'attr') ?>" value="<?= esc($value, 'attr') ?>">
        <?php endforeach; ?>

        <label id="facet-q-label" for="facet-q" class="field-label"><?= esc(lang('Catalog.courses.search')) ?></label>
        <input id="facet-q" type="search" name="q" value="<?= esc((string) ($filters['q'] ?? ''), 'attr') ?>"
               maxlength="100" autocomplete="off" class="field">
        <button type="submit" class="btn-brand mt-3 w-full !px-4"><?= esc(lang('Site.search.button')) ?></button>
    </form>

    <?php foreach ($groups as $group): ?>
        <?php $active = (string) ($filters[$group['key']] ?? ''); ?>
        <section class="mt-8" aria-labelledby="facet-<?= esc($group['key'], 'attr') ?>">
            <h3 id="facet-<?= esc($group['key'], 'attr') ?>" class="field-label"><?= esc($group['label']) ?></h3>
            <ul class="mt-2 flex flex-wrap gap-2">
                <?php // "Any" is a link back to this listing without this one
                      // facet, not a link to the bare catalogue: it clears the
                      // row it sits under and leaves the rest standing. ?>
                <li>
                    <a href="<?= esc($facetUrl($group['key'], null)) ?>"
                       class="<?= $active === '' ? $chipActive : $chipResting ?>"
                       <?= $active === '' ? 'aria-current="page"' : '' ?>><?= esc(lang('Catalog.courses.any')) ?></a>
                </li>
                <?php foreach ($group['options'] as $value => $label): ?>
                    <li>
                        <a href="<?= esc($facetUrl($group['key'], (string) $value)) ?>"
                           class="<?= $active === (string) $value ? $chipActive : $chipResting ?>"
                           <?= $active === (string) $value ? 'aria-current="page"' : '' ?>><?= esc($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>

    <?php if ($tree !== []): ?>
        <section class="mt-8 border-t border-line pt-8" aria-labelledby="facet-browse">
            <h3 id="facet-browse" class="field-label"><?= esc(lang('Catalog.courses.browse')) ?></h3>
            <?php // The tree is navigation rather than a facet: a category is a
                  // page with its own address, heading and copy, so it changes
                  // the URL's path and not its query string. ?>
            <div class="text-sm"><?= $branch($tree) ?></div>
            <?php if ($category !== null): ?>
                <a href="<?= esc(locale_url('courses')) ?>" class="mt-4 inline-block text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                    <?= esc(lang('Catalog.courses.all')) ?>
                </a>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</aside>
