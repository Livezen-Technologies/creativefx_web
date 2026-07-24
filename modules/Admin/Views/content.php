<?php helper('norlanka'); $this->extend('Modules\Admin\Views\layout');
$btn  = 'rounded-md border border-white/15 px-2 py-1 text-[11px] uppercase tracking-wider text-white/60 hover:border-white hover:text-white';
$sel  = 'rounded-md border border-white/15 bg-black/40 px-2 py-1 text-xs focus:border-brand-red focus:outline-none';
?>
<?= $this->section('content') ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <p class="text-sm text-white/50">Page builder</p>
        <h2 class="text-xl font-semibold"><?= esc(t_field($page['title'] ?? [])) ?> <span class="text-white/40">/<?= esc($page['slug']) ?></span></h2>
    </div>
    <div class="flex gap-3">
        <a href="<?= site_url((config('App')->supportedLocales[0] ?? 'en') . '/' . ($page['is_home'] ? '' : $page['slug'])) ?>" target="_blank" class="btn-ghost">Preview ↗</a>
        <a href="<?= site_url('admin/pages') ?>" class="btn-ghost">Back</a>
    </div>
</div>

<!-- Structure operations use their own small forms; content editing is one big form below. -->
<?php foreach ($page['sections'] ?? [] as $section): $hidden = $section['status'] !== 'published'; ?>
    <div class="mb-6 rounded-xl border <?= $hidden ? 'border-amber-400/30 opacity-70' : 'border-white/10' ?> p-5">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-widest text-brand-red"><?= esc($section['key'] ?: $section['type']) ?></span>
            <span class="text-xs text-white/30">(<?= esc($section['type']) ?>)</span>
            <?php if ($hidden): ?><span class="rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] uppercase tracking-widest text-amber-300">hidden</span><?php endif; ?>
            <span class="ml-auto flex items-center gap-1.5">
                <form method="post" action="<?= site_url('admin/sections/' . $section['id'] . '/move') ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button class="<?= $btn ?>" title="Move up">↑</button></form>
                <form method="post" action="<?= site_url('admin/sections/' . $section['id'] . '/move') ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button class="<?= $btn ?>" title="Move down">↓</button></form>
                <form method="post" action="<?= site_url('admin/sections/' . $section['id'] . '/toggle') ?>"><?= csrf_field() ?><button class="<?= $btn ?>"><?= $hidden ? 'Show' : 'Hide' ?></button></form>
                <form method="post" action="<?= site_url('admin/sections/' . $section['id'] . '/delete') ?>" onsubmit="return confirm('Delete this section and all its blocks?')"><?= csrf_field() ?><button class="<?= $btn ?> !border-brand-red/40 !text-brand-red">Delete</button></form>
            </span>
        </div>

        <?php foreach ($section['blocks'] ?? [] as $block):
            $bHidden = $block['status'] !== 'published';
            $pretty  = json_encode(json_decode($block['content'] ?? '{}', true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            <div class="mb-4 rounded-lg border <?= $bHidden ? 'border-amber-400/30 opacity-70' : 'border-white/5' ?> bg-white/[0.02] p-3">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="text-xs uppercase tracking-widest text-white/50">#<?= (int) $block['id'] ?> · <?= esc($block['type']) ?></span>
                    <?php if ($bHidden): ?><span class="rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] uppercase tracking-widest text-amber-300">hidden</span><?php endif; ?>
                    <span class="ml-auto flex items-center gap-1.5">
                        <form method="post" action="<?= site_url('admin/blocks/' . $block['id'] . '/move') ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button class="<?= $btn ?>" title="Move up">↑</button></form>
                        <form method="post" action="<?= site_url('admin/blocks/' . $block['id'] . '/move') ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button class="<?= $btn ?>" title="Move down">↓</button></form>
                        <form method="post" action="<?= site_url('admin/blocks/' . $block['id'] . '/toggle') ?>"><?= csrf_field() ?><button class="<?= $btn ?>"><?= $bHidden ? 'Show' : 'Hide' ?></button></form>
                        <form method="post" action="<?= site_url('admin/blocks/' . $block['id'] . '/delete') ?>" onsubmit="return confirm('Delete this block?')"><?= csrf_field() ?><button class="<?= $btn ?> !border-brand-red/40 !text-brand-red">Delete</button></form>
                    </span>
                </div>
                <textarea name="blocks[<?= (int) $block['id'] ?>]" form="content-form" rows="<?= min(24, max(4, substr_count((string) $pretty, "\n") + 1)) ?>"
                          class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 font-mono text-xs leading-relaxed focus:border-brand-red focus:outline-none"><?= esc($pretty) ?></textarea>
            </div>
        <?php endforeach; ?>

        <!-- Add block to this section -->
        <form method="post" action="<?= site_url('admin/sections/' . $section['id'] . '/blocks') ?>" class="mt-2 flex items-center gap-2">
            <?= csrf_field() ?>
            <select name="type" class="<?= $sel ?>">
                <?php foreach ($blockTypes as $t): ?><option value="<?= esc($t, 'attr') ?>"><?= esc($t) ?></option><?php endforeach; ?>
            </select>
            <button class="<?= $btn ?>">+ Add block</button>
        </form>
    </div>
<?php endforeach; ?>

<!-- Add section -->
<form method="post" action="<?= site_url('admin/pages/' . $page['id'] . '/sections') ?>" class="mb-8 flex flex-wrap items-center gap-2 rounded-xl border border-dashed border-white/15 p-5">
    <?= csrf_field() ?>
    <input type="text" name="key" placeholder="section key (e.g. faq)" class="<?= $sel ?> w-48">
    <input type="text" name="type" placeholder="type (e.g. content)" class="<?= $sel ?> w-40">
    <button class="<?= $btn ?>">+ Add section</button>
    <span class="text-xs text-white/35">New sections start published and empty — add blocks to them above.</span>
</form>

<!-- Content save (single form covering every block textarea via form=) -->
<form id="content-form" method="post" action="<?= site_url('admin/pages/' . $page['id'] . '/content') ?>" class="sticky bottom-4">
    <?= csrf_field() ?>
    <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-brand-black/95 p-4 backdrop-blur">
        <button class="btn-brand">Save content</button>
        <span class="text-xs text-white/40">Each block is locale-aware JSON (e.g. <code>{"title":{"en":"…","ja":"…"}}</code>). Structure changes (order, add, hide, delete) apply immediately.</span>
    </div>
</form>
<?= $this->endSection() ?>
