<?php
helper(['norlanka', 'url']);
$email    = setting('email', 'shankerv@viswakula.com', 'contact');
$phone    = setting('phone', '', 'contact');
$whatsapp = setting('whatsapp', '', 'contact');
$wa       = $whatsapp !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : '';
?>
<section class="bg-brand-black py-16">
    <div class="container-x grid gap-12 lg:grid-cols-2">
        <!-- Contact details -->
        <div data-gsap="reveal">
            <h2 class="text-3xl font-bold sm:text-4xl"><?= esc(t_field($content['title'] ?? ['en' => 'Get in touch'])) ?></h2>
            <?php if (! empty($content['intro'])): ?>
                <p class="mt-4 max-w-md text-white/70"><?= esc(t_field($content['intro'])) ?></p>
            <?php endif; ?>
            <dl class="mt-8 space-y-4 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-widest text-white/40">Email</dt>
                    <dd><a class="text-white hover:text-brand-red" href="mailto:<?= esc($email) ?>"><?= esc($email) ?></a></dd>
                </div>
                <?php if ($phone !== ''): ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40">Phone</dt>
                        <dd><a class="text-white hover:text-brand-red" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= esc($phone) ?></a></dd>
                    </div>
                <?php endif; ?>
                <?php if ($wa !== ''): ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40">WhatsApp</dt>
                        <dd><a class="text-white hover:text-brand-red" href="<?= esc($wa) ?>" target="_blank" rel="noopener"><?= esc($whatsapp) ?></a></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Form (posts JSON to /api/contact, no page reload) -->
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-8" data-gsap="reveal"
             x-data="{
                form: { name: '', email: '', subject: '', message: '', website: '' },
                loading: false, ok: false, error: '',
                async submit() {
                    this.loading = true; this.error = '';
                    try {
                        const res = await fetch('/api/contact', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: new URLSearchParams({ ...this.form, locale: document.documentElement.lang })
                        });
                        const data = await res.json();
                        this.loading = false;
                        if (res.ok && data.status === 'success') { this.ok = true; }
                        else { this.error = (data.messages && Object.values(data.messages)[0]) || data.message || 'Something went wrong.'; }
                    } catch (e) { this.loading = false; this.error = 'Network error.'; }
                }
             }">
            <template x-if="!ok">
                <form @submit.prevent="submit()" class="space-y-4">
                    <input type="text" x-model="form.website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <input type="text" x-model="form.name" required placeholder="<?= esc(lang('Site.contact.name')) ?>"
                               class="w-full rounded-lg border border-white/15 bg-white/5 px-3 py-2.5 text-sm focus:border-brand-red focus:outline-none">
                        <input type="email" x-model="form.email" required placeholder="<?= esc(lang('Site.contact.email')) ?>"
                               class="w-full rounded-lg border border-white/15 bg-white/5 px-3 py-2.5 text-sm focus:border-brand-red focus:outline-none">
                    </div>
                    <input type="text" x-model="form.subject" placeholder="<?= esc(lang('Site.contact.subject')) ?>"
                           class="w-full rounded-lg border border-white/15 bg-white/5 px-3 py-2.5 text-sm focus:border-brand-red focus:outline-none">
                    <textarea x-model="form.message" required rows="4" placeholder="<?= esc(lang('Site.contact.message')) ?>"
                              class="w-full rounded-lg border border-white/15 bg-white/5 px-3 py-2.5 text-sm focus:border-brand-red focus:outline-none"></textarea>
                    <p x-show="error" x-text="error" class="text-sm text-brand-red" x-cloak></p>
                    <button type="submit" class="btn-brand w-full" :disabled="loading">
                        <span x-text="loading ? '…' : '<?= esc(lang('Site.contact.send')) ?>'"></span>
                    </button>
                </form>
            </template>
            <template x-if="ok">
                <div class="py-10 text-center" x-cloak>
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/20 text-2xl text-emerald-400">&check;</div>
                    <p class="mt-4 font-semibold"><?= esc(lang('Site.contact.success')) ?></p>
                </div>
            </template>
        </div>
    </div>
</section>
