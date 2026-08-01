<?php helper('norlanka');
// Optional film beneath the heading, filling the column the copy leaves empty.
$video  = ! empty($content['video']) && is_file(FCPATH . ltrim((string) $content['video'], '/')) ? $content['video'] : null;
$poster = ! empty($content['poster']) && is_file(FCPATH . ltrim((string) $content['poster'], '/')) ? $content['poster'] : null;
?>
<section class="bg-brand-black py-16">
    <div class="container-x grid gap-12 md:grid-cols-2 md:items-start">
        <div data-gsap="reveal">
            <?php if (! empty($content['eyebrow'])): ?>
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc(t_field($content['eyebrow'])) ?></p>
            <?php endif; ?>
            <h2 class="text-2xl font-semibold sm:text-3xl"><?= esc(t_field($content['title'] ?? [])) ?></h2>

            <?php if ($video !== null): ?>
                <!-- A film the reader chooses to watch, so it keeps its controls
                     and poster rather than autoplaying beside the body copy. -->
                <figure class="isolate mt-8 overflow-hidden rounded-2xl border border-white/10">
                    <video controls preload="metadata" playsinline class="aspect-video w-full bg-black object-cover"
                           <?= $poster !== null ? 'poster="' . esc($poster, 'attr') . '"' : '' ?>>
                        <source src="<?= esc($video, 'attr') ?>" type="video/mp4">
                    </video>
                    <?php if (! empty($content['video_caption'])): ?>
                        <figcaption class="px-5 py-4 text-sm text-white/55"><?= esc(t_field($content['video_caption'])) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>
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
