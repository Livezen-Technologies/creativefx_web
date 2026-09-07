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
<?php // Rewritten because every substantive claim in it was wrong for this site:
      // it promised a slideshow of six images, asked for descriptions "in all
      // three languages", and said an empty folder falls back to the
      // Authority's emblem — wording inherited from the fork this was built
      // from. The one place in the admin that explains where the front page
      // photograph comes from was describing a different front page. ?>
<aside class="mt-6 rounded-xl border border-white/10 bg-white/[0.02] p-5 text-sm leading-relaxed">
    <h2 class="font-semibold">The home page hero</h2>
    <p class="mt-1.5 text-white/70">
        One image sits behind the heading on the front page: the first, in filename order, that is
        either in the folder <code class="rounded bg-black/40 px-1.5 py-0.5">hero</code> or carries the
        tag <code class="rounded bg-black/40 px-1.5 py-0.5">hero</code>. To use a picture that is
        already uploaded, select it and add that tag under <em>Alt text and tags</em> — there is no
        need to upload it a second time or move the file.
    </p>
    <p class="mt-2 text-white/70">
        Landscape photographs work best, 1600&times;900 or larger, and darker ones read better: the
        words sit on top of the picture under a shading layer, and that layer is set for the
        brightest image anybody could upload rather than tuned to yours. Give it alt text — this is
        the largest image on the site, and it is what a reader using a screen reader is told the
        picture shows.
    </p>
    <p class="mt-2 text-white/70">
        With no hero image the front page shows its heading full width, which is a finished design
        rather than a gap — so the page is never broken while photography is being chosen.
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
