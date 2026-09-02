<?php helper(['norlanka', 'url']); if (empty($section['blocks'])) { return; }

/**
 * "Rooms & Suites" — the room types, each with its own photograph and a way to
 * ask for it. One block per room, so adding a third is a content change.
 */
$blocks = $section['blocks'];
$head   = json_decode($blocks[0]['content'] ?? '[]', true) ?: [];
$rooms  = [];
foreach (array_slice($blocks, 1) as $b) {
    $room = json_decode($b['content'] ?? '[]', true) ?: [];
    if ($room !== []) { $rooms[] = $room; }
}
if ($rooms === []) { return; }
?>
<section class="bg-brand-black py-20 sm:py-28">
    <div class="container-x">

        <div class="flex flex-wrap items-end justify-between gap-6" data-gsap="reveal">
            <div>
                <?php if (! empty($head['eyebrow'])): ?>
                    <p class="eyebrow"><?= esc(t_field($head['eyebrow'])) ?></p>
                <?php endif; ?>
                <?php if (! empty($head['title'])): ?>
                    <h2 class="mt-5 text-3xl font-bold sm:text-5xl"><?= esc(t_field($head['title'])) ?></h2>
                <?php endif; ?>
            </div>
            <?php if (! empty($head['button'])): ?>
                <a href="<?= esc(locale_url($head['url'] ?? 'accommodation')) ?>" class="btn-brand">
                    <?= esc(t_field($head['button'])) ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="mt-12 grid gap-10 sm:grid-cols-2">
            <?php foreach ($rooms as $room): ?>
                <article data-gsap="reveal">
                    <?php if (! empty($room['image'])): ?>
                        <img src="<?= esc(media_src($room['image']), 'attr') ?>"
                             alt="<?= esc(t_field($room['title'] ?? []), 'attr') ?>"
                             loading="lazy" width="900" height="600"
                             class="aspect-[3/2] w-full rounded-sm object-cover">
                    <?php endif; ?>
                    <h3 class="mt-7 text-2xl font-bold" style="color: rgb(var(--accent))">
                        <?= esc(t_field($room['title'] ?? [])) ?>
                    </h3>
                    <p class="mt-4 leading-relaxed text-white/70"><?= esc(t_field($room['text'] ?? [])) ?></p>
                    <?php if (! empty($room['button'])): ?>
                        <a href="<?= esc(locale_url($room['url'] ?? 'contact')) ?>" class="btn-brand mt-7">
                            <?= esc(t_field($room['button'])) ?>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

    </div>
</section>
