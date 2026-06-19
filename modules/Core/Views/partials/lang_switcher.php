<?php helper('norlanka'); $locale = current_locale(); $locales = supported_locales(); ?>
<div
    x-data="langSwitcher(<?= esc(json_encode(['current' => $locale, 'locales' => $locales]), 'attr') ?>)"
    class="relative"
    @click.outside="open = false"
>
    <button type="button" @click="open = !open"
            class="flex items-center gap-2 rounded-full border border-white/25 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/90 transition hover:border-white">
        <span x-text="label(current)"><?= esc(strtoupper($locale)) ?></span>
        <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none"><path d="M3 4.5 6 7.5 9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <div x-show="open" x-transition x-cloak
         class="absolute right-0 mt-2 w-40 overflow-hidden rounded-xl border border-white/10 bg-brand-black/95 backdrop-blur">
        <template x-for="l in locales" :key="l.code">
            <button type="button" @click="go(l.code)"
                    :class="l.code === current ? 'text-brand-red' : 'text-white/80 hover:text-white'"
                    class="flex w-full items-center justify-between px-4 py-2 text-sm transition">
                <span x-text="l.native"></span>
                <span class="text-[10px] uppercase tracking-widest opacity-60" x-text="l.code"></span>
            </button>
        </template>
    </div>
</div>
