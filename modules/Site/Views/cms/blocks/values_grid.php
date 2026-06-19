<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-10 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($content['items'] as $i => $item): ?>
                <article class="rounded-2xl border border-white/10 bg-white/[0.02] p-7 transition hover:border-brand-red/60" data-gsap="reveal">
                    <span class="text-sm font-semibold text-brand-red"><?= sprintf('%02d', $i + 1) ?></span>
                    <h3 class="mt-3 text-xl font-semibold"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                    <?php if (! empty($item['text'])): ?>
                        <p class="mt-2 text-sm leading-relaxed text-white/60"><?= esc(t_field($item['text'])) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
