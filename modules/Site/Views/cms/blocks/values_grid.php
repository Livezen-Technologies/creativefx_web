<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; }

// Small icon set for the value tiles (Lucide-style 24px stroke paths).
$valueIcons = [
    'shield'    => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z M9 12l2 2 4-4',
    'users'     => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m22 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
    'bulb'      => 'M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2Z',
    'handshake' => 'm11 17 2 2a1 1 0 1 0 3-3 M14 14l2.5 2.5a1 1 0 1 0 3-3l-3.9-3.9a2 2 0 0 1 0-2.8l.4-.4a2 2 0 0 1 2.8 0L21 8 M18 15l2-2 M5 8l-3 3 4 4 3-3 M9 14l-2 2a1 1 0 1 0 3 3l2-2',
    'leaf'      => 'M11 20A7 7 0 0 1 4 13c0-4 3-8 8-10 0 0 8 3 8 10a7 7 0 0 1-7 7h-2ZM6 21c1-3 3-6 6-8',
    'heart'     => 'M19 14c1.5-1.5 3-3.3 3-5.5A5.5 5.5 0 0 0 12 5 5.5 5.5 0 0 0 2 8.5c0 2.2 1.5 4 3 5.5l7 7Z',
];
?>
<!-- Values as flip tiles: photo, icon and name on the front; hover/focus flips
     the tile to reveal the description (per the content sheet). -->
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-10 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($content['items'] as $i => $item):
                $hasBack = ! empty($item['text']);
                $image   = ! empty($item['image']) && is_file(FCPATH . ltrim((string) $item['image'], '/')) ? $item['image'] : null;
                $icon    = $valueIcons[$item['icon'] ?? ''] ?? null; ?>
                <article class="flip-card <?= $image ? 'flip-card--photo' : '' ?> <?= $hasBack ? '' : 'flip-card--static' ?>" tabindex="0" data-gsap="reveal">
                    <div class="flip-card__inner">
                        <?php if ($image): ?>
                            <!-- Photo front: image, scrim, icon + name pinned to the bottom. -->
                            <div class="flip-card__face flip-card__front value-tile">
                                <img src="<?= esc($image, 'attr') ?>" alt="" loading="lazy" class="value-tile__img">
                                <span class="value-tile__scrim" aria-hidden="true"></span>
                                <span class="value-tile__body">
                                    <?php if ($icon): ?>
                                        <svg class="value-tile__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= esc($icon, 'attr') ?>"/></svg>
                                    <?php endif; ?>
                                    <h3 class="value-tile__title"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                                </span>
                                <?php if ($hasBack): ?>
                                    <span class="value-tile__hint" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9a8 8 0 0 1 14.2-3.4M20 15a8 8 0 0 1-14.2 3.4M18 2v4h-4M6 22v-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="flip-card__face flip-card__front rounded-2xl border border-white/10 bg-white/[0.02] p-7">
                                <span class="text-sm font-semibold text-brand-red"><?= sprintf('%02d', $i + 1) ?></span>
                                <h3 class="mt-3 text-xl font-semibold"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                                <?php if ($hasBack): ?>
                                    <span class="mt-4 inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-white/35">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9a8 8 0 0 1 14.2-3.4M20 15a8 8 0 0 1-14.2 3.4M18 2v4h-4M6 22v-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($hasBack): ?>
                            <div class="flip-card__face flip-card__back rounded-2xl border border-brand-red/40 bg-brand-red/[0.08] p-7">
                                <h3 class="text-sm font-semibold uppercase tracking-widest text-brand-red"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                                <p class="mt-3 text-sm leading-relaxed text-white/80"><?= esc(t_field($item['text'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
