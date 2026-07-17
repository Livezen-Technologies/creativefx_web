<?php
helper('url');
$user   = session()->get('admin_user') ?? [];
$active = $active ?? '';
$nav = [
    ['dashboard', 'Dashboard', 'admin'],
    ['pages', 'Pages', 'admin/pages'],
    ['news-posts', 'News Posts', 'admin/news-posts'],
    ['news-categories', 'News Categories', 'admin/news-categories'],
    ['categories', 'Product Categories', 'admin/categories'],
    ['videos', 'Launch Video', 'admin/videos'],
    ['showroom-cats', 'Showroom Categories', 'admin/showroom-categories'],
    ['showroom-products', 'Showroom Products', 'admin/showroom-products'],
    ['esg', 'ESG Metrics', 'admin/esg-metrics'],
    ['jobs', 'Jobs', 'admin/jobs'],
    ['applications', 'Applications', 'admin/applications'],
    ['translations', 'Translations', 'admin/translations'],
    ['media', 'Media', 'admin/media'],
    ['contacts', 'Contact Inbox', 'admin/contacts'],
    ['leads', 'Leads', 'admin/leads'],
    ['settings', 'Settings', 'admin/settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin') ?> — Norlanka Admin</title>
    <?= vite_tags('resources/js/app.js') ?>
</head>
<body class="on-dark min-h-screen bg-[#0b0b0c] font-sans text-white antialiased">
<div class="flex min-h-screen" x-data="{ open: false }">
    <!-- Sidebar -->
    <aside class="hidden w-60 flex-none border-r border-white/10 bg-brand-black lg:block">
        <div class="flex h-16 items-center px-6">
            <a href="<?= site_url('admin') ?>" class="text-lg font-bold tracking-widest">NOR<span class="text-brand-red">LANKA</span></a>
        </div>
        <nav class="px-3 py-4">
            <?php foreach ($nav as [$key, $label, $path]): ?>
                <a href="<?= site_url($path) ?>"
                   class="mb-1 block rounded-lg px-3 py-2 text-sm transition <?= $active === $key ? 'bg-brand-red text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' ?>"><?= esc($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Main -->
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-16 items-center justify-between border-b border-white/10 px-6">
            <h1 class="text-lg font-semibold"><?= esc($title ?? 'Admin') ?></h1>
            <div class="flex items-center gap-4 text-sm">
                <a href="<?= site_url() ?>" target="_blank" class="text-white/50 hover:text-white">View site ↗</a>
                <span class="hidden text-white/50 sm:inline"><?= esc($user['email'] ?? '') ?></span>
                <a href="<?= site_url('admin/logout') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs hover:border-white">Sign out</a>
            </div>
        </header>

        <main class="flex-1 p-6 lg:p-8">
            <?php if (session('message')): ?>
                <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"><?= esc(session('message')) ?></div>
            <?php endif; ?>
            <?php if (session('error')): ?>
                <div class="mb-6 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm text-brand-red"><?= esc(session('error')) ?></div>
            <?php endif; ?>
            <?php if (session('errors') && is_array(session('errors'))): ?>
                <div class="mb-6 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm text-brand-red">
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
