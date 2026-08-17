<?php helper(['norlanka', 'url']);
// Dynamic block: renders the rental catalogue from the Gear module rather than
// payload items, so rates and availability edited in Admin are live on every
// page that carries the block. Payload: eyebrow, title, intro (locale maps),
// category (a gear_categories slug, optional filter) and limit (default 9).
$gearFilter = trim((string) ($content['category'] ?? ''));
$gearItems  = [];

try {
    $gearCategoryId = null;
    if ($gearFilter !== '') {
        $gearCategory   = model('Modules\Gear\Models\GearCategoryModel')->findPublishedBySlug($gearFilter);
        $gearCategoryId = $gearCategory === null ? null : (int) $gearCategory['id'];
    }
    // A payload filtering on a category that has since been unpublished shows
    // nothing, rather than silently falling back to the whole catalogue.
    if ($gearFilter === '' || $gearCategoryId !== null) {
        $gearItems = model('Modules\Gear\Models\GearItemModel')
            ->published($gearCategoryId)
            ->findAll(max(1, (int) ($content['limit'] ?? 9)));
    }
} catch (\Throwable $e) {
    $gearItems = [];
}

if ($gearItems === []) {
    return;
}

// Availability is a status, not a decoration: the dot and the border carry the
// colour, the label carries the meaning, so the state survives a greyscale
// print. The pill sits on the artwork, so its own ground is the themeable one
// (bg-brand-black) — a tint would leave the label as ink on a dark photo in the
// light theme.
$gearPills = [
    'available'   => ['label' => 'Available', 'dot' => 'bg-emerald-500', 'ring' => 'border-emerald-500/60'],
    'booked'      => ['label' => 'Booked', 'dot' => 'bg-amber-500', 'ring' => 'border-amber-500/60'],
    'maintenance' => ['label' => 'Maintenance', 'dot' => 'bg-white/40', 'ring' => 'border-white/25'],
];

// "LKR 12,500". Whole rupees are the norm, so cents only appear on a rate that
// actually carries them.
$gearRate = static function ($amount, string $currency): string {
    $value = (float) $amount;
    if ($value <= 0) {
        return '';
    }

    return trim($currency . ' ' . number_format($value, fmod($value, 1) === 0.0 ? 0 : 2));
};

// Resolve every card once — decoding specs and testing image paths inside the
// render loop would mix data work into the markup (same prepass idiom as
// packages.php).
$gearQuoteUrl = locale_url('quote');
$gearCards    = [];

foreach ($gearItems as $gearItem) {
    $gearName = trim(t_field($gearItem['name'] ?? []));
    if ($gearName === '') {
        continue; // Unnamed kit has nothing to rent.
    }

    // Three specs is what fits before the cards start growing uneven; the rest
    // belong on the quote conversation.
    $gearSpecs = [];
    foreach ((array) (json_decode((string) ($gearItem['specs'] ?? ''), true) ?: []) as $gearSpec) {
        $gearSpecLabel = trim(t_field($gearSpec['label'] ?? []));
        $gearSpecValue = trim((string) ($gearSpec['value'] ?? ''));
        if ($gearSpecLabel === '' || $gearSpecValue === '') {
            continue;
        }
        $gearSpecs[] = [$gearSpecLabel, $gearSpecValue];
        if (count($gearSpecs) === 3) {
            break;
        }
    }

    $gearImage    = (string) ($gearItem['image'] ?? '');
    $gearCurrency = trim((string) ($gearItem['currency'] ?? '')) ?: 'LKR';

    $gearCards[] = [
        'name'    => $gearName,
        'summary' => trim(t_field($gearItem['summary'] ?? [])),
        'specs'   => $gearSpecs,
        // Missing artwork degrades to the tinted panel below, never a broken image.
        'image'  => $gearImage !== '' && is_file(FCPATH . ltrim($gearImage, '/')) ? $gearImage : null,
        'daily'  => $gearRate($gearItem['rate_daily'] ?? null, $gearCurrency),
        'weekly' => $gearRate($gearItem['rate_weekly'] ?? null, $gearCurrency),
        'pill'   => $gearPills[$gearItem['availability'] ?? ''] ?? $gearPills['available'],
        // The quote form pre-selects the service and the item from the query
        // string, so a rental enquiry arrives already knowing what it is about.
        'href' => $gearQuoteUrl . '?service=gear-renting&item=' . rawurlencode((string) ($gearItem['slug'] ?? '')),
    ];
}

