<?php $this->extend('Modules\Admin\Views\layout');
$in = 'rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
?>
<?= $this->section('content') ?>

<!-- Bulk upload -->
<form method="post" action="<?= site_url('admin/media/upload') ?>" enctype="multipart/form-data"
      class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-white/10 bg-white/[0.02] p-5">
    <?= csrf_field() ?>
    <label class="block">
        <span class="mb-1 block text-xs font-semibold uppercase tracking-widest text-white/50">Files</span>
        <input type="file" name="files[]" multiple required class="text-sm text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-red file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-semibold uppercase tracking-widest text-white/50">Folder</span>
        <input type="text" name="folder" placeholder="uploads" class="<?= $in ?> w-36">
    </label>
    <label class="block min-w-40 flex-1">
        <span class="mb-1 block text-xs font-semibold uppercase tracking-widest text-white/50">Tags</span>
        <input type="text" name="tags" placeholder="comma,separated,tags" class="<?= $in ?> w-full">
    </label>
    <button class="btn-brand">Upload</button>
    <p class="w-full text-xs text-white/35">Images, videos, PDF/Word/Excel/ZIP and GLB/GLTF 3D models. JPEG/PNG images get an optimized <code>.webp</code> copy at the same path automatically.</p>
</form>

<!-- Search + type filter -->
<form method="get" action="<?= site_url('admin/media') ?>" class="mb-6 flex flex-wrap items-center gap-2">
    <input type="text" name="q" value="<?= esc($q ?? '') ?>" placeholder="Search name, tag, folder…" class="<?= $in ?> w-64">
    <?php if (($type ?? '') !== ''): ?><input type="hidden" name="type" value="<?= esc($type, 'attr') ?>"><?php endif; ?>
    <button class="btn-ghost !px-4 !py-2 text-xs">Search</button>
    <span class="mx-2 h-5 w-px bg-white/10"></span>
    <?php $chip = static fn (bool $on): string => $on
        ? 'rounded-full bg-brand-red px-3.5 py-1.5 text-xs font-semibold uppercase tracking-widest text-white'
        : 'rounded-full border border-white/15 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-widest text-white/60 hover:border-brand-red/50 hover:text-white'; ?>
    <a href="<?= site_url('admin/media') . (($q ?? '') !== '' ? '?q=' . urlencode($q) : '') ?>" class="<?= $chip(($type ?? '') === '') ?>">All</a>
    <?php foreach ($types as $t): ?>
        <a href="<?= site_url('admin/media') . '?type=' . $t . (($q ?? '') !== '' ? '&q=' . urlencode($q) : '') ?>" class="<?= $chip(($type ?? '') === $t) ?>"><?= esc(ucfirst($t)) ?></a>
    <?php endforeach; ?>
</form>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
    <?php foreach ($rows as $row): $isImg = str_starts_with((string) ($row['mime_type'] ?? ''), 'image/'); ?>
        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.02]">
            <div class="flex h-28 items-center justify-center bg-black/40">
                <?php if ($isImg): ?>
                    <img src="<?= esc($row['url']) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
                <?php else: ?>
                    <span class="text-xs uppercase tracking-widest text-white/40"><?= esc(pathinfo($row['path'], PATHINFO_EXTENSION) ?: 'file') ?></span>
                <?php endif; ?>
            </div>
            <div class="p-3">
                <div class="flex items-center gap-1.5">
                    <input readonly value="<?= esc($row['url']) ?>" onclick="this.select()" class="w-full bg-transparent text-[11px] text-white/60">
                    <button type="button" onclick="navigator.clipboard.writeText('<?= esc($row['url'], 'js') ?>');this.textContent='✓';setTimeout(()=>this.textContent='Copy',1200)"
                            class="rounded-md border border-white/15 px-2 py-0.5 text-[10px] uppercase tracking-wider text-white/60 hover:border-white hover:text-white">Copy</button>
                </div>
                <?php if (! empty($row['tags'])): ?>
                    <p class="mt-1.5 truncate text-[10px] text-white/40">🏷 <?= esc($row['tags']) ?></p>
                <?php endif; ?>
                <div class="mt-2 flex items-center justify-between text-[10px] text-white/40">
                    <span><?= esc($row['folder'] ?? '') ?> · <?= esc(number_format(((int) ($row['size_bytes'] ?? 0)) / 1024, 0)) ?> KB<?= $row['width'] ?? null ? ' · ' . (int) $row['width'] . '×' . (int) $row['height'] : '' ?></span>
                    <form method="post" action="<?= site_url('admin/media/' . $row['id'] . '/delete') ?>" onsubmit="return confirm('Delete this file?')">
                        <?= csrf_field() ?>
                        <button class="hover:text-brand-red">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
        <p class="col-span-full py-10 text-center text-sm text-white/40">No media found<?= ($q ?? '') !== '' || ($type ?? '') !== '' ? ' for this filter' : ' yet' ?>.</p>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
