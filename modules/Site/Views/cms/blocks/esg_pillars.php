<?php helper('norlanka'); if (empty($content['pillars']) || ! is_array($content['pillars'])) { return; } ?>
<section class="bg-brand-black py-20">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            <?php foreach ($content['pillars'] as $pillar): ?>
                <article class="rounded-2xl border border-white/10 bg-white/[0.02] p-8" data-gsap="reveal">
                    <h3 class="text-xl font-semibold text-brand-red"><?= esc(t_field($pillar['title'] ?? [])) ?></h3>
                    <?php if (! empty($pillar['items']) && is_array($pillar['items'])): ?>
                        <ul class="mt-4 space-y-3">
                            <?php foreach ($pillar['items'] as $item): ?>
                                <li class="flex gap-3 text-sm text-white/75">
                                    <span class="mt-1.5 h-1.5 w-1.5 flex-none rounded-full bg-brand-red"></span>
                                    <span><?= esc(t_field($item)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
