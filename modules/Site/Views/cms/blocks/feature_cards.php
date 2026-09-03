<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-3 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mb-10 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($content['items'] as $item): ?>
                <?php // An optional picture above the heading. Cards seeded without
                      // one render exactly as before, so no existing page moves. ?>
                <?php $img = ! empty($item['image']) && is_file(FCPATH . ltrim((string) $item['image'], '/'))
                    ? $item['image'] : null; ?>
                <article class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/60" data-gsap="reveal">
                    <?php if ($img !== null): ?>
                        <img src="<?= esc(media_src($img), 'attr') ?>"
                             alt="<?= esc(t_field($item['image_alt'] ?? []), 'attr') ?>"
                             loading="lazy" width="900" height="600"
                             class="aspect-[3/2] w-full object-cover">
                    <?php endif; ?>
                    <div class="p-7">
                    <h3 class="text-lg font-semibold text-brand-red"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                    <?php if (! empty($item['text'])): ?>
                        <p class="mt-3 text-sm leading-relaxed text-white/60"><?= esc(t_field($item['text'])) ?></p>
                    <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
