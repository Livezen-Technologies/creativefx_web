<?php helper('norlanka'); ?>
<section class="bg-brand-black py-16">
    <div class="container-x grid gap-12 md:grid-cols-2 md:items-start">
        <div data-gsap="reveal">
            <?php if (! empty($content['eyebrow'])): ?>
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc(t_field($content['eyebrow'])) ?></p>
            <?php endif; ?>
            <h2 class="text-2xl font-semibold sm:text-3xl"><?= esc(t_field($content['title'] ?? [])) ?></h2>
        </div>
        <div data-gsap="reveal">
            <?php if (! empty($content['body'])): ?>
                <div class="leading-relaxed text-white/70"><?= rich_text($content['body']) ?></div>
            <?php endif; ?>
            <?php if (! empty($content['items']) && is_array($content['items'])): ?>
                <ul class="mt-6 space-y-3">
                    <?php foreach ($content['items'] as $item): ?>
                        <li class="flex gap-3 text-white/80">
                            <span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-brand-red"></span>
                            <span><?= esc(t_field($item)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
