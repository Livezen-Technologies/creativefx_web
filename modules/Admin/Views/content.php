<?php helper('norlanka'); $this->extend('Modules\Admin\Views\layout'); ?>
<?= $this->section('content') ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <p class="text-sm text-white/50">Editing blocks for</p>
        <h2 class="text-xl font-semibold"><?= esc(t_field($page['title'] ?? [])) ?> <span class="text-white/40">/<?= esc($page['slug']) ?></span></h2>
    </div>
    <a href="<?= site_url('admin/pages') ?>" class="btn-ghost">Back</a>
</div>

<form method="post" action="<?= site_url('admin/pages/' . $page['id'] . '/content') ?>" class="space-y-8">
    <?= csrf_field() ?>
    <?php foreach ($page['sections'] ?? [] as $section): ?>
        <fieldset class="rounded-xl border border-white/10 p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red"><?= esc($section['key'] ?: $section['type']) ?> <span class="text-white/30">(<?= esc($section['type']) ?>)</span></legend>
            <?php foreach ($section['blocks'] ?? [] as $block):
                $pretty = json_encode(json_decode($block['content'] ?? '{}', true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
                <div class="mb-4">
                    <label class="mb-1 block text-xs uppercase tracking-widest text-white/40">Block #<?= (int) $block['id'] ?> — <?= esc($block['type']) ?></label>
                    <textarea name="blocks[<?= (int) $block['id'] ?>]" rows="<?= max(4, substr_count((string) $pretty, "\n") + 1) ?>"
                              class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 font-mono text-xs leading-relaxed focus:border-brand-red focus:outline-none"><?= esc($pretty) ?></textarea>
                </div>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <div class="flex gap-3">
        <button class="btn-brand">Save content</button>
        <a href="<?= site_url('admin/pages') ?>" class="btn-ghost">Cancel</a>
    </div>
    <p class="text-xs text-white/40">Each block is locale-aware JSON (e.g. <code>{"title":{"en":"…","ja":"…"}}</code>). Invalid JSON is skipped.</p>
</form>
<?= $this->endSection() ?>
