<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

// Category id => localized name / slug, for the card labels and the filter.
$catNames = [];
$catSlugs = [];
foreach ($categories as $c) {
    $catNames[(int) $c['id']] = t_field($c['name'] ?? '');
    $catSlugs[(int) $c['id']] = (string) $c['slug'];
}

// How many published projects sit in each category — an editor can empty one,
// and that category then needs an honest empty state rather than a blank grid.
$counts = array_fill_keys(array_values($catSlugs), 0);
foreach ($projects as $p) {
    $slug = $catSlugs[(int) ($p['category_id'] ?? 0)] ?? null;
    if ($slug !== null) {
        $counts[$slug]++;
    }
}

$activeSlug = (string) ($activeCategory['slug'] ?? 'all');
$hasHero    = ! empty($heroImage) && is_file(FCPATH . ltrim((string) $heroImage, '/'));

// Filter pills. 'all' clears the query string entirely.
$pillUrl = static fn (string $slug): string => locale_url('portfolio') . ($slug === 'all' ? '' : '?category=' . $slug);
$pills   = [['all', 'All work']];
foreach ($categories as $c) {
    $pills[] = [(string) $c['slug'], $catNames[(int) $c['id']]];
}
// The active pill is styled off aria-current, so the no-JS state (rendered by
// the server) and the Alpine state (bound below) share one source of truth.
$pillClass = 'rounded-full border border-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest '
    . 'text-white/60 transition hover:border-brand-red/50 hover:text-white '
    . 'aria-[current=page]:border-brand-red aria-[current=page]:bg-brand-red aria-[current=page]:text-white';

// Client, year and the "View project" cue ride in on hover at desktop widths;
// on touch, where there is no hover, they simply stay put.
$onHover = 'transition duration-300 md:translate-y-2 md:opacity-0 md:group-hover:translate-y-0 '
    . 'md:group-hover:opacity-100 md:group-focus-visible:translate-y-0 md:group-focus-visible:opacity-100 '
    . 'motion-reduce:transition-none motion-reduce:md:translate-y-0';
?>
<?= $this->section('content') ?>

<!-- Portfolio hero -->
<section class="relative overflow-hidden">
    <?php if ($hasHero): ?>
        <!-- Decorative: the headline below carries the meaning. -->
        <img src="<?= esc($heroImage, 'attr') ?>" alt="" class="absolute inset-0 -z-30 h-full w-full object-cover">
        <!-- Scrims: keep the copy legible over the artwork. -->
        <div class="absolute inset-0 -z-20 bg-gradient-to-r from-brand-black/95 via-brand-black/60 to-brand-black/25"></div>
        <div class="absolute inset-0 -z-20 bg-gradient-to-t from-brand-black via-brand-black/30 to-transparent"></div>
        <div class="hero-red-glow absolute inset-0 -z-10"></div>
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-20"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-black/40 via-brand-black/10 to-brand-black"></div>
    <?php endif; ?>

    <div class="container-x flex min-h-[52vh] flex-col justify-end pb-14 pt-40">
        <p class="eyebrow" data-gsap="reveal">Selected work</p>
        <h1 class="mt-5 max-w-4xl text-4xl font-bold leading-[1.05] sm:text-6xl" data-gsap="reveal">Portfolio</h1>
        <p class="mt-6 max-w-2xl text-lg text-white/70" data-gsap="reveal">
            Brand films, commercial photography, podcast seasons, live broadcasts and performance
            campaigns — produced in Colombo and on location across Sri Lanka. Every project below
            is real client work, with the brief, the build and what it moved.
        </p>
    </div>
</section>

<!-- Filter + grid. The pills are ordinary links, so ?category= filtering works
     with JavaScript off (cards outside the filter ship hidden). Alpine then
     takes over the same state and switches the grid without a reload, reading
     each filter URL back off the pill's own href ($el.href) rather than
     restating it. -->
