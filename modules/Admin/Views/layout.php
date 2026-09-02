<?php
helper('url');
$user   = session()->get('admin_user') ?? [];
$active = $active ?? '';

// Grouped navigation: [group label, [[key, label, path, icon], ...]]
// Icons are Lucide-style 24px stroke paths.
$icons = [
    'home'      => 'M3 12l9-9 9 9M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10',
    'file'      => 'M14 3v5h5M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z',
    'news'      => 'M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0V9m14-3h-6m6 4h-6m6 4H8m10 4H8',
    'tag'       => 'M12 2H2v10l9.29 9.29a1 1 0 0 0 1.42 0l8.58-8.58a1 1 0 0 0 0-1.42L12 2ZM7 7h.01',
    'box'       => 'M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8ZM3.3 7l8.7 5 8.7-5M12 22V12',
    'video'     => 'M22 8l-6 4 6 4V8ZM14 6H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2Z',
    'cube'      => 'M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12s4.5 10 10 10ZM2 12h20M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10 15 15 0 0 1 4-10Z',
    'leaf'      => 'M11 20A7 7 0 0 1 4 13c0-4 3-8 8-10 0 0 8 3 8 10a7 7 0 0 1-7 7h-2ZM6 21c1-3 3-6 6-8',
    'briefcase' => 'M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Zm0 4h18',
    'users'     => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m22 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
    'globe'     => 'M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12s4.5 10 10 10ZM2 12h20M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10 15 15 0 0 1 4-10Z',
    'image'     => 'M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM9 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm12 5-3.5-3.5a2 2 0 0 0-3 0L6 21',
    'inbox'     => 'M22 12h-6l-2 3h-4l-2-3H2m3.5-7 -3.5 7v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-7a2 2 0 0 0-1.8-1H7.3a2 2 0 0 0-1.8 1Z',
    'target'    => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm0-6a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0-3a1 1 0 1 0 0-2',
    'settings'  => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7.5 7.5 0 0 0-1.7-1L14.8 3h-4l-.4 2.6a7.5 7.5 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7.5 7.5 0 0 0 1.7 1l.4 2.4h4l.4-2.6a7.5 7.5 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6c.07-.3.1-.7.1-1Z',
];

$groups = [
    ['Overview', [
        ['dashboard', 'Dashboard', 'admin', 'home'],
    ]],
    ['Content', [
        ['pages', 'Pages', 'admin/pages', 'file'],
        ['news-posts', 'News Posts', 'admin/news-posts', 'news'],
        ['news-categories', 'News Categories', 'admin/news-categories', 'tag'],
        ['videos', 'Launch Video', 'admin/videos', 'video'],
    ]],
    ['Catalog', [
        ['categories', 'Product Categories', 'admin/categories', 'tag'],
        ['products', 'Products', 'admin/products', 'box'],
        ['showroom-cats', 'Showroom Categories', 'admin/showroom-categories', 'cube'],
        ['showroom-products', 'Showroom Products', 'admin/showroom-products', 'cube'],
    ]],
    ['People', [
        ['jobs', 'Jobs', 'admin/jobs', 'briefcase'],
        ['applications', 'Applications', 'admin/applications', 'users'],
    ]],
    ['Engagement', [
        ['contacts', 'Contact Inbox', 'admin/contacts', 'inbox'],
        ['leads', 'Leads', 'admin/leads', 'target'],
    ]],
    ['System', [
        ['media', 'Media Library', 'admin/media', 'image'],
        ['translations', 'Translations', 'admin/translations', 'globe'],
        ['esg', 'ESG Metrics', 'admin/esg-metrics', 'leaf'],
        ['settings', 'Settings', 'admin/settings', 'settings'],
    ]],
];

$navIcon = static fn (string $key): string => '<svg class="h-[18px] w-[18px] flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="' . ($icons[$key] ?? $icons['file']) . '"/></svg>';

