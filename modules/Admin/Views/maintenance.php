<?php
helper('norlanka');
$this->extend('Modules\Admin\Views\layout');

/**
 * @var array{active:bool, flag:bool, env:bool, since:string, until:string,
 *            headline:string, message:string, bypass_key:string,
 *            allow_ips:string, retry_after:int} $state
 * @var string $bypassKey
 * @var string $yourIp
 */
$inputCls = 'w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
$labelCls = 'mb-1 block text-xs uppercase tracking-widest text-white/50';

$previewUrl = rtrim(site_url(), '/') . '/?preview=' . $bypassKey;
?>
<?= $this->section('content') ?>

<div class="max-w-3xl space-y-8">

    <!-- The switch -->
    <section class="rounded-xl border <?= $state['active'] ? 'border-brand-red/50 bg-brand-red/10' : 'border-white/10' ?> p-6">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-2.5 w-2.5">
                        <?php if ($state['active']): ?>
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-red opacity-75"></span>
                        <?php endif; ?>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full <?= $state['active'] ? 'bg-brand-red' : 'bg-emerald-400' ?>"></span>
                    </span>
                    <h2 class="text-lg font-semibold">
                        <?= $state['active'] ? 'Site is offline' : 'Site is live' ?>
                    </h2>
                </div>
                <p class="mt-2 max-w-md text-sm text-white/55">
                    <?php if ($state['active']): ?>
                        Visitors get the offline page and a 503, which tells search engines to
                        come back later rather than drop the pages. You and anyone with the
                        preview link below still see the real site.
                    <?php else: ?>
                        Switching on serves every visitor the offline page instead of the site.
                        This admin panel stays reachable, so you can always switch back.
                    <?php endif; ?>
                </p>
                <?php if ($state['active'] && $state['since'] !== ''): ?>
                    <p class="mt-3 text-xs text-white/40">Offline since <?= esc($state['since']) ?></p>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= site_url('admin/maintenance/toggle') ?>" class="flex-none"
                  <?= $state['active'] ? '' : 'onsubmit="return confirm(\'Take the public site offline now?\')"' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="state" value="<?= $state['active'] ? 'off' : 'on' ?>">
                <button type="submit" class="rounded-lg px-5 py-2.5 text-sm font-semibold transition <?= $state['active']
                    ? 'bg-emerald-500 text-black hover:bg-emerald-400'
                    : 'bg-brand-red text-brand-ink hover:bg-brand-red/85' ?>">
                    <?= $state['active'] ? 'Bring the site back online' : 'Take the site offline' ?>
                </button>
            </form>
        </div>

        <?php if ($state['env']): ?>
            <p class="mt-5 rounded-lg border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-xs text-amber-200">
                <strong>maintenance.enabled is set in .env on this server</strong> and overrides this
                button. Remove that line to hand control back to this screen.
            </p>
        <?php endif; ?>
    </section>

    <!-- Preview link -->
    <section class="rounded-xl border border-white/10 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-widest text-brand-red">Preview link</h2>
        <p class="mt-2 text-sm text-white/55">
            Opening this once lets that browser through for a day, while everyone else
            still sees the offline page. Share it with a client or a colleague who needs
            to review the live site during the outage.
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <input type="text" readonly value="<?= esc($previewUrl) ?>" id="preview-url"
                   class="<?= $inputCls ?> min-w-0 flex-1 font-mono text-xs text-white/70">
            <button type="button" id="copy-preview"
                    class="flex-none rounded-lg border border-white/15 px-4 py-2 text-xs transition hover:border-brand-red hover:text-white">
                Copy
            </button>
            <form method="post" action="<?= site_url('admin/maintenance/key') ?>" class="flex-none"
                  onsubmit="return confirm('Generate a new link? The current one stops working immediately.')">
                <?= csrf_field() ?>
                <button type="submit" class="rounded-lg border border-white/15 px-4 py-2 text-xs transition hover:border-brand-red hover:text-white">
                    New link
                </button>
            </form>
        </div>
    </section>

    <!-- What visitors see -->
    <form method="post" action="<?= site_url('admin/maintenance') ?>" class="space-y-8">
        <?= csrf_field() ?>

        <fieldset class="rounded-xl border border-white/10 p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">What visitors see</legend>
            <p class="mb-5 text-xs text-white/40">
                Leave the headline and message empty to use the built-in wording, which is
                already written in every site language. Anything you type here replaces
                it for every visitor, in that one language.
            </p>

            <div class="space-y-5">
                <div>
                    <label class="<?= $labelCls ?>" for="brand">Name on the page</label>
                    <input type="text" id="brand" name="brand" value="<?= esc($state['brand']) ?>"
                           placeholder="<?= esc(setting('site_name', 'CreativeFX'), 'attr') ?>" class="<?= $inputCls ?>">
                    <p class="mt-1 text-xs text-white/35">Defaults to the site name from Settings.</p>
                </div>

                <div>
                    <label class="<?= $labelCls ?>" for="headline">Headline</label>
                    <input type="text" id="headline" name="headline" value="<?= esc($state['headline']) ?>"
                           placeholder="We will be back shortly" class="<?= $inputCls ?>">
                </div>

                <div>
                    <label class="<?= $labelCls ?>" for="message">Message</label>
                    <textarea id="message" name="message" rows="3" class="<?= $inputCls ?>"
                              placeholder="Our site is briefly offline while we make some improvements…"><?= esc($state['message']) ?></textarea>
                </div>

                <div>
                    <label class="<?= $labelCls ?>" for="until">Expected back</label>
                    <input type="text" id="until" name="until" value="<?= esc($state['until']) ?>"
                           placeholder="18 August, 09:00 (GMT+5:30)" class="<?= $inputCls ?>">
                    <p class="mt-1 text-xs text-white/35">Shown exactly as typed. Leave empty to say nothing.</p>
                </div>
            </div>
        </fieldset>

        <fieldset class="rounded-xl border border-white/10 p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">Access &amp; crawlers</legend>
            <div class="space-y-5">
                <div>
                    <label class="<?= $labelCls ?>" for="allow_ips">Always allow these IP addresses</label>
                    <input type="text" id="allow_ips" name="allow_ips" value="<?= esc($state['allow_ips']) ?>"
                           placeholder="203.0.113.10, 203.0.113.11" class="<?= $inputCls ?>">
                    <p class="mt-1 text-xs text-white/35">
                        Comma separated — an office or VPN address browses the site as normal.
                        You are on <span class="font-mono text-white/60"><?= esc($yourIp) ?></span>.
                    </p>
                </div>

                <div>
                    <label class="<?= $labelCls ?>" for="retry_after">Retry-After (seconds)</label>
                    <input type="number" id="retry_after" name="retry_after" min="60" step="60"
                           value="<?= (int) $state['retry_after'] ?>" class="<?= $inputCls ?> max-w-[12rem]">
                    <p class="mt-1 text-xs text-white/35">
                        How long crawlers are asked to wait before trying again. One hour by default.
                    </p>
                </div>
            </div>
        </fieldset>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded-lg bg-brand-red px-5 py-2.5 text-sm font-semibold text-brand-ink transition hover:bg-brand-red/85">
                Save
            </button>
            <a href="<?= site_url('admin/maintenance/preview') ?>" target="_blank" rel="noopener"
               class="rounded-lg border border-white/15 px-4 py-2.5 text-sm text-white/70 transition hover:border-brand-red hover:text-white">
                Preview the offline page
            </a>
        </div>
    </form>

    <p class="text-xs text-white/35">
        From the server the same switch is <span class="font-mono text-white/55">php spark maintenance on|off|status</span>,
        and <span class="font-mono text-white/55">touch writable/maintenance.flag</span> works even with the database down.
    </p>
</div>

<script>
document.getElementById('copy-preview')?.addEventListener('click', async (e) => {
    const input = document.getElementById('preview-url');
    try {
        await navigator.clipboard.writeText(input.value);
    } catch (_) {
        input.select();
        document.execCommand('copy');
    }
    e.target.textContent = 'Copied';
    setTimeout(() => { e.target.textContent = 'Copy'; }, 1500);
});
</script>
<?= $this->endSection() ?>
