<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<!-- Values as flip tiles: the value name on the front; hover/focus flips the
     tile to reveal the description (per the content sheet). -->
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-10 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($content['items'] as $i => $item): $hasBack = ! empty($item['text']); ?>
                <article class="flip-card <?= $hasBack ? '' : 'flip-card--static' ?>" tabindex="0" data-gsap="reveal">
                    <div class="flip-card__inner">
                        <div class="flip-card__face flip-card__front rounded-2xl border border-white/10 bg-white/[0.02] p-7">
                            <span class="text-sm font-semibold text-brand-red"><?= sprintf('%02d', $i + 1) ?></span>
                            <h3 class="mt-3 text-xl font-semibold"><?= esc(t_field($item['title'] ?? [])) ?></h3>
                            <?php if ($hasBack): ?>
                                <span class="mt-4 inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-white/35">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9a8 8 0 0 1 14.2-3.4M20 15a8 8 0 0 1-14.2 3.4M18 2v4h-4M6 22v-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            <?php endif; ?>
                        </div>
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
