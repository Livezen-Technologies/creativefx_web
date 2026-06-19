<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

// --- Hero content (from the seeded CMS 'hero' block, with sensible fallbacks) ---
$heroRaw      = $sections['hero']['blocks'][0]['content'] ?? null;
$hero         = $heroRaw ? json_decode($heroRaw, true) : [];
$heroHeadline = $hero['headline'] ?? ['en' => "We craft the world's apparel, responsibly."];
$heroSubhead  = $hero['subhead']  ?? ['en' => 'Design. Innovation. Responsible sourcing — at global scale.'];

// Kinetic-typography headline parts (fall back to the plain headline split into words).
$kPre      = t_field($hero['pre'] ?? []);
$kPost     = t_field($hero['post'] ?? []);
$kRotators = array_values(array_filter(array_map(static fn ($r) => t_field($r), $hero['rotators'] ?? [])));
if ($kPre === '' && $kPost === '' && $kRotators === []) {
    $kPre = t_field($heroHeadline); // no kinetic data seeded → animate the whole headline
}
$splitWords = static function (string $text): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $html = '';
    foreach (preg_split('/\s+/u', $text) as $w) {
        $html .= '<span class="kw"><span class="kw-i">' . esc($w) . '</span></span> ';
    }
    return $html;
};

// Capabilities ("What we do") — Hirdaramani-style services grid.
$capabilities = [
    [
        'title' => 'Apparel Manufacturing',
        'text'  => 'High-volume, full-package production of knit and woven garments for the world’s leading brands.',
        'icon'  => 'M6 3l-2 4 3 2v12h10V9l3-2-2-4-3 2a4 4 0 01-6 0L6 3z',
    ],
    [
        'title' => 'Washing & Finishing',
        'text'  => 'Vertically integrated, water-conscious washing, dyeing and finishing under one roof.',
        'icon'  => 'M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z',
    ],
    [
        'title' => 'Printing & Embroidery',
        'text'  => 'In-house printing, embroidery and embellishment that bring every design to life.',
        'icon'  => 'M4 20h16M5 16l9-9 3 3-9 9H5v-3zM14 7l3-3 3 3-3 3',
    ],
    [
        'title' => 'Design & Development',
        'text'  => 'A dedicated R&D and design studio turning concepts into shelf-ready collections, fast.',
        'icon'  => 'M12 20h9M3 20l2-6 11-11 4 4L9 18l-6 2zM14 6l4 4',
    ],
];

// Sustainability pillars — the heart of a responsible-manufacturing story.
$impact = [
    ['title' => 'Net-zero ambition', 'text' => 'Driving renewable energy and efficiency toward carbon-neutral operations.'],
    ['title' => 'Ethical workplaces', 'text' => 'Safe, fair and empowering livelihoods for every person on our floor.'],
    ['title' => 'Circular materials',  'text' => 'Lower-impact fibres, less water and waste designed out at the source.'],
];

// Global footprint regions.
$regions = ['Sri Lanka', 'South Asia', 'South-East Asia', 'Europe', 'North America', 'Global brands'];
?>

<?= $this->section('content') ?>

<!-- ===================== HERO ===================== -->
<section
    id="hero"
    data-gsap="hero-out"
    class="relative flex min-h-screen items-center overflow-hidden"
