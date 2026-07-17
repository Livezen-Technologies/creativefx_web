<?php
/**
 * Product card. Expects: $product (row), optional $label (category name).
 */
helper(['norlanka', 'url']);
$cName  = t_field(json_decode($product['name'] ?? '[]', true) ?: []);
$cShort = t_field(json_decode($product['short_description'] ?? '[]', true) ?: []);
$badge  = $product['label'] ?? '';
?>
<a href="<?= esc(locale_url('products/' . $product['slug'])) ?>"
   class="group isolate flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/40 hover:bg-white/[0.05]">
    <span class="relative block aspect-[4/5] overflow-hidden">
        <?php if (! empty($product['hero_image'])): ?>
            <img src="<?= esc($product['hero_image'], 'attr') ?>" alt="<?= esc($cName, 'attr') ?>" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]">
        <?php endif; ?>
        <?php if ($badge !== '' && $badge !== null): ?>
            <span class="absolute left-3 top-3 rounded-full bg-brand-red px-3 py-1 text-[10px] font-semibold uppercase tracking-widest text-white"><?= esc(lang('Site.products.label_' . $badge)) ?></span>
        <?php endif; ?>
        <?php if (! empty($product['model_path'])): ?>
            <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/60 px-3 py-1 text-[10px] font-semibold uppercase tracking-widest text-white backdrop-blur">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5zM3 7l9 5m0 0 9-5m-9 5v10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                3D
            </span>
        <?php endif; ?>
    </span>
    <span class="flex flex-1 flex-col p-5">
        <?php if (! empty($label)): ?>
            <span class="text-[11px] font-semibold uppercase tracking-widest text-brand-red"><?= esc($label) ?></span>
        <?php endif; ?>
        <span class="mt-1.5 text-base font-bold leading-snug transition group-hover:text-brand-red"><?= esc($cName) ?></span>
        <?php if ($cShort !== ''): ?>
            <span class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-white/60"><?= esc($cShort) ?></span>
        <?php endif; ?>
    </span>
</a>
