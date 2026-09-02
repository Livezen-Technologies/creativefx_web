<?php helper('norlanka'); if (empty($section['blocks'])) { return; }

/**
 * The bodies whose standards the factory is held to, as a static row.
 *
 * Deliberately not the marquee the CMS pages use for certifications: that one
 * earns its motion with a dozen marks, and three sliding past forever reads as
 * a bug rather than a list. Three marks fit on one line at every width worth
 * designing for.
 *
 * Artwork is matched by a slug of the mark's own name, the same convention the
 * partners block uses, so supplying a logo is a matter of dropping the file in
 * and nothing else. Until then the mark shows as its name, which is honest and
 * still tells a visitor what the certificate is.
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

$content = json_decode($section['blocks'][0]['content'] ?? '[]', true) ?: [];
$marks   = [];

foreach ($content['items'] ?? [] as $item) {
    $label = is_array($item) ? t_field($item) : (string) $item;
    if (trim($label) === '') {
        continue;
    }
    $marks[] = ['label' => $label, 'logo' => $resolve($label)];
}

if ($marks === []) { return; }
?>
<section class="border-y border-white/10 bg-brand-black py-24 sm:py-28">
    <div class="container-x text-center">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="eyebrow justify-center" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>

        <?php if (! empty($content['title'])): ?>
            <h2 class="mt-5 text-3xl font-bold leading-tight sm:text-5xl" data-gsap="reveal">
                <?= esc(t_field($content['title'])) ?>
            </h2>
        <?php endif; ?>

        <?php if (! empty($content['intro'])): ?>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-white/70" data-gsap="reveal">
                <?= esc(t_field($content['intro'])) ?>
            </p>
        <?php endif; ?>

        <ul class="mt-14 flex flex-wrap items-stretch justify-center gap-6" data-gsap="reveal">
            <?php foreach ($marks as $mark): ?>
                <li class="flex w-full max-w-[15rem] items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] p-7 backdrop-blur sm:w-56">
                    <?php if ($mark['logo']): ?>
                        <!-- Certificate marks are printed artwork on white; a plain
                             white tile behind them keeps their own colours true
                             instead of leaving them to sit on the dark ground. -->
                        <span class="flex h-24 w-full items-center justify-center rounded-xl bg-white p-3">
                            <img src="<?= esc(media_src($mark['logo']), 'attr') ?>"
                                 alt="<?= esc($mark['label'], 'attr') ?>" loading="lazy"
                                 class="max-h-full w-auto max-w-full object-contain">
                        </span>
                    <?php else: ?>
                        <span class="text-sm font-semibold uppercase leading-snug tracking-wider text-white/75">
                            <?= esc($mark['label']) ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
