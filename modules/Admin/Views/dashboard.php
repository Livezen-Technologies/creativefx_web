<?php
helper(['url', 'norlanka', 'admin']);
$this->extend('Modules\Admin\Views\layout');

// Presentation helpers live in the admin helper now, not in this file: the
// dashboard is a set of partials, and a closure defined here is a local
// variable that $this->include() does not carry into any of them.
$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$userName = trim((string) (session()->get('admin_user')['first_name'] ?? '')) ?: 'there';
?>
<?= $this->section('content') ?>

<!-- Greeting + quick actions -->
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold"><?= esc($greeting) ?>, <?= esc($userName) ?> 👋</h2>
        <p class="mt-1 text-sm text-white/50"><?= esc(date('l, j F Y')) ?> — here's what's happening across the site.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= site_url('admin/news-posts/new') ?>" class="flex items-center gap-1.5 rounded-lg bg-brand-red px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-brand-red/85"><?= admin_icon('plus', 'h-3.5 w-3.5') ?> News post</a>
        <a href="<?= site_url('admin/rooms/new') ?>" class="flex items-center gap-1.5 rounded-lg border border-white/15 px-3.5 py-2 text-xs font-semibold text-white/80 transition hover:border-white/40"><?= admin_icon('bed', 'h-3.5 w-3.5') ?> Add a room</a>
        <a href="<?= site_url('admin/media') ?>" class="flex items-center gap-1.5 rounded-lg border border-white/15 px-3.5 py-2 text-xs font-semibold text-white/80 transition hover:border-white/40"><?= admin_icon('upload', 'h-3.5 w-3.5') ?> Upload media</a>
        <a href="<?= site_url('admin/pages') ?>" class="flex items-center gap-1.5 rounded-lg border border-white/15 px-3.5 py-2 text-xs font-semibold text-white/80 transition hover:border-white/40"><?= admin_icon('edit', 'h-3.5 w-3.5') ?> Edit pages</a>
    </div>
</div>

<?php // Widgets are drawn from the layout: which appear, in what order and at
      // what width is this administrator's own, stored against their account.
      // Each partial is a self-contained panel and knows nothing about the grid,
      // so a rearrangement is data rather than a template change. ?>
<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-12">
    <?php foreach ($widgets as $w): ?>
        <div class="<?= esc($w['span'], 'attr') ?> min-w-0">
            <?php // include(), not view(): it renders with the data this view was
                  // given, which is how each partial reaches $kpis, $days and
                  // the rest without every one of them being threaded through. ?>
            <?= $this->include('Modules\Admin\Views\dashboard\\' . $w['key']) ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($widgets === []): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-white/15 p-10 text-center">
        <p class="text-sm text-white/50">Every panel is switched off. Open <strong class="text-white/75">Customise</strong> below to bring some back.</p>
    </div>
<?php endif; ?>

<?php // The customiser sits at the bottom and is closed by default: it is a
      // thing you use once and then forget, and a dashboard that opens onto its
      // own settings has buried the point of itself. ?>
<div class="mt-8" x-data="{ open: false }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <button type="button" @click="open = ! open"
                class="inline-flex items-center gap-2 rounded-lg border border-white/15 px-3.5 py-2 text-xs font-semibold text-white/70 transition hover:border-white/40 hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>
            <span x-text="open ? 'Done' : 'Customise dashboard'">Customise dashboard</span>
        </button>
        <form method="post" action="<?= site_url('admin/dashboard/layout/reset') ?>" x-show="open" x-cloak>
            <?= csrf_field() ?>
            <button class="text-xs text-white/45 transition hover:text-white">Reset to the default arrangement</button>
        </form>
    </div>

    <div x-show="open" x-cloak x-transition class="mt-4 overflow-hidden rounded-2xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
                <tr>
                    <th class="px-4 py-3">Panel</th>
                    <th class="px-4 py-3">Width</th>
                    <th class="px-4 py-3 text-right">Order</th>
                    <th class="px-4 py-3 text-right">Shown</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($allWidgets as $i => $w): ?>
                    <tr class="<?= $w['enabled'] ? '' : 'opacity-50' ?>">
                        <td class="px-4 py-3">
                            <p class="font-medium"><?= esc($w['label']) ?></p>
                            <p class="mt-0.5 max-w-md text-xs text-white/40"><?= esc($w['about']) ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <form method="post" action="<?= site_url('admin/dashboard/layout/' . $w['key'] . '/size') ?>">
                                <?= csrf_field() ?>
                                <?php // Submits on change, so a width is one click
                                      // rather than a click and a Save. ?>
                                <select name="size" onchange="this.form.submit()"
                                        class="rounded-lg border border-white/15 bg-black/30 px-2 py-1 text-xs text-white">
                                    <?php foreach ($sizes as $key => $size): ?>
                                        <option value="<?= esc($key, 'attr') ?>" <?= $w['size'] === $key ? 'selected' : '' ?>><?= esc($size['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <?php foreach ([['up', 'M12 19V5M5 12l7-7 7 7', $i > 0], ['down', 'M12 5v14M19 12l-7 7-7-7', $i < count($allWidgets) - 1]] as [$dir, $path, $can]): ?>
                                    <form method="post" action="<?= site_url('admin/dashboard/layout/' . $w['key'] . '/move') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="dir" value="<?= $dir ?>">
                                        <button <?= $can ? '' : 'disabled' ?> aria-label="Move <?= $dir ?>"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-white/15 text-white/60 transition enabled:hover:border-white/40 enabled:hover:text-white disabled:opacity-25">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= $path ?>"/></svg>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="post" action="<?= site_url('admin/dashboard/layout/' . $w['key'] . '/toggle') ?>">
                                <?= csrf_field() ?>
                                <button class="rounded-lg border border-white/15 px-2.5 py-1 text-xs font-semibold text-white/70 transition hover:border-white/40 hover:text-white">
                                    <?= $w['enabled'] ? 'Hide' : 'Show' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="border-t border-white/10 px-4 py-3 text-xs leading-relaxed text-white/35">
            This arrangement is yours alone — other administrators keep their own. Widths apply on wide screens;
            below that every panel is full width, because two half-width panels side by side on a phone are two
            unreadable ones.
        </p>
    </div>
</div>

<?= $this->endSection() ?>
