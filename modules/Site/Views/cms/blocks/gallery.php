<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<!-- Image gallery block. -->
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <?php if (! empty($content['title'])): ?>
            <h2 class="mt-2 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-gsap="reveal">
            <?php foreach ($content['items'] as $item): if (empty($item['src'])) { continue; } ?>
                <figure class="group isolate relative overflow-hidden rounded-2xl border border-white/10">
                    <img src="<?= esc(media_src($item['src']), 'attr') ?>" alt="<?= esc(t_field($item['caption'] ?? []), 'attr') ?>" loading="lazy"
                         class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.04]">
                    <?php if (! empty($item['caption'])): ?>
                        <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent px-5 pb-4 pt-10 text-sm font-medium text-[#f4f4f5]">
                            <?= esc(t_field($item['caption'])) ?>
                        </figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
