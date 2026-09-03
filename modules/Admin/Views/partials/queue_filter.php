<?php
helper('url');

/**
 * The row of status chips above a queue, each carrying its own count.
 *
 * The count is the point. A moderator opening this screen should be able to see
 * that there are four things waiting without clicking into anything, and an
 * empty queue should say so rather than look like a screen that failed to load.
 *
 * @var string $base     admin path, e.g. 'admin/feedback'
 * @var string $current  the selected status ('' = all)
 * @var list<string> $statuses
 * @var array<string,int> $counts
 * @var array<string,string> $extra  query parameters to preserve
 */
$counts = $counts ?? [];
$extra  = $extra ?? [];

$link = static function (?string $status) use ($base, $extra): string {
    $params = array_filter($extra, static fn ($v) => $v !== '' && $v !== null);
    if ($status !== null) {
        $params['status'] = $status;
    }

    return site_url($base) . ($params === [] ? '' : '?' . http_build_query($params));
};
?>
<div class="mb-6 flex flex-wrap gap-2">
    <a href="<?= esc($link(null), 'attr') ?>"
       class="rounded-full border px-4 py-1.5 text-sm font-medium transition <?= $current === '' ? 'border-brand-red bg-brand-red/15 text-white' : 'border-white/10 text-white/60 hover:text-white' ?>">
        All
        <?php if ($counts !== []): ?><span class="ml-1.5 text-xs text-white/40"><?= esc((string) array_sum($counts)) ?></span><?php endif; ?>
    </a>
    <?php foreach ($statuses as $status): ?>
        <a href="<?= esc($link($status), 'attr') ?>"
           class="rounded-full border px-4 py-1.5 text-sm font-medium capitalize transition <?= $current === $status ? 'border-brand-red bg-brand-red/15 text-white' : 'border-white/10 text-white/60 hover:text-white' ?>">
            <?= esc($status) ?>
            <?php if (isset($counts[$status])): ?>
                <span class="ml-1.5 text-xs <?= $counts[$status] > 0 ? 'text-brand-red' : 'text-white/40' ?>"><?= esc((string) $counts[$status]) ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
