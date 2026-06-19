<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$products = [];
foreach ($category['products'] as $p) {
    $products[] = [
        'id'          => (int) $p['id'],
        'name'        => t_field($p['name']),
        'description' => t_field($p['description']),
        'gallery'     => json_decode($p['gallery'] ?? '[]', true) ?: [],
        'materials'   => json_decode($p['materials'] ?? '[]', true) ?: [],
        'hotspot'     => json_decode($p['hotspot'] ?? '{}', true) ?: (object) [],
    ];
}
$accentHex = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $category['background']) ? $category['background'] : '#CF2030';
$sceneData = ['accent' => $accentHex, 'theme' => $category['theme'], 'products' => $products];
$catName   = t_field($category['name']);
?>
<?= $this->section('content') ?>
<div x-data="showroomScene()">
    <section class="border-b border-white/10 bg-brand-black pt-28">
        <div class="container-x flex flex-wrap items-end justify-between gap-4 pb-6">
            <div>
                <a href="<?= esc(locale_url('showroom')) ?>" class="text-xs uppercase tracking-widest text-white/50 hover:text-white">← All categories</a>
                <h1 class="mt-2 text-3xl font-bold sm:text-4xl" style="color: <?= esc($accentHex, 'attr') ?>"><?= esc($catName) ?></h1>
            </div>
            <div class="text-sm text-white/60"><span class="text-brand-red">♥</span> <span x-text="$store.wishlist.count">0</span> saved</div>
        </div>

        <?php if ($materials !== []): ?>
            <div class="container-x flex flex-wrap gap-2 pb-5">
                <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-brand-red text-white' : 'bg-white/5 text-white/70'" class="rounded-full px-4 py-1.5 text-xs">All</button>
                <?php foreach ($materials as $m): ?>
                    <button @click="setFilter(<?= esc(json_encode($m), 'attr') ?>)"
                            :class="filter === <?= esc(json_encode($m), 'attr') ?> ? 'bg-brand-red text-white' : 'bg-white/5 text-white/70 hover:text-white'"
                            class="rounded-full px-4 py-1.5 text-xs"><?= esc($m) ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- 3D stage -->
    <section class="relative">
        <div data-showroom class="h-[62vh] min-h-[420px] w-full bg-[#08080a]"></div>
        <script type="application/json" id="showroom-data"><?= json_encode($sceneData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
        <p class="pointer-events-none absolute bottom-4 left-0 right-0 text-center text-[11px] uppercase tracking-widest text-white/40">Drag to orbit · click a piece for details</p>
    </section>

    <!-- Product list (fallback + quick select) -->
    <section class="bg-brand-black py-12">
        <div class="container-x">
            <h2 class="mb-6 text-sm font-semibold uppercase tracking-widest text-white/50">Pieces in this category</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="p in products.filter((p) => inList(p))" :key="p.id">
                    <button @click="select(p.id)" class="group rounded-2xl border border-white/10 bg-white/[0.02] p-4 text-left transition hover:border-brand-red/60">
                        <div class="flex gap-1.5">
                            <template x-for="c in p.gallery" :key="c">
                                <span class="h-6 w-6 rounded-full" :style="'background:' + c"></span>
                            </template>
                        </div>
                        <h3 class="mt-3 text-sm font-semibold" x-text="p.name"></h3>
                        <span class="mt-1 block text-xs text-white/50 group-hover:text-white">View details →</span>
                    </button>
                </template>
            </div>
        </div>
    </section>

    <!-- Product panel -->
    <div x-show="panelOpen" x-cloak class="fixed inset-0 z-[60]" @keydown.window.escape="close()">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="close()"></div>
        <div class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col overflow-y-auto border-l border-white/10 bg-brand-black p-8"
             x-show="panelOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <template x-if="current">
                <div>
                    <div class="flex items-start justify-between">
                        <h2 class="text-2xl font-bold" x-text="current.name"></h2>
                        <button @click="close()" class="text-white/50 hover:text-white">✕</button>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-white/70" x-text="current.description"></p>

                    <div class="mt-6 flex gap-2">
                        <template x-for="c in current.gallery" :key="c">
                            <span class="h-16 w-16 rounded-xl border border-white/10" :style="'background:' + c"></span>
                        </template>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-white/40">Materials</h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="m in current.materials" :key="m">
                                <span class="rounded-full bg-white/5 px-3 py-1 text-xs" x-text="m"></span>
                            </template>
                        </div>
                    </div>

                    <button @click="$store.wishlist.toggle(current.id)"
                            class="btn-ghost mt-6 w-full"
                            :class="$store.wishlist.has(current.id) ? 'border-brand-red text-brand-red' : ''">
                        <span x-text="$store.wishlist.has(current.id) ? '♥ Saved to wishlist' : '♡ Save to wishlist'"></span>
                    </button>

                    <!-- Inquiry -->
                    <div class="mt-8 border-t border-white/10 pt-6">
                        <h3 class="text-sm font-semibold uppercase tracking-widest text-white/50">Product inquiry</h3>
                        <template x-if="!sent">
                            <form @submit.prevent="submitInquiry()" class="mt-4 space-y-3">
                                <input type="text" x-model="form.website" class="hidden" tabindex="-1" aria-hidden="true">
                                <input type="text" x-model="form.name" required placeholder="Your name" class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
                                <input type="email" x-model="form.email" required placeholder="Email" class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
                                <input type="text" x-model="form.company" placeholder="Company (optional)" class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
                                <textarea x-model="form.message" rows="3" placeholder="Message" class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none"></textarea>
                                <p x-show="error" x-text="error" class="text-sm text-brand-red" x-cloak></p>
                                <button type="submit" class="btn-brand w-full" :disabled="sending">
                                    <span x-text="sending ? 'Sending…' : 'Send inquiry'"></span>
                                </button>
                            </form>
                        </template>
                        <template x-if="sent">
                            <p class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">Thank you — our team will be in touch shortly.</p>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
