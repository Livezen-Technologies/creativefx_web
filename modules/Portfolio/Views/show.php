<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$pTitle   = t_field($project['title'] ?? '');
$pExcerpt = t_field($project['excerpt'] ?? '');
$catName  = $category !== null ? t_field($category['name'] ?? '') : '';

// Locale-mapped prose: editor HTML renders as-is (sanitised by rich_text),
// plain text keeps its line breaks.
$prose = static fn (?string $raw): string => rich_text(t_field((string) ($raw ?? '')));

$cover = ! empty($project['cover_image']) && is_file(FCPATH . ltrim((string) $project['cover_image'], '/'))
    ? (string) $project['cover_image'] : null;

// Hero film. A site-root-absolute path plays inline; a YouTube/Vimeo link is
// converted to its embed form. Anything else is ignored so the cover still runs.
$video      = trim((string) ($project['video_url'] ?? ''));
$videoFile  = ($video !== '' && $video[0] === '/' && is_file(FCPATH . ltrim($video, '/'))) ? $video : null;
$videoEmbed = null;
if ($videoFile === null && $video !== '') {
    if (preg_match('#^https?://(?:www\.)?youtu\.be/([\w-]{6,})#i', $video, $m)
        || preg_match('#^https?://(?:www\.)?youtube\.com/watch\?(?:.*&)?v=([\w-]{6,})#i', $video, $m)) {
        $videoEmbed = 'https://www.youtube-nocookie.com/embed/' . $m[1];
    } elseif (preg_match('#^https?://(?:www\.)?vimeo\.com/(\d{6,})#i', $video, $m)) {
        $videoEmbed = 'https://player.vimeo.com/video/' . $m[1];
    } elseif (preg_match('#^https://(?:www\.youtube-nocookie\.com/embed/|www\.youtube\.com/embed/|player\.vimeo\.com/video/)#i', $video)) {
        $videoEmbed = $video;
    }
}

// Project date: the exact day when we have it, the year otherwise.
$dateLabel = ! empty($project['project_date'])
    ? date('j F Y', strtotime((string) $project['project_date']))
    : (string) ($project['year'] ?? '');

$facts = array_filter([
    'Client'   => (string) ($project['client'] ?? ''),
    'Industry' => (string) ($project['industry'] ?? ''),
    'Service'  => (string) ($project['service'] ?? ''),
    'Date'     => $dateLabel,
], static fn (string $v): bool => trim($v) !== '');

// Gallery: [{src, caption locale-map}, …]. Frames whose file is missing are
// dropped rather than rendered broken.
$gallery = [];
foreach (json_decode((string) ($project['gallery'] ?? '[]'), true) ?: [] as $item) {
    $src = is_array($item) ? (string) ($item['src'] ?? '') : (string) $item;
    if ($src === '' || ! is_file(FCPATH . ltrim($src, '/'))) {
        continue;
    }
    $gallery[] = ['src' => $src, 'caption' => is_array($item) ? t_field($item['caption'] ?? '') : ''];
}

$description = $prose($project['description'] ?? null);
$challenge   = $prose($project['challenge'] ?? null);
$approach    = $prose($project['approach'] ?? null);
$results     = $prose($project['results'] ?? null);
?>
<?= $this->section('content') ?>

