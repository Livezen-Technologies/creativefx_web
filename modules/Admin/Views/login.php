<?php helper('url'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(setting('site_name', '')) ?> Admin — Sign in</title>
    <?= vite_tags('resources/js/app.js') ?>
</head>
<body class="on-dark flex min-h-screen items-center justify-center bg-brand-black font-sans text-white antialiased">
<script>if (localStorage.getItem('admin-theme') === 'light') { document.body.classList.remove('on-dark'); }</script>
    <div class="hero-aurora pointer-events-none fixed inset-0 -z-10 opacity-40"></div>

    <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-white/[0.03] p-8 backdrop-blur">
        <div class="mb-6 text-center">
            <span class="text-2xl font-bold tracking-widest">NOR<span class="text-brand-red">LANKA</span></span>
            <p class="mt-1 text-xs uppercase tracking-widest text-white/40">Admin sign in</p>
        </div>

        <?php if (session('error')): ?>
            <p class="mb-4 rounded-lg bg-brand-red/15 px-3 py-2 text-sm text-brand-red"><?= esc(session('error')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= site_url('admin/login') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="text-xs uppercase tracking-widest text-white/50">Email</label>
                <input type="email" name="email" value="admin@norlanka.local" required
                       class="mt-1 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest text-white/50">Password</label>
                <input type="password" name="password" value="norlanka123" required
                       class="mt-1 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
            </div>
            <button type="submit" class="btn-brand w-full">Sign in</button>
        </form>

        <p class="mt-6 text-center text-[11px] text-white/30">Dev credentials are pre-filled for this foundation build.</p>
    </div>
</body>
</html>
