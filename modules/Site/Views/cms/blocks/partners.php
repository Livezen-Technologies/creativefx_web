<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; }

/**
 * Certification / partner marks as a continuously scrolling marquee.
 *
 * Each item resolves to a logo file when one exists in the folder below
 * (matched on a slug of the item's name, any common image extension), and
 * falls back to a wordmark badge until the artwork is supplied — so dropping
 * the official files in needs no code change.
 */
$logoDir = 'media/certifications/';
$exts    = ['svg', 'png', 'webp', 'jpg', 'jpeg'];

$resolve = static function (string $name) use ($logoDir, $exts): ?string {
    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    if ($slug === '') {
        return null;
    }
    foreach ($exts as $ext) {
        if (is_file(FCPATH . $logoDir . $slug . '.' . $ext)) {
            return '/' . $logoDir . $slug . '.' . $ext;
        }
    }
    return null;
};

// Resolve once, then render the list twice so the loop is seamless.
$marks = [];
foreach ($content['items'] as $item) {
    $label = is_array($item) ? t_field($item) : (string) $item;
    if (trim($label) === '') {
        continue;
    }
    $marks[] = ['label' => $label, 'logo' => $resolve($label)];
}
if ($marks === []) { return; }
?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-3 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mb-10 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="brand-marquee cert-marquee" data-gsap="reveal"
         aria-label="<?= esc(t_field($content['title'] ?? []), 'attr') ?>">
        <div class="brand-marquee__track">
            <?php foreach ([0, 1] as $copy): // duplicated track = seamless loop ?>
                <?php foreach ($marks as $mark): ?>
                    <span class="<?= $mark['logo'] ? 'brand-card' : 'cert-card' ?>" <?= $copy === 1 ? 'aria-hidden="true"' : '' ?>>
                        <?php if ($mark['logo']): ?>
                            <img src="<?= esc($mark['logo'], 'attr') ?>" alt="<?= esc($mark['label'], 'attr') ?>" loading="lazy" width="160" height="72">
                        <?php else: ?>
                            <?= esc($mark['label']) ?>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