<article>
    <!-- Project hero -->
    <section class="relative overflow-hidden">
        <div class="hero-aurora absolute inset-0 -z-20"></div>
        <div class="container-x flex min-h-[32vh] flex-col justify-end pb-10 pt-36">
            <a href="<?= esc(locale_url('portfolio'), 'attr') ?>" class="mb-5 text-xs font-semibold uppercase tracking-widest text-brand-red hover:underline">← Back to portfolio</a>
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-white/50" data-gsap="reveal">
                <?php if ($catName !== ''): ?>
                    <a href="<?= esc(locale_url('portfolio') . '?category=' . ($category['slug'] ?? ''), 'attr') ?>" class="text-brand-red hover:underline"><?= esc($catName) ?></a>
                <?php endif; ?>
                <?php if (! empty($project['year'])): ?>
                    <?php if ($catName !== ''): ?><span aria-hidden="true">·</span><?php endif; ?>
                    <span><?= esc($project['year']) ?></span>
                <?php endif; ?>
            </div>
            <h1 class="mt-4 max-w-4xl text-3xl font-bold leading-[1.1] sm:text-5xl" data-gsap="reveal"><?= esc($pTitle) ?></h1>
            <?php if ($pExcerpt !== ''): ?>
                <p class="mt-5 max-w-2xl text-lg leading-relaxed text-white/70" data-gsap="reveal"><?= esc($pExcerpt) ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="bg-brand-black pb-20">
        <div class="container-x">
            <!-- Hero media: the film when there is one, the cover frame otherwise -->
            <?php if ($videoFile !== null): ?>
                <figure class="isolate overflow-hidden rounded-3xl border border-white/10" data-gsap="reveal">
                    <video class="w-full" controls playsinline preload="metadata"
                           <?= $cover !== null ? 'poster="' . esc($cover, 'attr') . '"' : '' ?>>
                        <source src="<?= esc($videoFile, 'attr') ?>" type="video/mp4">
                    </video>
                </figure>
            <?php elseif ($videoEmbed !== null): ?>
                <figure class="isolate aspect-video overflow-hidden rounded-3xl border border-white/10" data-gsap="reveal">
                    <iframe src="<?= esc($videoEmbed, 'attr') ?>" title="<?= esc($pTitle, 'attr') ?>" class="h-full w-full"
                            loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                </figure>
            <?php elseif ($cover !== null): ?>
                <figure class="isolate overflow-hidden rounded-3xl border border-white/10" data-gsap="reveal">
                    <img src="<?= esc($cover, 'attr') ?>" alt="<?= esc($pTitle, 'attr') ?>" class="w-full object-cover">
                </figure>
            <?php endif; ?>

            <div class="mt-12 grid gap-12 lg:grid-cols-12">
                <!-- Fact sheet -->
                <?php if ($facts !== []): ?>
                    <div class="lg:col-span-4" data-gsap="reveal">
                        <dl class="divide-y divide-white/[0.07] rounded-2xl border border-white/10 bg-white/[0.02]">
                            <?php foreach ($facts as $label => $value): ?>
                                <div class="px-6 py-4">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-white/45"><?= esc($label) ?></dt>
                                    <dd class="mt-1 text-white/80"><?= esc($value) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endif; ?>

                <!-- Story -->
                <div class="<?= $facts !== [] ? 'lg:col-span-8' : 'lg:col-span-12' ?>">
                    <?php if ($description !== ''): ?>
                        <div class="text-[1.05rem] leading-relaxed text-white/70" data-gsap="reveal"><?= $description ?></div>
                    <?php endif; ?>

                    <?php if ($challenge !== '' || $approach !== ''): ?>
                        <div class="mt-10 grid gap-6 sm:grid-cols-2">
                            <?php if ($challenge !== ''): ?>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-7 transition hover:border-brand-red/60" data-gsap="reveal">
                                    <h2 class="text-lg font-bold">The challenge</h2>
                                    <div class="mt-3 leading-relaxed text-white/65"><?= $challenge ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($approach !== ''): ?>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-7 transition hover:border-brand-red/60" data-gsap="reveal">
                                    <h2 class="text-lg font-bold">Creative approach</h2>
                                    <div class="mt-3 leading-relaxed text-white/65"><?= $approach ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gallery -->
            <?php if ($gallery !== []): ?>
                <div class="mt-16">
                    <h2 class="mb-3 text-3xl font-bold sm:text-4xl" data-gsap="reveal">From the shoot</h2>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-gsap="reveal">
                        <?php foreach ($gallery as $frame): ?>
                            <figure class="isolate overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02]">
                                <img src="<?= esc($frame['src'], 'attr') ?>" alt="<?= esc($frame['caption'] !== '' ? $frame['caption'] : $pTitle, 'attr') ?>"
                                     loading="lazy" class="aspect-[4/3] w-full object-cover">
                                <?php if ($frame['caption'] !== ''): ?>
                                    <figcaption class="px-5 py-3 text-sm text-white/55"><?= esc($frame['caption']) ?></figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Results -->
            <?php if ($results !== ''): ?>
                <div class="mt-16 rounded-2xl border border-brand-red/40 bg-brand-red/[0.06] p-8 sm:p-10" data-gsap="reveal">
                    <p class="eyebrow">Results</p>
                    <div class="mt-5 max-w-3xl text-[1.05rem] leading-relaxed text-white/75"><?= $results ?></div>
                </div>
            <?php endif; ?>

            <!-- Related work -->
            <?php if ($related !== []): ?>
                <div class="mt-16 border-t border-white/10 pt-10">
                    <h2 class="text-xl font-bold">More work like this</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-gsap="reveal">
                        <?php foreach ($related as $r):
                            $rTitle = t_field($r['title'] ?? '');
                            $rCover = ! empty($r['cover_image']) && is_file(FCPATH . ltrim((string) $r['cover_image'], '/'))
                                ? (string) $r['cover_image'] : null; ?>
                            <a href="<?= esc(locale_url('portfolio/' . $r['slug']), 'attr') ?>"
                               class="group isolate flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/60">
                                <?php if ($rCover !== null): ?>
                                    <span class="block overflow-hidden">
                                        <img src="<?= esc($rCover, 'attr') ?>" alt="" loading="lazy"
                                             class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.04] motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                                    </span>
                                <?php endif; ?>
                                <span class="flex flex-1 flex-col p-6">
                                    <?php if (! empty($r['client'])): ?>
                                        <span class="text-[11px] font-semibold uppercase tracking-widest text-brand-red"><?= esc($r['client']) ?></span>
                                    <?php endif; ?>
                                    <span class="mt-2 text-base font-bold leading-snug transition group-hover:text-brand-red"><?= esc($rTitle) ?></span>
                                    <?php if (! empty($r['year'])): ?>
                                        <span class="mt-1 text-sm text-white/50"><?= esc($r['year']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Brief CTA -->
            <div class="mt-16 flex flex-col items-start gap-4 rounded-2xl border border-white/10 bg-white/[0.02] p-7 sm:flex-row sm:items-center sm:justify-between" data-gsap="reveal">
                <div>
                    <h2 class="text-xl font-bold">Planning something similar?</h2>
                    <p class="mt-1 text-white/60">Send us the brief and the dates. We will scope the crew, the kit and the budget in LKR.</p>
                </div>
                <a href="<?= esc(locale_url('quote'), 'attr') ?>" class="btn-brand flex-none">Request a quote</a>
            </div>
        </div>
    </section>
</article>

<?= $this->endSection() ?>
