<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$hex = static fn ($v, $fallback = '#CF2030') => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $v) ? $v : $fallback;

$palette = json_decode($category['palette'] ?? '[]', true) ?: [$hex($category['background'])];
$features = json_decode($category['features'] ?? '[]', true) ?: [];
$accentHex = $hex($palette[0] ?? $category['background']);
$catName    = t_field($category['name']);
$themeName  = t_field($category['theme_name'] ?? []) ?: $category['theme'];
$tagline    = t_field($category['tagline'] ?? []);

$email    = setting('email', 'hello@norlanka.com', 'contact');
$whatsapp = preg_replace('/\D+/', '', (string) setting('whatsapp', '', 'contact'));

$products = [];
foreach ($category['products'] as $p) {
    $products[] = [
        'id'          => (int) $p['id'],
        'name'        => t_field($p['name']),
        'description' => rich_text(json_decode($p['description'] ?? '[]', true) ?: []),
        'gallery'     => json_decode($p['gallery'] ?? '[]', true) ?: [],
        'image'       => $p['image'] ?? '',
        'images'      => json_decode($p['images'] ?? '[]', true) ?: [],
        'model'       => $p['model_path'] ?? '',
        'materials'   => json_decode($p['materials'] ?? '[]', true) ?: [],
        'sizes'       => json_decode($p['sizes'] ?? '[]', true) ?: [],
        'fabric'      => $p['fabric'] ?? '',
        'moq'         => $p['moq'] ?? '',
        'collection'  => $p['collection'] ?? '',
        'hotspot'     => json_decode($p['hotspot'] ?? '{}', true) ?: (object) [],
    ];
}
$sceneData = [
    'accent'   => $accentHex,
    'theme'    => $category['theme'],
    'palette'  => array_map($hex, $palette),
    'contact'  => ['whatsapp' => $whatsapp, 'email' => $email],
    'products' => $products,
];

