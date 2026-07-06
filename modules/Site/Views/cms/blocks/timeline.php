<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<section class="bg-brand-black py-20">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-12 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <ol class="relative ml-3 border-l border-white/15">
            <?php foreach ($content['items'] as $item): ?>
                <li class="mb-10 pl-8" data-gsap="reveal">
                    <span class="absolute -left-[7px] mt-1 h-3 w-3 rounded-full bg-brand-red"></span>
                    <div class="text-sm font-semibold uppercase tracking-widest text-brand-red"><?= esc(t_field($item['year'] ?? [])) ?></div>
                    <h3 class="mt-1 text-xl font-semibold"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                    <?php if (! empty($item['text'])): ?>
                        <p class="mt-2 max-w-2xl text-white/60"><?= esc(t_field($item['text'])) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
