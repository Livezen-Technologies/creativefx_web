<?php helper(['norlanka', 'url']); if (empty($content['items']) || ! is_array($content['items'])) { return; }

// Service glyphs (Lucide-style 24px stroke paths) keyed by item['icon'], the
// same inline set idiom as values_grid.php — no icon font or sprite to ship.
$serviceIcons = [
    'camera'    => 'M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3Z M15 13a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    'mic'       => 'M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z M19 10v2a7 7 0 0 1-14 0v-2 M12 19v3 M8 22h8',
    'broadcast' => 'M4.9 19.1a10 10 0 0 1 0-14.2 M7.8 16.2a6 6 0 0 1 0-8.4 M16.2 7.8a6 6 0 0 1 0 8.4 M19.1 4.9a10 10 0 0 1 0 14.2 M14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z',
    'box'       => 'm21 8-9-5-9 5v8l9 5 9-5V8Z M3.3 7.7 12 12.5l8.7-4.8 M12 22V12.5',
    'megaphone' => 'm3 11 18-5v12L3 14v-3Z M11.6 16.8a3 3 0 1 1-5.8-1.6',
    'chart'     => 'M3 3v18h18 M8 17v-5 M13 17V8 M18 17v-3',
];
?>
<!-- Service cards, shared by the home page "Services Preview" and the Services
     overview. Each card is a single link target: the arrow stays the visible
     affordance while a stretched overlay inside that same anchor makes the whole
     card clickable, so screen readers still hear one link, not two. -->
<section class="bg-brand-black py-16">
    <div class="container-x">
        <?php if (! empty($content['eyebrow'])): ?>
            <p class="eyebrow" data-gsap="reveal"><?= esc(t_field($content['eyebrow'])) ?></p>
        <?php endif; ?>
        <?php if (! empty($content['title'])): ?>
            <h2 class="mt-5 text-3xl font-bold sm:text-4xl" data-gsap="reveal"><?= esc(t_field($content['title'])) ?></h2>
        <?php endif; ?>
        <?php if (! empty($content['intro'])): ?>
            <p class="mt-3 max-w-2xl text-white/60" data-gsap="reveal"><?= esc(t_field($content['intro'])) ?></p>
        <?php endif; ?>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($content['items'] as $item):
                $title = t_field($item['title'] ?? []);
                if ($title === '') { continue; } // an unnamed service has nothing to explore
                $image = ! empty($item['image']) && is_file(FCPATH . ltrim((string) $item['image'], '/')) ? $item['image'] : null;
                $icon  = $serviceIcons[$item['icon'] ?? ''] ?? null;
                // Absolute paths and full URLs pass through as-is; a bare slug is
                // a locale-prefixed route. No url at all = a plain, unlinked card.
                $url  = trim((string) ($item['url'] ?? ''));
                $href = $url === '' ? null : (($url[0] === '/' || str_starts_with($url, 'http')) ? $url : locale_url($url));
                // No Site.* string reads as a service CTA (the closest, showroom
                // and products keys, are scoped to those modules), so the label
                // comes from the payload; without one the arrow carries the link
                // and the sr-only service name names it.
                $cta = t_field($item['cta'] ?? []); ?>
                <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/60 focus-within:border-brand-red/60" data-gsap="reveal">
                    <?php if ($image !== null): ?>
                        <div class="relative overflow-hidden">
                            <!-- Decorative: the heading below already names the service. -->
                            <img src="<?= esc($image, 'attr') ?>" alt="" loading="lazy"
                                 class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.04] motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                            <!-- Scrim in the themeable ground colour so the badge stays legible on any photo. -->
                            <span class="absolute inset-0 bg-gradient-to-t from-brand-black/80 via-brand-black/15 to-transparent" aria-hidden="true"></span>
                            <?php if ($icon !== null): ?>
                                <span class="absolute bottom-4 left-4 inline-flex h-11 w-11 items-center justify-center rounded-xl border border-white/15 bg-brand-black/60 text-brand-red backdrop-blur" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="<?= esc($icon, 'attr') ?>"/></svg>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($icon !== null): ?>
                        <!-- No photo: a brand-tinted panel at the same 4:3 ratio keeps the
                             row aligned and reads as deliberate, not as a failed image. -->
                        <div class="flex aspect-[4/3] items-center justify-center border-b border-white/10 bg-brand-red/[0.08]" aria-hidden="true">
                            <svg class="h-12 w-12 text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="<?= esc($icon, 'attr') ?>"/></svg>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-1 flex-col p-7">
                        <h3 class="text-lg font-semibold transition-colors group-hover:text-brand-red"><?= esc($title) ?></h3>
                        <?php if (! empty($item['text'])): ?>
                            <p class="mt-3 text-sm leading-relaxed text-white/60"><?= esc(t_field($item['text'])) ?></p>
                        <?php endif; ?>
                        <?php if ($href !== null): ?>
                            <a href="<?= esc($href, 'attr') ?>"
                               class="mt-auto inline-flex items-center gap-2 self-start rounded-sm pt-5 text-xs font-semibold uppercase tracking-widest text-brand-red focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                <?php if ($cta !== ''): ?><?= esc($cta) ?><?php endif; ?>
                                <!-- Repeated CTA wording on a grid is ambiguous out of context — name the service too. -->
                                <span class="sr-only"><?= esc($title) ?></span>
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                <!-- Stretched link: covers the card without splitting it into a second tab stop. -->
                                <span class="absolute inset-0" aria-hidden="true"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