>
    <!-- Background: real launch film if set in the CMS, else the animated brand visual -->
    <?php if (! empty($video['src_path'])): ?>
        <video class="absolute inset-0 -z-30 h-full w-full object-cover"
               autoplay muted loop playsinline preload="auto"
               poster="<?= esc($video['poster_path'] ?? '') ?>">
            <source src="<?= esc($video['src_path']) ?>" type="video/mp4">
            <?php if (! empty($video['src_path_webm'])): ?>
                <source src="<?= esc($video['src_path_webm']) ?>" type="video/webm">
            <?php endif; ?>
        </video>
    <?php else: ?>
        <div class="hero-aurora absolute inset-0 -z-30"></div>
        <div data-three-hero class="absolute inset-0 -z-20 opacity-70"></div>
    <?php endif; ?>
    <!-- Legibility scrim over whichever background -->
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-black/60 via-black/35 to-brand-black"></div>

    <div class="container-x relative w-full pt-28">
        <p class="mb-5 text-xs font-semibold uppercase tracking-[0.35em] text-brand-red" data-gsap="reveal">
            Responsible Apparel Manufacturing
        </p>
        <h1 class="kinetic-hero max-w-5xl text-4xl font-bold leading-[1.04] sm:text-6xl lg:text-7xl" aria-label="<?= esc(t_field($heroHeadline), 'attr') ?>">
            <?php if ($kPre !== ''): ?><span class="kline" data-kinetic><?= $splitWords($kPre) ?></span><?php endif; ?>
            <?php if ($kRotators !== []): ?>
                <span class="rotator-wrap text-brand-red" data-rotator aria-hidden="true">
                    <span class="rotator-list">
                        <?php foreach ($kRotators as $rw): ?><span class="rotator-word"><?= esc($rw) ?></span><?php endforeach; ?>
                        <span class="rotator-word"><?= esc($kRotators[0]) ?></span>
                    </span>
                </span>
            <?php endif; ?>
            <?php if ($kPost !== ''): ?><span class="kline" data-kinetic><?= $splitWords($kPost) ?></span><?php endif; ?>
        </h1>
        <p class="mt-6 max-w-2xl text-lg text-white/70" data-gsap="reveal"><?= esc(t_field($heroSubhead)) ?></p>

        <div class="mt-10 flex flex-wrap items-center gap-4" data-gsap="reveal">
            <a href="<?= esc(locale_url('our-expertise')) ?>" class="btn-brand">Explore our expertise</a>
            <a href="<?= esc(locale_url('showroom')) ?>" class="btn-ghost">Enter the showroom</a>
        </div>

        <p class="mt-12 max-w-xl text-[11px] uppercase tracking-[0.3em] text-white/40" data-gsap="reveal">
            Trusted by the world’s leading brands &amp; retailers
        </p>
    </div>

    <div class="absolute inset-x-0 bottom-8 flex justify-center">
        <span class="animate-bounce text-[10px] uppercase tracking-[0.3em] text-white/40"><?= esc(lang('Site.experience.scroll')) ?></span>
    </div>
</section>

<!-- ===================== LEGACY / INTRO ===================== -->
<section class="bg-brand-black py-24 sm:py-28">
    <div class="container-x grid gap-12 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-7" data-gsap="reveal">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red">Who we are</p>
            <h2 class="mt-5 text-3xl font-bold leading-tight sm:text-5xl">
                A partner the world’s brands trust to make apparel the right way.
            </h2>
        </div>
        <div class="lg:col-span-5" data-gsap="reveal">
            <p class="text-lg leading-relaxed text-white/65">
                From responsible sourcing to design and innovation, Norlanka delivers full-package
                manufacturing at global scale — pairing craftsmanship with measurable accountability
                at every stage of the journey from fibre to finished garment.
            </p>
            <a href="<?= esc(locale_url('our-story')) ?>"
               class="mt-6 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-widest text-white transition hover:text-brand-red">
                Our story
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ===================== STATS (CMS-driven) ===================== -->
<?= $this->include('Modules\Site\Views\home\sections\stats', ['section' => $sections['stats'] ?? null]) ?>

