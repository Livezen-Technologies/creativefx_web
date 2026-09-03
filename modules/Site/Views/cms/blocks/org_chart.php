<?php
helper('norlanka');

/**
 * The Authority's reporting structure as a chart the browser draws, not a
 * scanned organogram.
 *
 * Clause 3.9 C(f) asks for the organisational structure "rendered as an
 * interactive chart rather than a scanned image", and the reason is practical:
 * a scan cannot be read on a telephone, cannot be searched, cannot be
 * translated with the rest of the site and cannot be read aloud. This is a
 * nested list underneath — which is what a screen reader announces — styled
 * into a chart for everyone else.
 */
$tree = [
    'label' => 'Board of the Authority',
    'note'  => 'Appointed under the Tea Small Holdings Development Law No. 35 of 1975',
    'children' => [[
        'label' => 'Chairman',
        'children' => [[
            'label' => 'Director General',
            'note'  => 'Chief executive, accountable to the Board',
            'children' => [
                ['label' => 'Deputy Director General (Development)', 'children' => [
                    ['label' => 'Development Division', 'note' => 'Replanting, new planting, soil conservation'],
                    ['label' => 'Extension & Training Division', 'note' => 'Field extension, Hantana National Training Centre'],
                    ['label' => 'Planning & Monitoring', 'note' => 'Strategic plan, targets, statistics'],
                ]],
                ['label' => 'Deputy Director General (Administration)', 'children' => [
                    ['label' => 'Administration Division', 'note' => 'Establishments, HR, Right to Information'],
                    ['label' => 'Finance Division', 'note' => 'Budget, payments, subsidy disbursement'],
                    ['label' => 'Societies & Marketing Division', 'note' => 'Societies, Tea Shakthi, leaf quality'],
                    ['label' => 'Information & Communication Technology', 'note' => 'Registers, systems, this website'],
                ]],
                ['label' => 'Internal Audit', 'note' => 'Reports separately from the divisions it audits'],
                ['label' => 'Regional Offices (9)', 'note' => 'Galle · Matara · Ratnapura · Kegalle · Kalutara · Kandy · Nuwara Eliya · Bandarawela · Head Office region', 'children' => [
                    ['label' => 'Sub offices (26)', 'children' => [
                        ['label' => 'Tea Inspector / Extension Officer ranges (147)', 'note' => 'The smallholder’s point of contact with the Authority'],
                    ]],
                ]],
            ],
        ]],
    ]],
];

/** Render one node and, recursively, everything under it. */
$node = static function (array $item, int $depth) use (&$node): string {
    $tone = $depth === 0
        ? 'border-brand-red bg-brand-red/10'
        : ($depth <= 2 ? 'border-line bg-surface' : 'border-line bg-brand-black');

    $html = '<li class="org-node">';
    $html .= '<div class="org-card rounded-xl border ' . $tone . ' px-4 py-3">';
    $html .= '<p class="text-sm font-semibold leading-snug">' . esc($item['label']) . '</p>';
    if (! empty($item['note'])) {
        $html .= '<p class="mt-1 text-xs leading-relaxed text-white/55">' . esc($item['note']) . '</p>';
    }
    $html .= '</div>';

    if (! empty($item['children'])) {
        $html .= '<ul class="org-children">';
        foreach ($item['children'] as $child) {
            $html .= $node($child, $depth + 1);
        }
        $html .= '</ul>';
    }

    return $html . '</li>';
};
?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-2xl font-semibold sm:text-3xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>

        <?php // The chart is wider than a phone. It scrolls inside its own box
              // rather than making the page scroll sideways. ?>
        <div class="mt-8 overflow-x-auto pb-4">
            <ul class="org-tree" role="tree" aria-label="<?= esc(lang('Site.about.org_chart_label'), 'attr') ?>">
                <?= $node($tree, 0) ?>
            </ul>
        </div>
    </div>
</section>
