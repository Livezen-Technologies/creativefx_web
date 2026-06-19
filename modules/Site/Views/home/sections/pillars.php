<?php helper('norlanka'); if (empty($section['blocks'])) { return; } ?>
<section class="bg-brand-black py-24">
    <div class="container-x">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red" data-gsap="reveal">What we stand for</p>
        <div class="mt-10 grid gap-8 md:grid-cols-3">
            <?php foreach ($section['blocks'] as $i => $block): $c = json_decode($block['content'] ?? '[]', true) ?: []; ?>
                <article class="rounded-2xl border border-white/10 bg-white/[0.02] p-8 transition hover:border-brand-red/60" data-gsap="reveal">
                    <span class="text-sm font-semibold text-brand-red">0<?= $i + 1 ?></span>
                    <h3 class="mt-4 text-2xl font-semibold"><?= esc(t_field($c['title'] ?? [])) ?></h3>
                    <p class="mt-3 leading-relaxed text-white/60"><?= esc(t_field($c['text'] ?? [])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