// Gentle palette wash behind the 3D stage.
$c0 = $accentHex;
$c1 = $hex($palette[1] ?? $c0);
?>
<?= $this->section('content') ?>
<div x-data="showroomScene()" class="on-dark">
    <!-- Themed header -->
    <section class="relative overflow-hidden border-b border-white/10 pt-28"
             style="background: radial-gradient(120% 80% at 15% 0%, <?= esc($c0, 'attr') ?>22, transparent 60%), radial-gradient(120% 80% at 90% 10%, <?= esc($c1, 'attr') ?>1f, transparent 55%), #050506;">
        <div class="container-x pb-8">
            <a href="<?= esc(locale_url('showroom')) ?>" class="text-xs uppercase tracking-widest text-white/50 hover:text-white">← <?= esc(lang('Site.showroom.all_categories')) ?></a>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.28em]" style="color: <?= esc($c0, 'attr') ?>"><?= esc($themeName) ?></span>
                    <h1 class="mt-1 text-3xl font-bold sm:text-5xl"><?= esc($catName) ?></h1>
                    <?php if ($tagline !== ''): ?>
                        <p class="mt-3 max-w-2xl text-white/65"><?= esc($tagline) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-white/60"><span class="text-brand-red">♥</span> <span x-text="$store.wishlist.count">0</span> <?= esc(lang('Site.showroom.saved')) ?></div>
            </div>

            <?php if ($features !== []): ?>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span class="text-[10px] uppercase tracking-[0.25em] text-white/35"><?= esc(lang('Site.showroom.environment')) ?>:</span>
                    <?php foreach ($features as $f): ?>
                        <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs text-white/70"><?= esc($f) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($materials !== []): ?>
                <div class="mt-5 flex flex-wrap gap-2">
                    <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-brand-red text-white' : 'bg-white/5 text-white/70'" class="rounded-full px-4 py-1.5 text-xs"><?= esc(lang('Site.showroom.all')) ?></button>
                    <?php foreach ($materials as $m): ?>
                        <button @click="setFilter(<?= esc(json_encode($m), 'attr') ?>)"
                                :class="filter === <?= esc(json_encode($m), 'attr') ?> ? 'bg-brand-red text-white' : 'bg-white/5 text-white/70 hover:text-white'"
                                class="rounded-full px-4 py-1.5 text-xs"><?= esc($m) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 3D stage -->
    <section class="relative">
        <div data-showroom class="h-[62vh] min-h-[420px] w-full bg-[#08080a]"></div>
        <script type="application/json" id="showroom-data"><?= json_encode($sceneData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
        <p class="pointer-events-none absolute bottom-4 left-0 right-0 text-center text-[11px] uppercase tracking-widest text-white/40"><?= esc(lang('Site.showroom.orbit_hint')) ?></p>
    </section>

    <!-- Product list -->
    <section class="bg-brand-black py-12">
        <div class="container-x">
            <h2 class="mb-6 text-sm font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.showroom.pieces')) ?></h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="p in products.filter((p) => inList(p))" :key="p.id">
                    <button @click="select(p.id)" class="group rounded-2xl border border-white/10 bg-white/[0.02] p-4 text-left transition hover:border-brand-red/60">
                        <!-- Product photo when available; colour swatches otherwise -->
                        <template x-if="p.image">
                            <div class="mb-3 aspect-[4/5] overflow-hidden rounded-xl bg-white/5">
                                <img :src="p.image" :alt="p.name" loading="lazy"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        </template>
                        <div class="flex gap-1.5" x-show="!p.image">
                            <template x-for="c in p.gallery" :key="c">
                                <span class="h-6 w-6 rounded-full ring-1 ring-white/15" :style="'background:' + c"></span>
                            </template>
                        </div>
                        <h3 class="mt-3 text-sm font-semibold" x-text="p.name"></h3>
                        <span class="mt-1 block text-[11px] uppercase tracking-widest text-white/40" x-text="p.collection"></span>
                        <span class="mt-2 block text-xs text-white/50 group-hover:text-white"><?= esc(lang('Site.showroom.view_details')) ?> →</span>
                    </button>
                </template>
            </div>
        </div>
    </section>

    <!-- Product panel -->
    <div x-show="panelOpen" x-cloak class="fixed inset-0 z-[60]" @keydown.window.escape="close()">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="close()"></div>
        <div class="nl-drawer absolute right-0 top-0 flex h-full w-full max-w-md flex-col overflow-y-auto border-l border-white/10 bg-brand-black p-8"
             x-show="panelOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <template x-if="current">
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] uppercase tracking-widest text-brand-red" x-text="current.collection"></span>
                            <h2 class="text-2xl font-bold" x-text="current.name"></h2>
                        </div>
                        <button @click="close()" class="text-white/50 hover:text-white">✕</button>
                    </div>
                    <template x-if="images.length">
                        <div class="mt-5">
                            <div class="aspect-[4/5] overflow-hidden rounded-2xl bg-white/5">
                                <img :src="shownImage" :alt="current.name" class="h-full w-full object-cover">
                            </div>
                            <!-- Strip only earns its space once there is a choice to make. -->
                            <div x-show="images.length > 1" class="nl-shots" role="tablist" :aria-label="current.name">
                                <template x-for="(shot, i) in images" :key="shot">
                                    <button type="button" role="tab"
                                            :aria-selected="activeImage === i ? 'true' : 'false'"
                                            :class="activeImage === i ? 'is-active' : ''"
                                            @click="activeImage = i"
                                            @keydown.arrow-right.prevent="activeImage = (i + 1) % images.length; $el.parentElement.querySelectorAll('button')[activeImage]?.focus()"
                                            @keydown.arrow-left.prevent="activeImage = (i - 1 + images.length) % images.length; $el.parentElement.querySelectorAll('button')[activeImage]?.focus()">
                                        <img :src="shot" alt="" loading="lazy">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div class="mt-3 text-sm leading-relaxed text-white/70" x-html="current.description"></div>
                </div>
            </template>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