<section class="bg-brand-black pb-20"
         x-data="{
             active: '<?= esc($activeSlug, 'attr') ?>',
             select(slug, url) {
                 this.active = slug;
                 try { window.history.replaceState(null, '', url); } catch (e) {}
             },
         }">
    <div class="container-x">
        <nav class="flex flex-wrap gap-2 border-b border-white/10 py-6" aria-label="Filter portfolio by discipline">
            <?php foreach ($pills as [$slug, $label]): ?>
                <a href="<?= esc($pillUrl($slug)) ?>" class="<?= $pillClass ?>"
                   @click.prevent="select('<?= esc($slug, 'attr') ?>', $el.href)"
                   :aria-current="active === '<?= esc($slug, 'attr') ?>' ? 'page' : false"
                   <?= $slug === $activeSlug ? 'aria-current="page"' : '' ?>><?= esc($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($projects === []): ?>
            <p class="py-24 text-center text-white/50">New work is being cut right now — the portfolio goes live shortly.</p>
        <?php else: ?>
            <!-- Masonry-ish: CSS columns let portrait covers stand taller than
                 landscape ones without cropping either. -->
            <div class="mt-10 columns-1 gap-6 sm:columns-2 lg:columns-3" data-gsap="reveal">
                <?php foreach ($projects as $p):
                    $pTitle = t_field($p['title'] ?? '');
                    if ($pTitle === '') {
                        continue; // an untitled project has nothing to show
                    }
                    $pSlug   = $catSlugs[(int) ($p['category_id'] ?? 0)] ?? null;
                    $pCat    = $catNames[(int) ($p['category_id'] ?? 0)] ?? '';
                    $cover   = ! empty($p['cover_image']) && is_file(FCPATH . ltrim((string) $p['cover_image'], '/'))
                        ? $p['cover_image'] : null;
                    $visible = $activeSlug === 'all' || $activeSlug === $pSlug;
                    $meta    = array_filter([$p['client'] ?? null, $p['year'] ?? null]); ?>
                    <a href="<?= esc(locale_url('portfolio/' . $p['slug'])) ?>"
                       class="group isolate relative mb-6 block break-inside-avoid overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/60 focus-visible:border-brand-red/60"
                       x-show="active === 'all' || active === '<?= esc((string) $pSlug, 'attr') ?>'"
                       <?= $visible ? '' : 'style="display: none"' ?>>
                        <?php if ($cover !== null): ?>
                            <img src="<?= esc($cover, 'attr') ?>" alt="" loading="lazy"
                                 class="w-full object-cover transition duration-700 group-hover:scale-[1.04] motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                        <?php else: ?>
                            <span class="block aspect-[4/3] w-full bg-gradient-to-br from-brand-red/25 via-brand-black to-brand-black"></span>
                        <?php endif; ?>

                        <span class="absolute inset-0 bg-gradient-to-t from-brand-black via-brand-black/45 to-transparent transition group-hover:from-brand-black group-hover:via-brand-black/60" aria-hidden="true"></span>

                        <span class="absolute inset-x-0 bottom-0 flex flex-col p-6">
                            <?php if ($pCat !== ''): ?>
                                <span class="text-[11px] font-semibold uppercase tracking-widest text-brand-red"><?= esc($pCat) ?></span>
                            <?php endif; ?>
                            <span class="mt-2 text-lg font-bold leading-snug"><?= esc($pTitle) ?></span>
                            <?php if ($meta !== []): ?>
                                <span class="mt-1 text-sm text-white/60 <?= $onHover ?>"><?= esc(implode(' · ', $meta)) ?></span>
                            <?php endif; ?>
                            <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-brand-red <?= $onHover ?>">
                                View project
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Empty categories: one line per category with nothing published,
                 shown server-side when it is the active filter and by Alpine
                 when the reader switches to it. -->
            <?php foreach ($categories as $c): if (($counts[(string) $c['slug']] ?? 0) > 0) { continue; } ?>
                <p class="py-20 text-center text-white/50"
                   x-show="active === '<?= esc((string) $c['slug'], 'attr') ?>'"
                   <?= $activeSlug === $c['slug'] ? '' : 'style="display: none"' ?>>
                    No <?= esc(strtolower($catNames[(int) $c['id']])) ?> projects are published yet — talk to us about being the first.
                </p>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Brief CTA -->
        <div class="mt-14 flex flex-col items-start gap-4 rounded-2xl border border-white/10 bg-white/[0.02] p-7 sm:flex-row sm:items-center sm:justify-between" data-gsap="reveal">
            <div>
                <h2 class="text-xl font-bold">Have a project in mind?</h2>
                <p class="mt-1 text-white/60">Tell us the brief, the deadline and the budget range — we will come back with a plan and a price.</p>
            </div>
            <a href="<?= esc(locale_url('quote')) ?>" class="btn-brand flex-none">Request a quote</a>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
