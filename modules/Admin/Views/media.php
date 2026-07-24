<?php $this->extend('Modules\Admin\Views\layout'); ?>
<?= $this->section('content') ?>

<!-- Centralized Media Manager: the browser UI is client-rendered (admin.js
     MediaBrowser) from /admin/media/list. A no-JS fallback form remains for
     classic uploads. -->
<div id="media-browser" data-caps='<?= esc(json_encode($caps ?? []), 'attr') ?>'></div>

<noscript>
    <form method="post" action="<?= site_url('admin/media/upload') ?>" enctype="multipart/form-data"
          class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-white/10 bg-white/[0.02] p-5">
        <?= csrf_field() ?>
        <input type="file" name="files[]" multiple required class="text-sm">
        <input type="text" name="folder" placeholder="folder (default: uploads)" class="rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm">
        <input type="text" name="tags" placeholder="tags,comma,separated" class="rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm">
        <button class="btn-brand">Upload</button>
    </form>
</noscript>
<?= $this->endSection() ?>
