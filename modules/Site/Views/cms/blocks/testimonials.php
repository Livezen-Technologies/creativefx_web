<?php helper('norlanka'); if (empty($content['items']) || ! is_array($content['items'])) { return; }

// Resolve every testimonial up front: a quote is the one thing a testimonial
// cannot do without, so anything else is dropped here — that way the slide
// count, the dots and the "n of m" labels can never disagree with the screen.
$slides = [];
foreach ($content['items'] as $item) {
    if (! is_array($item)) { continue; }

    $quote = trim(t_field($item['quote'] ?? []));
    if ($quote === '') { continue; }

    $name = trim(t_field($item['name'] ?? []));

    $slides[] = [
        'quote'  => $quote,
        'name'   => $name,
        // Role and company read as one line of attribution; either may be absent.
        'meta'   => implode(', ', array_filter([
            trim(t_field($item['role'] ?? [])),
            trim(t_field($item['company'] ?? [])),
        ])),
        'photo'  => ! empty($item['photo']) && is_file(FCPATH . ltrim((string) $item['photo'], '/')) ? $item['photo'] : null,
        // Clamped rather than trusted: 0 (or a missing key) simply hides the stars.
        'rating'  => max(0, min(5, (int) ($item['rating'] ?? 0))),
        'initial' => $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '',
    ];
}
if ($slides === []) { return; }

$count = count($slides);
$multi = $count > 1;

// The carousel needs an accessible name of its own; the section heading is the
// natural one, with a plain fallback for a block authored without a title.
$label = trim(t_field($content['title'] ?? []));
if ($label === '') { $label = 'Client testimonials'; }
?>
<!-- Client testimonials. One large quote at a time: a testimonial is read, not
     scanned, and a single wide card gives the quote the room it needs at every
     breakpoint. Auto-advance is a courtesy — it never runs under reduced
     motion, and hover or focus stops it for as long as the visitor is there. -->
