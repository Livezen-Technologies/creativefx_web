<?php
helper(['norlanka', 'url']);

/**
 * The hotel's contact details, beside the enquiry form.
 *
 * Every row renders only when its setting holds something. The email default
 * used to be a real address belonging to somebody at an unrelated company —
 * left over from whatever this template was first written for — which meant one
 * empty setting away from publishing a stranger's inbox as the hotel's. A blank
 * default is the safe one: nothing to show, so nothing is shown.
 */
$email    = setting('email', '', 'contact');
$phone    = setting('phone', '', 'contact');
$phoneAlt = setting('phone_alt', '', 'contact');
$address  = setting('address', '', 'contact');
$whatsapp = setting('whatsapp', '', 'contact');
$wa       = $whatsapp !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : '';

$tel = static fn (string $n): string => preg_replace('/[^0-9+]/', '', $n);
?>
<section class="bg-brand-black py-16">
    <div class="container-x grid gap-12 lg:grid-cols-2">
        <!-- Contact details -->
        <div data-gsap="reveal">
            <h2 class="text-3xl font-bold sm:text-4xl"><?= esc(t_field($content['title'] ?? ['en' => 'Get in touch'])) ?></h2>
            <?php if (! empty($content['intro'])): ?>
                <p class="mt-4 max-w-md text-white/70"><?= esc(t_field($content['intro'])) ?></p>
            <?php endif; ?>
            <dl class="mt-8 space-y-5 text-sm">
                <?php if ($address !== ''): ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40">Address</dt>
                        <dd class="mt-1 leading-relaxed"><?= esc($address) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if ($phone !== '' || $phoneAlt !== ''): ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40">Phone</dt>
                        <?php foreach (array_filter([$phone, $phoneAlt]) as $n): ?>
                            <dd class="mt-1"><a class="text-white hover:text-brand-red" href="tel:<?= esc($tel($n), 'attr') ?>"><?= esc($n) ?></a></dd>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($wa !== ''): ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40">WhatsApp</dt>
                        <?php // The number is almost always one of the phone numbers
                              // directly above, so printing it again says nothing.
                              // What the reader wants here is the action. ?>
                        <dd class="mt-1">
                            <a class="inline-flex items-center gap-2 text-white hover:text-brand-red"
                               href="<?= esc($wa, 'attr') ?>" target="_blank" rel="noopener">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.33 4.97L2 22l5.25-1.38a9.87 9.87 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm0 18.15h-.01a8.2 8.2 0 01-4.18-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.2 8.2 0 01-1.26-4.38c0-4.54 3.7-8.23 8.24-8.23a8.2 8.2 0 015.82 2.42 8.18 8.18 0 012.41 5.82c0 4.54-3.7 8.23-8.24 8.23z"/>
                                </svg>
                                <?= esc(lang('Site.whatsapp.hint')) ?>
                            </a>
                        </dd>
                    </div>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40">Email</dt>
                        <dd class="mt-1"><a class="text-white hover:text-brand-red" href="mailto:<?= esc($email, 'attr') ?>"><?= esc($email) ?></a></dd>
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
