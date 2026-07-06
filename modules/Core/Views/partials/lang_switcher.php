<?php helper('norlanka'); $locale = current_locale(); $locales = supported_locales(); ?>
<div
    x-data="langSwitcher(<?= esc(json_encode(['current' => $locale, 'locales' => $locales]), 'attr') ?>)"
    class="relative"
    @click.outside="open = false"
>
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="flex items-center gap-1.5 rounded-full border border-white/25 bg-white/[0.06] px-2.5 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:border-brand-red/60 hover:bg-white/10 sm:gap-2 sm:px-3.5"
            aria-label="Change language">
        <svg class="h-4 w-4 text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18" stroke-linecap="round"/></svg>
        <!-- Full language name on ≥sm, compact locale code (EN/日本語 code) on mobile. -->
        <span class="hidden sm:inline" x-text="label(current)"><?= esc(strtoupper($locale)) ?></span>
        <span class="sm:hidden" x-text="current.toUpperCase()"><?= esc(strtoupper($locale)) ?></span>
        <svg class="hidden h-3 w-3 transition-transform sm:block" :class="open ? 'rotate-180' : ''" viewBox="0 0 12 12" fill="none"><path d="M3 4.5 6 7.5 9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
