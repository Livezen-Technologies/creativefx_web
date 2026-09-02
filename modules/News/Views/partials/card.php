<?php
/**
 * News card. Expects: $post (row), optional $label (category name).
 */
helper(['norlanka', 'url']);
$cTitle   = t_field(json_decode($post['title'] ?? '[]', true) ?: []);
$cExcerpt = t_field(json_decode($post['excerpt'] ?? '[]', true) ?: []);
$cDate    = ! empty($post['published_at']) ? date('j M Y', strtotime($post['published_at'])) : '';
?>
<a href="<?= esc(locale_url('news/' . $post['slug'])) ?>"
   class="group isolate flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] transition hover:border-brand-red/40 hover:bg-white/[0.05]">
    <?php if (! empty($post['image'])): ?>
        <span class="relative block aspect-[16/9] overflow-hidden">
            <img src="<?= esc(media_src($post['image']), 'attr') ?>" alt="<?= esc($cTitle, 'attr') ?>" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]">
        </span>
    <?php endif; ?>
    <span class="flex flex-1 flex-col p-6">
        <span class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-white/45">
            <?php if (! empty($label)): ?>
                <span class="text-brand-red"><?= esc($label) ?></span>
                <span aria-hidden="true">·</span>
            <?php endif; ?>
            <?php if ($cDate !== ''): ?><time datetime="<?= esc(date('Y-m-d', strtotime($post['published_at'])), 'attr') ?>"><?= esc($cDate) ?></time><?php endif; ?>
        </span>
        <span class="mt-3 text-lg font-bold leading-snug transition group-hover:text-brand-red"><?= esc($cTitle) ?></span>
        <?php if ($cExcerpt !== ''): ?>
            <span class="mt-2 line-clamp-3 text-sm leading-relaxed text-white/60"><?= esc($cExcerpt) ?></span>
        <?php endif; ?>
        <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-brand-red">
            <?= esc(lang('Site.news.read')) ?>
            <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
    </span>
</a>