if ($gearCards === []) {
    return;
}
?>
<!-- Gear rental grid. Cards stretch to the tallest in the row (grid default) and
     each is a flex column, so the rate and the Rent Now button sit on the bottom
     edge however uneven the spec lists are. -->
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
            <?php foreach ($gearCards as $gearCard): ?>
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/60 focus-within:border-brand-red/60" data-gsap="reveal">
                    <div class="relative overflow-hidden">
                        <?php if ($gearCard['image'] !== null): ?>
                            <!-- Decorative: the heading below already names the kit. -->
                            <img src="<?= esc($gearCard['image'], 'attr') ?>" alt="" loading="lazy"
                                 class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.04] motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                        <?php else: ?>
                            <!-- No artwork yet: a brand-tinted panel at the same ratio keeps
                                 the row aligned and reads as deliberate, not as a failure. -->
                            <div class="flex aspect-[4/3] items-center justify-center border-b border-white/10 bg-brand-red/[0.08]" aria-hidden="true">
                                <svg class="h-12 w-12 text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-9-5-9 5v8l9 5 9-5V8Z"/><path d="M3.3 7.7 12 12.5l8.7-4.8M12 22V12.5"/></svg>
                            </div>
                        <?php endif; ?>
                        <!-- Pill classes are fixed strings from the table above, not
                             data — echoed raw so the class list stays readable (and
                             scannable by Tailwind), like $gridCols in packages.php. -->
                        <span class="absolute left-3 top-3 inline-flex items-center gap-2 whitespace-nowrap rounded-full border bg-brand-black/85 px-3 py-1 text-[10px] font-semibold uppercase tracking-widest text-white/80 backdrop-blur <?= $gearCard['pill']['ring'] ?>">
                            <span class="h-1.5 w-1.5 rounded-full <?= $gearCard['pill']['dot'] ?>" aria-hidden="true"></span>
                            <?= esc($gearCard['pill']['label']) ?>
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col p-7">
                        <h3 class="text-lg font-semibold transition-colors group-hover:text-brand-red"><?= esc($gearCard['name']) ?></h3>

                        <?php if ($gearCard['summary'] !== ''): ?>
                            <p class="mt-3 text-sm leading-relaxed text-white/60"><?= esc($gearCard['summary']) ?></p>
                        <?php endif; ?>

                        <?php if ($gearCard['specs'] !== []): ?>
                            <dl class="mt-5 space-y-2 text-xs leading-relaxed">
                                <?php foreach ($gearCard['specs'] as [$gearSpecLabel, $gearSpecValue]): ?>
                                    <div class="flex gap-x-3">
                                        <dt class="w-24 flex-none text-white/40"><?= esc($gearSpecLabel) ?></dt>
                                        <dd class="min-w-0 flex-1 text-white/70"><?= esc($gearSpecValue) ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>

                        <div class="mt-auto pt-6">
                            <?php if ($gearCard['daily'] !== ''): ?>
                                <!-- No rate, no row: kit that is only ever quoted on
                                     request goes straight to the button. -->
                                <p class="flex flex-wrap items-baseline gap-x-1.5">
                                    <span class="text-xl font-bold text-brand-red"><?= esc($gearCard['daily']) ?></span>
                                    <span class="text-xs text-white/50">/ day</span>
                                </p>
                                <?php if ($gearCard['weekly'] !== ''): ?>
                                    <p class="mt-1 text-xs text-white/40"><?= esc($gearCard['weekly']) ?> / week</p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <a href="<?= esc($gearCard['href'], 'attr') ?>" class="btn-brand mt-5 w-full !px-5 !py-2.5 text-xs">
                                Rent Now
                                <!-- "Rent Now" nine times over is ambiguous out of context — name the kit for screen readers. -->
                                <span class="sr-only"><?= esc($gearCard['name']) ?></span>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
