<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.gallery.title')]],
    'eyebrow' => lang('Site.nav.media_centre'),
    'heading' => lang('Site.gallery.title'),
    'intro'   => lang('Site.gallery.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12"
         x-data="{ open: false, i: 0, items: <?= esc(json_encode(array_map(static fn ($m) => [
             'src' => $m['url'] ?? ('/' . ltrim((string) $m['path'], '/')),
             'alt' => (string) ($m['alt'] ?? ''),
         ], $images)), 'attr') ?>,
           show(n) { this.i = (n + this.items.length) % this.items.length; this.open = true; },
           close() { this.open = false; } }"
         @keydown.escape.window="close()"
         @keydown.arrow-right.window="open && show(i + 1)"
         @keydown.arrow-left.window="open && show(i - 1)">
    <div class="container-x">
        <?php if (count($albums) > 1): ?>
            <nav aria-label="<?= esc(lang('Site.gallery.albums'), 'attr') ?>" class="flex flex-wrap gap-2">
                <a href="<?= esc(locale_url('gallery')) ?>"
                   class="rounded-full border px-4 py-1.5 text-sm font-medium transition <?= $album === '' ? 'border-brand-red bg-brand-red/10 text-brand-red' : 'border-line bg-surface hover:border-brand-red' ?>">
                    <?= esc(lang('Site.gallery.all_albums')) ?>
                </a>
                <?php foreach ($albums as $name => $count): ?>
                    <a href="<?= esc(locale_url('gallery?album=' . rawurlencode((string) $name))) ?>"
                       class="rounded-full border px-4 py-1.5 text-sm font-medium transition <?= $album === (string) $name ? 'border-brand-red bg-brand-red/10 text-brand-red' : 'border-line bg-surface hover:border-brand-red' ?>">
                        <?= esc(ucfirst(str_replace('-', ' ', (string) $name))) ?>
                        <span class="ml-1.5 text-xs text-white/50"><?= esc((string) $count) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($images === []): ?>
            <p class="mt-8 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.gallery.none')) ?></p>
        <?php else: ?>
            <ul class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" role="list">
                <?php foreach ($images as $n => $image): ?>
                    <li>
                        <button type="button" @click="show(<?= (int) $n ?>)"
                                class="group block w-full overflow-hidden rounded-xl border border-line bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                            <img src="<?= esc($image['url'] ?? ('/' . ltrim((string) $image['path'], '/')), 'attr') ?>"
                                 alt="<?= esc((string) ($image['alt'] ?? ''), 'attr') ?>"
                                 loading="lazy" width="<?= (int) ($image['width'] ?: 800) ?>" height="<?= (int) ($image['height'] ?: 600) ?>"
                                 class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php // The lightbox. A dialog with a real close button and keyboard
          // handling; the page behind it stays where it was, and Escape and the
          // arrow keys do what everyone expects them to do. ?>
    <div x-show="open" x-cloak x-transition.opacity
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4"
         role="dialog" aria-modal="true" @click.self="close()">
        <button type="button" @click="close()"
                class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full border border-white/30 text-white transition hover:bg-white/10"
                aria-label="<?= esc(lang('Site.gallery.close'), 'attr') ?>">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
        </button>
        <button type="button" @click="show(i - 1)"
                class="absolute left-3 flex h-11 w-11 items-center justify-center rounded-full border border-white/30 text-white transition hover:bg-white/10 sm:left-6"
                aria-label="<?= esc(lang('Site.gallery.prev'), 'attr') ?>">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" @click="show(i + 1)"
                class="absolute right-3 flex h-11 w-11 items-center justify-center rounded-full border border-white/30 text-white transition hover:bg-white/10 sm:right-6"
                aria-label="<?= esc(lang('Site.gallery.next'), 'attr') ?>">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <figure class="max-h-full max-w-5xl">
            <img :src="items[i]?.src" :alt="items[i]?.alt" class="max-h-[80vh] w-auto rounded-lg">
            <figcaption class="mt-3 text-center text-sm text-white/70" x-text="items[i]?.alt"></figcaption>
        </figure>
    </div>
</section>

<?= $this->endSection() ?>