<!-- ===================== CAPABILITIES / WHAT WE DO ===================== -->
<section class="bg-brand-black py-24 sm:py-28">
    <div class="container-x">
        <div class="max-w-2xl" data-gsap="reveal">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red">What we do</p>
            <h2 class="mt-5 text-3xl font-bold sm:text-5xl">End-to-end manufacturing, under one roof.</h2>
            <p class="mt-4 text-white/60">Vertically integrated capabilities that take a collection from first sketch to global shelf.</p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($capabilities as $cap): ?>
                <article class="group flex h-full flex-col rounded-2xl border border-white/10 bg-white/[0.02] p-8 transition hover:border-brand-red/60 hover:bg-white/[0.04]" data-gsap="reveal">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl border border-white/10 text-brand-red transition group-hover:border-brand-red/60">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="<?= esc($cap['icon'], 'attr') ?>"/></svg>
                    </span>
                    <h3 class="mt-6 text-xl font-semibold"><?= esc($cap['title']) ?></h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/60"><?= esc($cap['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== VALUES / PILLARS (CMS-driven) ===================== -->
<?= $this->include('Modules\Site\Views\home\sections\pillars', ['section' => $sections['pillars'] ?? null]) ?>

<!-- ===================== SUSTAINABILITY / OUR IMPACT ===================== -->
<section class="relative overflow-hidden border-y border-white/10 py-24 sm:py-28">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-50"></div>
    <div class="absolute inset-0 -z-10 bg-black/55"></div>
    <div class="container-x grid gap-14 lg:grid-cols-2 lg:items-center">
        <div data-gsap="reveal">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red">Our impact</p>
            <h2 class="mt-5 text-3xl font-bold leading-tight sm:text-5xl">Sustainability, woven into every stitch.</h2>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/70">
                Responsible manufacturing isn’t a programme — it’s how we operate. We measure our
                footprint, invest in our people and design waste out of the process, so the apparel
                we make is something everyone can stand behind.
            </p>
            <a href="<?= esc(locale_url('impact')) ?>" class="btn-brand mt-8">Explore our impact</a>
        </div>

        <div class="grid gap-4" data-gsap="reveal">
            <?php foreach ($impact as $i => $point): ?>
                <div class="flex items-start gap-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6 backdrop-blur">
                    <span class="text-2xl font-bold text-brand-red">0<?= $i + 1 ?></span>
                    <div>
                        <h3 class="text-lg font-semibold"><?= esc($point['title']) ?></h3>
                        <p class="mt-1 text-sm leading-relaxed text-white/60"><?= esc($point['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== GLOBAL FOOTPRINT ===================== -->
<section class="bg-brand-black py-24 sm:py-28">
    <div class="container-x">
        <div class="grid gap-12 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-5" data-gsap="reveal">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red">Global footprint</p>
                <h2 class="mt-5 text-3xl font-bold sm:text-5xl">Made in Sri Lanka. Delivered to the world.</h2>
                <p class="mt-5 text-lg leading-relaxed text-white/65">
                    From our manufacturing heartland we serve leading brands across every major market —
                    combining local craftsmanship with the reliability of a global supply partner.
                </p>
            </div>
            <div class="lg:col-span-7" data-gsap="reveal">
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($regions as $r): ?>
                        <span class="rounded-full border border-white/15 px-5 py-2.5 text-sm font-medium uppercase tracking-widest text-white/70 transition hover:border-brand-red/60 hover:text-white"><?= esc($r) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== VIRTUAL SHOWROOM (CMS-driven CTA) ===================== -->
<?= $this->include('Modules\Site\Views\home\sections\cta', ['section' => $sections['cta'] ?? null]) ?>

<!-- ===================== PARTNER / CLOSING CTA ===================== -->
<section class="bg-brand-black pb-28">
    <div class="container-x">
        <div class="overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#1a0205] via-brand-black to-brand-black p-10 sm:p-16" data-gsap="reveal">
            <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold leading-tight sm:text-4xl">Let’s build your next collection together.</h2>
                    <p class="mt-4 max-w-lg text-white/65">Partner with a manufacturer that delivers quality, scale and responsibility — or join a team shaping the future of apparel.</p>
                </div>
                <div class="flex flex-wrap gap-4 lg:justify-end">
                    <a href="<?= esc(locale_url('contact')) ?>" class="btn-brand">Contact us</a>
                    <a href="<?= esc(locale_url('careers')) ?>" class="btn-ghost">View careers</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
