<?php helper('url'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Norlanka Admin — Sign in</title>
    <?= vite_tags('resources/js/app.js') ?>
</head>
<body class="flex min-h-screen items-center justify-center bg-brand-black font-sans text-white antialiased">
    <div class="hero-aurora pointer-events-none fixed inset-0 -z-10 opacity-40"></div>

    <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-white/[0.03] p-8 backdrop-blur"
         x-data="{
            email: 'admin@norlanka.local', password: 'norlanka123',
            loading: false, error: '', token: '', user: null,
            async submit() {
                this.loading = true; this.error = '';
                try {
                    const res = await fetch('/api/auth/login', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new URLSearchParams({ email: this.email, password: this.password })
                    });
                    const data = await res.json();
                    this.loading = false;
                    if (res.ok && data.token) {
                        this.token = data.token; this.user = data.user;
                        localStorage.setItem('norlanka_token', data.token);
                    } else {
                        this.error = (data.messages && data.messages.error) || data.message || 'Login failed';
                    }
                } catch (e) { this.loading = false; this.error = 'Network error'; }
            }
         }">
        <div class="mb-6 text-center">
            <span class="text-2xl font-bold tracking-widest">NOR<span class="text-brand-red">LANKA</span></span>
            <p class="mt-1 text-xs uppercase tracking-widest text-white/40">Admin sign in</p>
        </div>

        <template x-if="!token">
            <form @submit.prevent="submit()" class="space-y-4">
                <div>
                    <label class="text-xs uppercase tracking-widest text-white/50">Email</label>
                    <input type="email" x-model="email" class="mt-1 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
                </div>
                <div>
                    <label class="text-xs uppercase tracking-widest text-white/50">Password</label>
                    <input type="password" x-model="password" class="mt-1 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
                </div>
                <p x-show="error" x-text="error" class="text-sm text-brand-red" x-cloak></p>
                <button type="submit" class="btn-brand w-full" :disabled="loading">
                    <span x-text="loading ? 'Signing in…' : 'Sign in'"></span>
                </button>
            </form>
        </template>

        <template x-if="token">
            <div class="space-y-3 text-center">
                <p class="text-sm text-emerald-400">Authenticated — JWT acquired.</p>
                <p class="text-xs text-white/50">Signed in as <span x-text="user && user.email"></span></p>
                <p class="break-all rounded-lg bg-black/40 p-3 text-[10px] text-white/40" x-text="token"></p>
                <p class="text-xs text-white/40">Token stored in localStorage. The dashboard API accepts it as a Bearer token.</p>
            </div>
        </template>

        <p class="mt-6 text-center text-[11px] text-white/30">Dev credentials are pre-filled for this foundation build.</p>
    </div>
</body>
</html>