$renderNav = static function () use ($groups, $active, $navIcon): void {
    foreach ($groups as [$group, $items]) { ?>
        <p class="mb-1 mt-5 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/30 first:mt-0"><?= esc($group) ?></p>
        <?php foreach ($items as [$key, $label, $path, $icon]) { ?>
            <a href="<?= site_url($path) ?>"
               class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition <?= $active === $key
                   ? 'bg-brand-red text-white shadow-lg shadow-brand-red/25'
                   : 'text-white/60 hover:bg-white/5 hover:text-white' ?>">
                <?= $navIcon($icon) ?>
                <span class="truncate"><?= esc($label) ?></span>
            </a>
        <?php }
    }
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf" data-name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Admin') ?> — <?= esc(setting('site_name', 'Magic Corn')) ?> Admin</title>
    <script>window.ADMIN_BASE = <?= json_encode(site_url('admin')) ?>;</script>
    <?= vite_tags('resources/js/app.js') ?>
    <?= vite_tags('resources/js/admin.js') ?>
</head>
<body class="on-dark min-h-screen bg-brand-black font-sans text-white antialiased">
<script>/* Apply the stored admin theme before first paint. */
if (localStorage.getItem('admin-theme') === 'light') { document.body.classList.remove('on-dark'); }</script>
<div class="flex min-h-screen" x-data="{ open: false }">
    <!-- Sidebar (desktop) -->
    <aside class="hidden w-64 flex-none border-r border-white/10 bg-brand-black lg:flex lg:flex-col">
        <div class="flex h-16 flex-none items-center border-b border-white/10 px-6">
            <a href="<?= site_url('admin') ?>" class="text-lg font-bold tracking-widest">NOR<span class="text-brand-red">LANKA</span></a>
            <span class="ml-2 rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-white/50">Admin</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4"><?php $renderNav(); ?></nav>
        <div class="flex-none border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-red/20 text-sm font-bold text-brand-red">
                    <?= esc(strtoupper(substr((string) ($user['email'] ?? 'A'), 0, 1))) ?>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium"><?= esc($user['email'] ?? '') ?></p>
                    <a href="<?= site_url('admin/logout') ?>" class="text-xs text-white/40 hover:text-brand-red">Sign out</a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Sidebar (mobile drawer) -->
    <div class="fixed inset-0 z-40 lg:hidden" x-show="open" x-cloak style="display:none">
        <div class="absolute inset-0 bg-black/70" @click="open = false"></div>
        <aside class="absolute inset-y-0 left-0 flex w-72 flex-col border-r border-white/10 bg-brand-black">
            <div class="flex h-16 flex-none items-center justify-between border-b border-white/10 px-5">
                <a href="<?= site_url('admin') ?>" class="text-lg font-bold tracking-widest">NOR<span class="text-brand-red">LANKA</span></a>
                <button @click="open = false" class="rounded-lg p-2 text-white/60 hover:bg-white/10" aria-label="Close menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-4"><?php $renderNav(); ?></nav>
        </aside>
    </div>

    <!-- Main -->
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 flex-none items-center justify-between border-b border-white/10 bg-brand-black/90 px-4 backdrop-blur sm:px-6">
            <div class="flex items-center gap-3">
                <button @click="open = true" class="rounded-lg p-2 text-white/60 hover:bg-white/10 lg:hidden" aria-label="Open menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-lg font-semibold"><?= esc($title ?? 'Admin') ?></h1>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <button type="button" id="theme-toggle" class="rounded-lg border border-white/15 p-2 text-white/70 transition hover:border-white/40 hover:text-white" aria-label="Toggle light/dark mode">
                    <svg class="theme-icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg class="theme-icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                </button>
                <a href="<?= site_url() ?>" target="_blank" class="hidden items-center gap-1.5 rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white/70 transition hover:border-white/40 hover:text-white sm:flex">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6m4-3h6v6m-11 5L21 3"/></svg>
                    View site
                </a>
                <a href="<?= site_url('admin/logout') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white/70 transition hover:border-brand-red hover:text-white">Sign out</a>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <?php if (session('message')): ?>
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3"/></svg>
                    <?= esc(session('message')) ?>
                </div>
            <?php endif; ?>
            <?php if (session('error')): ?>
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm text-brand-red">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4m0 4h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
                    <?= esc(session('error')) ?>
                </div>
            <?php endif; ?>
            <?php if (session('errors') && is_array(session('errors'))): ?>
                <div class="mb-6 rounded-xl border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm text-brand-red">
                    <ul class="list-inside list-disc">
                        <?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>
</body>
</html>
