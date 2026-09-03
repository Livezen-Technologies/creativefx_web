<?php $this->extend('Modules\Admin\Views\layout'); ?>
<?= $this->section('content') ?>

<!-- Centralized Media Manager: the browser UI is client-rendered (admin.js
     MediaBrowser) from /admin/media/list. A no-JS fallback form remains for
     classic uploads. -->
<div id="media-browser" data-caps='<?= esc(json_encode($caps ?? []), 'attr') ?>'></div>

<!-- The one folder whose name has a meaning elsewhere in the site. Without
     this note the connection between an upload and the front page is
     undiscoverable: nothing on the home page says where its photographs come
     from, and nothing here says the folder is special. -->
<aside class="mt-6 rounded-xl border border-white/10 bg-white/[0.02] p-5 text-sm leading-relaxed">
    <h2 class="font-semibold">The home page hero</h2>
    <p class="mt-1.5 text-white/70">
        Images in the folder <code class="rounded bg-black/40 px-1.5 py-0.5">hero</code> become the
        slideshow behind the front page heading, in filename order — name them
        <code class="rounded bg-black/40 px-1.5 py-0.5">01-…</code>,
        <code class="rounded bg-black/40 px-1.5 py-0.5">02-…</code> to set the sequence. The first six are used.
    </p>
    <p class="mt-2 text-white/70">
        Landscape photographs work best, 1600&times;900 or larger. Give each one a description
        in all three languages: it is what a reader using a screen reader is told the picture shows.
        With the folder empty the panel falls back to the Authority&rsquo;s emblem, so the page is
        never broken while photography is being chosen.
    </p>
</aside>

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