<section class="relative overflow-hidden bg-brand-black py-20"
         x-data="{
            i: 0,
            n: <?= $count ?>,
            timer: null,
            paused: false,
            go(k) { this.i = (k % this.n + this.n) % this.n; this.restart(); },
            next() { this.go(this.i + 1); },
            prev() { this.go(this.i - 1); },
            play() {
                if (this.paused || this.n < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
                this.timer = setInterval(() => this.next(), 7000);
            },
            stop() { clearInterval(this.timer); this.timer = null; },
            restart() { this.stop(); this.play(); },
            hold() { this.paused = true; this.stop(); },
            release() { this.paused = false; this.play(); }
         }"
         x-init="play()"
         @mouseenter="hold()" @mouseleave="release()" @focusin="hold()" @focusout="release()">
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

        <div class="relative mt-10 rounded-3xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red"
             role="group" aria-label="<?= esc($label, 'attr') ?>"
             <?php if ($multi): ?>
                 aria-roledescription="carousel" tabindex="0"
                 @keydown.arrow-left.prevent="prev()" @keydown.arrow-right.prevent="next()"
             <?php endif; ?>
             data-gsap="reveal">

            <!-- Decorative quote mark, deliberately first in the DOM so the card
                 (which is only ~2% opaque) paints over it and lets it wash through. -->
            <svg class="pointer-events-none absolute -top-5 left-3 h-24 w-24 text-brand-red/10 sm:-top-9 sm:left-6 sm:h-36 sm:w-36"
                 viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                <path d="M13.2 5.6C7.6 8.1 4 13 4 19.1 4 23.4 6.8 26 10.4 26c3.2 0 5.6-2.4 5.6-5.4 0-3.1-2.2-5.3-5.1-5.3-.6 0-1.3.1-1.6.2.7-2.6 3.1-5.5 6.1-7l-2.2-2.9Zm15.2 0C22.8 8.1 19.2 13 19.2 19.1c0 4.3 2.8 6.9 6.4 6.9 3.2 0 5.6-2.4 5.6-5.4 0-3.1-2.2-5.3-5.1-5.3-.6 0-1.3.1-1.6.2.7-2.6 3.1-5.5 6.1-7l-2.2-2.9Z"/>
            </svg>

            <!-- A floor under the slide viewport: only one slide is in the flow at a
                 time, so without it a short quote following a long one would shunt
                 the rest of the page upwards on every advance.
                 aria-live goes quiet while the carousel rotates on its own —
                 an announcement every 7s would talk over the reader — and back
                 to polite the moment hover, focus or a control stops it. -->
            <div class="relative min-h-[20rem] sm:min-h-[16rem]"
                 aria-live="polite" <?= $multi ? ':aria-live="timer ? \'off\' : \'polite\'"' : '' ?>>
                <!-- Slide one carries no x-cloak: it paints server-side, so the block still
                     shows a testimonial before (and without) Alpine, while the rest stay
                     cloaked until Alpine owns their visibility. -->
                <?php foreach ($slides as $k => $slide): ?>
                    <figure class="rounded-2xl border border-white/10 bg-white/[0.02] p-7 transition hover:border-brand-red/60 sm:p-10"
                            x-show="i === <?= $k ?>" <?= $k > 0 ? 'x-cloak' : '' ?>
                            <?php /* Enter-only, and opacity-only: a leave transition would keep the
                                    outgoing slide in the flow and double the block's height mid-swap,
                                    and a fade (unlike movement) stays kind under reduced motion. */ ?>
                            x-transition:enter="transition-opacity duration-500 ease-out"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            role="group" aria-roledescription="slide"
                            aria-label="<?= $k + 1 ?> of <?= $count ?>">

                        <?php if ($slide['rating'] > 0): ?>
                            <span class="flex items-center gap-1" role="img" aria-label="Rated <?= $slide['rating'] ?> out of 5">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <svg class="h-4 w-4 <?= $s <= $slide['rating'] ? 'text-brand-red' : 'text-white/15' ?>"
                                         viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="m12 2.6 2.9 5.9 6.5.9-4.7 4.6 1.1 6.4-5.8-3-5.8 3 1.1-6.4L2.6 9.4l6.5-.9z"/>
                                    </svg>
                                <?php endfor; ?>
                            </span>
                        <?php endif; ?>

                        <blockquote class="<?= $slide['rating'] > 0 ? 'mt-6' : '' ?> max-w-3xl text-lg leading-relaxed text-white/80 sm:text-xl sm:leading-relaxed">
                            &ldquo;<?= esc($slide['quote']) ?>&rdquo;
                        </blockquote>

                        <?php // An anonymous testimonial gets no empty attribution strip. ?>
                        <?php if ($slide['name'] !== '' || $slide['meta'] !== '' || $slide['photo'] !== null): ?>
                            <figcaption class="mt-8 flex items-center gap-4">
                                <?php if ($slide['photo'] !== null): ?>
                                    <!-- Decorative: the name sits right beside it, so alt would only repeat it. -->
                                    <img src="<?= esc($slide['photo'], 'attr') ?>" alt="" loading="lazy"
                                         class="h-14 w-14 flex-none rounded-full border border-white/10 object-cover object-top">
                                <?php elseif ($slide['initial'] !== ''): ?>
                                    <!-- No portrait on file: a monogram disc, so the row never collapses. -->
                                    <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand-red/20 text-lg font-bold text-brand-red" aria-hidden="true">
                                        <?= esc($slide['initial']) ?>
                                    </span>
                                <?php endif; ?>
                                <span>
                                    <?php if ($slide['name'] !== ''): ?>
                                        <span class="block font-semibold leading-snug"><?= esc($slide['name']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($slide['meta'] !== ''): ?>
                                        <span class="mt-1 block text-xs uppercase tracking-widest text-white/50"><?= esc($slide['meta']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>

            <?php if ($multi): ?>
                <!-- Controls live outside the live region: their labels change with
                     the slide and would otherwise be announced twice. -->
                <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-1">
                        <?php for ($k = 0; $k < $count; $k++): ?>
                            <button type="button" class="group rounded-full p-1.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red"
                                    @click="go(<?= $k ?>)"
                                    :aria-current="i === <?= $k ?> ? 'true' : 'false'">
                                <span class="block h-1.5 rounded-full transition-all duration-300"
                                      :class="i === <?= $k ?> ? 'w-7 bg-brand-red' : 'w-1.5 bg-white/25 group-hover:bg-white/50'"></span>
                                <span class="sr-only">Show testimonial <?= $k + 1 ?> of <?= $count ?></span>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="prev()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-white/70 transition hover:border-brand-red/60 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18 9 12l6-6"/></svg>
                            <span class="sr-only">Previous testimonial</span>
                        </button>
                        <button type="button" @click="next()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-white/70 transition hover:border-brand-red/60 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            <span class="sr-only">Next testimonial</span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
