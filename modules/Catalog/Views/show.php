<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');

$pName   = t_field(json_decode($product['name'] ?? '[]', true) ?: []);
$pShort  = t_field(json_decode($product['short_description'] ?? '[]', true) ?: []);
$pDesc   = t_field(json_decode($product['description'] ?? '[]', true) ?: []);
$catName = $category ? t_field(json_decode($category['name'] ?? '[]', true) ?: []) : '';

$jsonList = static function (?string $json): array {
    $v = json_decode((string) $json, true);
    return is_array($v) ? array_values(array_filter($v)) : [];
};
$features     = $jsonList($product['features'] ?? null);
$applications = $jsonList($product['applications'] ?? null);
$gallery      = $jsonList($product['gallery'] ?? null);
$specs        = array_values(array_filter(
    json_decode((string) ($product['specs'] ?? '[]'), true) ?: [],
    static fn ($s) => is_array($s) && ! empty($s['label']),
));
$certs   = array_values(array_filter(array_map('trim', explode(',', (string) ($product['certifications'] ?? '')))));
$has3d   = ! empty($product['model_path']);
$chips   = array_filter([$catName, $product['collection'] ?? null, $product['sku'] ?? null]);
?>
<?= $this->section('content') ?>

<!-- Product hero -->
<section class="relative overflow-hidden">
    <!-- Same photographic band as the range index, so a product page does not
         open on a flat panel. The product's own shot sits below, not here. -->
    <img src="/media/magiccorn/6.jpg" alt="" aria-hidden="true"
         class="hero-media absolute inset-0 -z-30 h-full w-full object-cover">
    <div class="hero-wash-side--page absolute inset-0 -z-20"></div>
    <div class="hero-wash-foot--page absolute inset-0 -z-20"></div>
    <div class="hero-red-glow absolute inset-0 -z-10"></div>
    <div class="container-x flex min-h-[30vh] flex-col justify-end pb-8 pt-36">
        <a href="<?= esc(locale_url('products')) ?>" class="mb-4 text-xs uppercase tracking-widest text-brand-red hover:underline">← <?= esc(lang('Site.products.back')) ?></a>
        <h1 class="max-w-3xl text-3xl font-bold leading-[1.1] sm:text-5xl" data-gsap="reveal"><?= esc($pName) ?></h1>
        <div class="mt-4 flex flex-wrap gap-2" data-gsap="reveal">
            <?php foreach ($chips as $chip): ?>
                <span class="rounded-full border border-white/15 bg-white/[0.04] px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/70"><?= esc($chip) ?></span>
            <?php endforeach; ?>
            <?php if (! empty($product['label'])): ?>
                <span class="rounded-full bg-brand-red px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-white"><?= esc(lang('Site.products.label_' . $product['label'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="bg-brand-black pb-20 pt-8">
    <div class="container-x grid gap-10 lg:grid-cols-12">
        <!-- Media: 3D viewer (when a GLB model is attached) + photography -->
        <div class="lg:col-span-7">
            <?php if ($has3d): ?>
                <div data-product-viewer
                     class="isolate relative aspect-[4/3] overflow-hidden rounded-3xl border border-white/10 bg-[#08080a]">
                    <script type="application/json" data-viewer-config><?= json_encode([
                        'model'  => $product['model_path'],
                        'poster' => $product['hero_image'] ?? '',
                        'hint'   => lang('Site.products.drag'),
                    ], JSON_UNESCAPED_SLASHES) ?></script>
                </div>
                <p class="mt-2 text-center text-xs uppercase tracking-widest text-white/40"><?= esc(lang('Site.products.drag')) ?></p>
            <?php elseif (! empty($product['hero_image'])): ?>
                <figure class="isolate overflow-hidden rounded-3xl border border-white/10" data-gsap="reveal">
                    <img src="<?= esc($product['hero_image'], 'attr') ?>" alt="<?= esc($pName, 'attr') ?>" class="w-full object-cover">
                </figure>
            <?php endif; ?>

            <?php $thumbs = array_values(array_diff($gallery, [$has3d ? '' : ($product['hero_image'] ?? '')])); ?>
            <?php if ($thumbs !== []): ?>
                <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    <?php foreach ($thumbs as $img): ?>
                        <figure class="isolate overflow-hidden rounded-xl border border-white/10">
                            <img src="<?= esc($img, 'attr') ?>" alt="<?= esc($pName, 'attr') ?>" loading="lazy" class="aspect-square w-full object-cover">
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="lg:col-span-5">
            <?php if ($pShort !== ''): ?><p class="text-lg leading-relaxed text-white/75"><?= esc($pShort) ?></p><?php endif; ?>
            <?php if ($pDesc !== '' && $pDesc !== $pShort): ?><div class="mt-4 leading-relaxed text-white/60"><?= rich_text(json_decode($product['description'] ?? '[]', true) ?: []) ?></div><?php endif; ?>

            <?php if ($features !== []): ?>
                <h2 class="mt-8 text-lg font-semibold"><?= esc(lang('Site.products.features')) ?></h2>
                <ul class="mt-3 space-y-2 text-white/70">
                    <?php foreach ($features as $f): ?>
                        <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-brand-red"></span><?= esc($f) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($applications !== []): ?>
                <h2 class="mt-8 text-lg font-semibold"><?= esc(lang('Site.products.applications')) ?></h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach ($applications as $a): ?>
                        <span class="rounded-full bg-white/[0.05] px-3.5 py-1.5 text-sm text-white/75 ring-1 ring-white/10"><?= esc($a) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($specs !== []): ?>
                <h2 class="mt-8 text-lg font-semibold"><?= esc(lang('Site.products.specs')) ?></h2>
                <dl class="mt-3 divide-y divide-white/[0.07] rounded-2xl border border-white/10 bg-white/[0.02]">
                    <?php foreach ($specs as $s): ?>
                        <div class="grid grid-cols-5 gap-3 px-5 py-3 text-sm">
                            <dt class="col-span-2 font-semibold uppercase tracking-wider text-white/50 text-xs pt-0.5"><?= esc($s['label']) ?></dt>
                            <dd class="col-span-3 text-white/75"><?= esc($s['value'] ?? '') ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>

            <?php if ($certs !== []): ?>
                <h2 class="mt-8 text-lg font-semibold"><?= esc(lang('Site.products.certifications')) ?></h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach ($certs as $c): ?>
                        <span class="rounded-lg border border-white/15 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/70"><?= esc($c) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($product['brochure_path'])): ?>
                <a href="<?= esc($product['brochure_path'], 'attr') ?>" target="_blank" rel="noopener" class="btn-ghost mt-8 inline-flex items-center gap-2 !px-5 !py-2.5 text-xs">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?= esc(lang('Site.products.download')) ?>
                </a>
            <?php endif; ?>

            <!-- Inquiry (stores a CRM lead, no reload) -->
            <div class="mt-10 rounded-2xl border border-white/10 bg-white/[0.02] p-6"
                 x-data="{ sending: false, sent: false, error: '', form: { name: '', email: '', company: '', message: '', website: '' },
                    async submit() {
                        this.sending = true; this.error = '';
                        try {
                            const res = await fetch('/api/inquiry', { method: 'POST', headers: { Accept: 'application/json' },
                                body: new URLSearchParams({ ...this.form, interest: <?= json_encode($pName) ?>, quantity: '', locale: document.documentElement.lang }) });
                            const data = await res.json();
                            this.sending = false;
                            if (res.ok && data.status === 'success') { this.sent = true; }
                            else { this.error = (data.messages && Object.values(data.messages)[0]) || 'Something went wrong.'; }
                        } catch (e) { this.sending = false; this.error = 'Network error.'; }
                    } }">
                <h2 class="text-lg font-semibold"><?= esc(lang('Site.products.inquiry')) ?></h2>
                <template x-if="sent">
                    <p class="mt-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-300"><?= esc(lang('Site.showroom.success')) ?></p>
                </template>
                <form x-show="!sent" @submit.prevent="submit()" class="mt-4 space-y-3">
                    <?php $in = 'w-full rounded-lg border border-white/15 bg-white/[0.04] px-3.5 py-2.5 text-sm placeholder:text-white/35 focus:border-brand-red focus:outline-none'; ?>
                    <input type="text" x-model="form.website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input type="text" x-model="form.name" required placeholder="<?= esc(lang('Site.contact.name'), 'attr') ?> *" class="<?= $in ?>">
                        <input type="email" x-model="form.email" required placeholder="<?= esc(lang('Site.contact.email'), 'attr') ?> *" class="<?= $in ?>">
                    </div>
                    <input type="text" x-model="form.company" placeholder="<?= esc(lang('Site.showroom.f_company'), 'attr') ?>" class="<?= $in ?>">
                    <textarea x-model="form.message" rows="3" placeholder="<?= esc(lang('Site.contact.message'), 'attr') ?>" class="<?= $in ?>"></textarea>
                    <p x-show="error" x-text="error" class="text-sm text-brand-red"></p>
                    <button type="submit" :disabled="sending" class="btn-brand w-full" x-text="sending ? '…' : <?= esc(json_encode(lang('Site.showroom.send')), 'attr') ?>"></button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($related !== []): ?>
        <div class="container-x mt-16 border-t border-white/10 pt-10">
            <h2 class="text-xl font-bold"><?= esc(lang('Site.products.related')) ?></h2>
            <div class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3">
                <?php foreach ($related as $r): ?>
                    <?= view('Modules\Catalog\Views\partials\card', ['product' => $r, 'label' => '']) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?= $this->endSection() ?>
