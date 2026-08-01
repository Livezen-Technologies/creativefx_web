<?php helper('norlanka');
// Optional clip beneath the heading, filling the column the copy leaves empty.
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
                <!-- Muted b-roll, so it autoplays; the toggle is there because a
                     looping clip beside body copy needs to be stoppable. -->
                <figure class="relative isolate mt-8 overflow-hidden rounded-2xl border border-white/10"
                        x-data="{ playing: true, toggle() { const v = $refs.clip; if (! v) return; if (v.paused) { v.play(); this.playing = true; } else { v.pause(); this.playing = false; } } }">
                    <video x-ref="clip" class="aspect-[4/3] w-full bg-black object-cover"
                           autoplay muted loop playsinline preload="metadata"
                           <?= $poster !== null ? 'poster="' . esc($poster, 'attr') . '"' : '' ?>>
                        <source src="<?= esc($video, 'attr') ?>" type="video/mp4">
                    </video>
                    <button type="button" @click="toggle()"
                            class="absolute bottom-3 right-3 inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/25 bg-brand-black/60 text-white/80 backdrop-blur transition hover:border-white hover:text-white"
                            :aria-label="playing ? 'Pause video' : 'Play video'">
                        <svg x-show="playing" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                        <svg x-show="!playing" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5l12 7-12 7z"/></svg>
                    </button>
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
