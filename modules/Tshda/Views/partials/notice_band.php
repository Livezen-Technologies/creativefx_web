<?php
helper(['norlanka', 'url']);

/**
 * Priority notices (Clause 3.9 B.IV): a highlighted region for matters needing
 * immediate public attention.
 *
 * It sits above everything else on the page and uses its own colour scope, not
 * the site's green — an alert that looks like the rest of the site is not an
 * alert. Marked as a live region so a screen reader announces one that appears
 * without the reader having to go looking for it.
 *
 * @var list<array> $notices
 */
$notices = $notices ?? [];
if ($notices === []) {
    return;
}
?>
<section class="theme-notice border-b border-line bg-brand-black" aria-label="<?= esc(lang('Site.home.notices'), 'attr') ?>">
    <div class="container-x py-4">
        <ul class="space-y-2.5" role="list">
            <?php foreach ($notices as $notice):
                $url    = trim((string) ($notice['url'] ?? ''));
                $href   = $url === '' ? '' : (preg_match('~^(https?:|/)~i', $url) ? $url : locale_url($url));
                $urgent = ($notice['severity'] ?? '') === 'urgent';
                $body   = t_field($notice['body'] ?? '');
            ?>
                <li class="flex flex-wrap items-start gap-x-3 gap-y-1">
                    <span class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= $urgent ? 'bg-brand-red text-white' : 'bg-brand-red/15 text-brand-red' ?>">
                        <?= esc($urgent ? lang('Site.home.urgent') : lang('Site.home.notice')) ?>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold leading-snug">
                            <?php if ($href !== ''): ?>
                                <a href="<?= esc($href) ?>" class="underline decoration-brand-red/40 underline-offset-4 transition hover:decoration-brand-red"><?= esc(t_field($notice['title'])) ?></a>
                            <?php else: ?>
                                <?= esc(t_field($notice['title'])) ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($body !== ''): ?>
                            <p class="mt-0.5 text-sm leading-relaxed text-white/70"><?= esc($body) ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
