<?php $this->extend('Modules\Admin\Views\layout'); ?>
<?= $this->section('content') ?>
<form method="post" action="<?= site_url('admin/media/upload') ?>" enctype="multipart/form-data"
      class="mb-8 flex flex-wrap items-center gap-3 rounded-xl border border-white/10 bg-white/[0.02] p-5">
    <?= csrf_field() ?>
    <input type="file" name="file" required class="text-sm text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-red file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
    <button class="btn-brand">Upload</button>
</form>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
    <?php foreach ($rows as $row): $isImg = str_starts_with((string) ($row['mime_type'] ?? ''), 'image/'); ?>
        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.02]">
            <div class="flex h-28 items-center justify-center bg-black/40">
                <?php if ($isImg): ?>
                    <img src="<?= esc($row['url']) ?>" alt="" class="h-full w-full object-cover">
                <?php else: ?>
                    <span class="text-xs uppercase tracking-widest text-white/40"><?= esc(pathinfo($row['path'], PATHINFO_EXTENSION) ?: 'file') ?></span>
                <?php endif; ?>
            </div>
            <div class="p-3">
                <input readonly value="<?= esc($row['url']) ?>" onclick="this.select()" class="w-full bg-transparent text-[11px] text-white/60">
                <div class="mt-2 flex items-center justify-between text-[10px] text-white/40">
                    <span><?= esc(number_format(((int) ($row['size_bytes'] ?? 0)) / 1024, 0)) ?> KB</span>
                    <form method="post" action="<?= site_url('admin/media/' . $row['id'] . '/delete') ?>" onsubmit="return confirm('Delete this file?')">
                        <?= csrf_field() ?>
                        <button class="hover:text-brand-red">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
        <p class="col-span-full py-10 text-center text-sm text-white/40">No media uploaded yet.</p>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
