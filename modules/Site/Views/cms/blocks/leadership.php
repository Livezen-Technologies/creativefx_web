<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; } ?>
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['title'])): ?>
            <h2 class="mb-10 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($content['items'] as $item):
                // Portrait photo when the file exists (drop-in at the seeded path);
                // an initial avatar otherwise.
                $photo = ! empty($item['photo']) && is_file(FCPATH . ltrim((string) $item['photo'], '/')) ? $item['photo'] : null; ?>
                <article class="rounded-2xl border border-white/10 bg-white/[0.02] p-7" data-gsap="reveal">
                    <?php if ($photo): ?>
                        <figure class="isolate mb-5 overflow-hidden rounded-xl border border-white/10">
                            <img src="<?= esc($photo, 'attr') ?>" alt="<?= esc(t_field($item['name'] ?? []), 'attr') ?>" loading="lazy" class="aspect-square w-full object-cover">
                        </figure>
                    <?php endif; ?>
                    <div class="flex items-center gap-4">
                        <?php if (! $photo): ?>
                            <div class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand-red/20 text-lg font-bold text-brand-red">
                                <?= esc(strtoupper(mb_substr(t_field($item['name'] ?? ['en' => '?']), 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3 class="text-lg font-semibold leading-snug"><?= esc(t_field($item['name'] ?? [])) ?></h3>
                            <p class="mt-1 text-xs uppercase tracking-widest text-white/50"><?= esc(t_field($item['role'] ?? [])) ?></p>
                        </div>
                    </div>
                    <?php if (! empty($item['message'])): ?>
                        <p class="mt-4 text-sm leading-relaxed text-white/65">&ldquo;<?= esc(t_field($item['message'])) ?>&rdquo;</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
