<?php helper('url'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Norlanka Admin — Dashboard</title>
    <?= vite_tags('resources/js/app.js') ?>
</head>
<body class="min-h-screen bg-brand-black font-sans text-white antialiased">
    <header class="border-b border-white/10">
        <div class="container-x flex h-16 items-center justify-between">
            <span class="text-lg font-bold tracking-widest">NOR<span class="text-brand-red">LANKA</span> <span class="text-white/40">Admin</span></span>
            <span class="text-xs uppercase tracking-widest text-white/40">Foundation skeleton</span>
        </div>
    </header>

    <main class="container-x py-12">
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <p class="mt-2 max-w-2xl text-white/60">
            Modular admin surface. Each card maps to a CMS module; full CRUD screens
            (CMS builder, translation manager, video management, showroom, ESG, careers, CRM)
            are delivered in later milestones.
        </p>

        <div class="mt-10 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            <?php foreach ($widgets as [$label, $count, $module]): ?>
                <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
                    <div class="text-3xl font-bold text-brand-red"><?= esc($count) ?></div>
                    <div class="mt-2 text-sm font-medium"><?= esc($label) ?></div>
                    <div class="mt-1 text-[10px] uppercase tracking-widest text-white/40"><?= esc($module) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
