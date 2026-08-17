<?php helper(['norlanka', 'url']); if (empty($content['items']) || ! is_array($content['items'])) { return; }

/**
 * Client / brand logo wall.
 *
 * Supplied logo files come in every palette and half of them are drawn in
 * black, so each mark sits on the same light plate the certifications strip
 * uses (.brand-card) rather than straight on the dark ground. Desaturating by
 * default makes a mixed set read as one wall; colour returns on hover/focus.
 */
$grayscale = ! array_key_exists('grayscale', $content) || ! empty($content['grayscale']);

// Resolve everything up front so the markup stays flat, and so a wall whose
// items are all unusable returns without printing an empty section.
$marks = [];
foreach ($content['items'] as $item) {
    if (! is_array($item)) {
        continue;
    }

    // The name is the plate's only label — alt text, fallback wordmark and the
    // link's accessible name all come from it — so a nameless item is skipped
    // rather than rendered as an unlabelled logo.
    $name = trim(t_field($item['name'] ?? []));
    if ($name === '') {
        continue;
    }

    $logo = ! empty($item['logo']) && is_file(FCPATH . ltrim((string) $item['logo'], '/')) ? $item['logo'] : null;
    $url  = trim((string) ($item['url'] ?? ''));
    $href = null;
    if ($url !== '') {
        $href = str_starts_with($url, 'http') || str_starts_with($url, '/') ? $url : locale_url($url);
    }

    $marks[] = [
        'name'     => $name,
        'logo'     => $logo,
        'href'     => $href,
        'external' => $href !== null && str_starts_with($href, 'http'),
    ];
}
if ($marks === []) { return; }

/**
 * Both plate classes are sized for the marquee they were written for — fixed
 * rem widths, two different heights and two different corner radii. In a grid
 * cell that fixed width overflows the column on a narrow screen, so force the
 * width fluid and level the two variants to one height and to the 1rem radius
 * every other card in the block set uses. `group` is the hover target that
 * brings the logo back to full colour, so it is only needed while
 * desaturation is on.
 */
$plateBase = '!h-24 !w-full !rounded-2xl' . ($grayscale ? ' group' : '');

// Only a plate that actually goes somewhere reacts to pointer and keyboard.
$plateLink = 'transition hover:border-brand-red/60 focus:outline-none focus-visible:ring-2'
    . ' focus-visible:ring-brand-red focus-visible:ring-offset-2 focus-visible:ring-offset-brand-black';

// Desaturation is a block-wide switch, not a per-item one — resolve it once.
$logoAttr = $grayscale
    ? ' class="grayscale transition duration-300 group-hover:grayscale-0 group-focus-visible:grayscale-0"'
    : '';
?>
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

        <!-- One reveal on the grid, not one per plate: a wall is read as a block. -->
        <ul class="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" data-gsap="reveal">
            <?php foreach ($marks as $mark):
                // A mark still awaiting artwork falls back to the certification
                // wordmark badge — never a broken <img>.
                $plate = ($mark['logo'] !== null ? 'brand-card' : 'cert-card')
                    . ' ' . $plateBase
                    . ($mark['href'] !== null ? ' ' . $plateLink : '');

                // One element carries the plate whether or not the mark links
                // out, so the logo treatment is not repeated across two branches.
                $tag = $mark['href'] !== null ? 'a' : 'span'; ?>
                <li>
                    <<?= $tag ?> class="<?= $plate ?>"<?php if ($mark['href'] !== null): ?> href="<?= esc($mark['href'], 'attr') ?>"<?= $mark['external'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?><?php endif; ?>>
                        <?php if ($mark['logo'] !== null): ?>
                            <img src="<?= esc($mark['logo'], 'attr') ?>" alt="<?= esc($mark['name'], 'attr') ?>" loading="lazy" width="160" height="64"<?= $logoAttr ?>>
                        <?php else: ?>
                            <?= esc($mark['name']) ?>
                        <?php endif; ?>
                        <?php if ($mark['external']): ?>
                            <!-- Named for screen readers: the link leaves the site. -->
                            <span class="sr-only"><?= esc(lang('Site.portfolio.new_tab')) ?></span>
                        <?php endif; ?>
                    </<?= $tag ?>>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
