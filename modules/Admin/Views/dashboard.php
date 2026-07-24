<?php
helper(['url', 'norlanka']);
$this->extend('Modules\Admin\Views\layout');

$icons = [
    'users'     => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m22 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
    'inbox'     => 'M22 12h-6l-2 3h-4l-2-3H2m3.5-7 -3.5 7v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-7a2 2 0 0 0-1.8-1H7.3a2 2 0 0 0-1.8 1Z',
    'target'    => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm0-6a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
    'briefcase' => 'M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Zm0 4h18',
    'news'      => 'M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0V9m14-3h-6m6 4h-6m6 4H8m10 4H8',
    'image'     => 'M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM9 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm12 5-3.5-3.5a2 2 0 0 0-3 0L6 21',
    'plus'      => 'M12 5v14M5 12h14',
    'upload'    => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12',
    'edit'      => 'M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3Z',
];
$icon = static fn (string $k, string $cls = 'h-5 w-5'): string => '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . ($icons[$k] ?? '') . '"/></svg>';

$chip = static function (string $status): string {
    $map = [
        'new'         => 'bg-brand-red/15 text-brand-red',
        'read'        => 'bg-white/10 text-white/60',
        'reviewing'   => 'bg-sky-500/15 text-sky-300',
        'shortlisted' => 'bg-emerald-500/15 text-emerald-300',
        'interviewed' => 'bg-violet-500/15 text-violet-300',
        'hired'       => 'bg-emerald-500/20 text-emerald-300',
        'offered'     => 'bg-amber-500/15 text-amber-300',
        'rejected'    => 'bg-white/8 text-white/40',
        'archived'    => 'bg-white/8 text-white/40',
        'replied'     => 'bg-emerald-500/15 text-emerald-300',
    ];
    $cls = $map[$status] ?? 'bg-white/10 text-white/60';
    return '<span class="rounded-full ' . $cls . ' px-2.5 py-0.5 text-[11px] font-semibold capitalize">' . esc($status) . '</span>';
};

$fmtBytes = static function (int $b): string {
    if ($b >= 1073741824) { return number_format($b / 1073741824, 1) . ' GB'; }
    if ($b >= 1048576) { return number_format($b / 1048576, 1) . ' MB'; }
    return number_format(max($b, 0) / 1024, 0) . ' KB';
};

$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$userName = trim((string) (session()->get('admin_user')['first_name'] ?? '')) ?: 'there';
?>
<?= $this->section('content') ?>

<!-- Greeting + quick actions -->
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold"><?= esc($greeting) ?>, <?= esc($userName) ?> 👋</h2>
        <p class="mt-1 text-sm text-white/50"><?= esc(date('l, j F Y')) ?> — here's what's happening across the site.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= site_url('admin/news-posts/new') ?>" class="flex items-center gap-1.5 rounded-lg bg-brand-red px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-brand-red/85"><?= $icon('plus', 'h-3.5 w-3.5') ?> News post</a>
        <a href="<?= site_url('admin/jobs/new') ?>" class="flex items-center gap-1.5 rounded-lg border border-white/15 px-3.5 py-2 text-xs font-semibold text-white/80 transition hover:border-white/40"><?= $icon('briefcase', 'h-3.5 w-3.5') ?> Post a job</a>
        <a href="<?= site_url('admin/media') ?>" class="flex items-center gap-1.5 rounded-lg border border-white/15 px-3.5 py-2 text-xs font-semibold text-white/80 transition hover:border-white/40"><?= $icon('upload', 'h-3.5 w-3.5') ?> Upload media</a>
        <a href="<?= site_url('admin/pages') ?>" class="flex items-center gap-1.5 rounded-lg border border-white/15 px-3.5 py-2 text-xs font-semibold text-white/80 transition hover:border-white/40"><?= $icon('edit', 'h-3.5 w-3.5') ?> Edit pages</a>
    </div>
</div>

<!-- KPI cards -->
<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
    <?php foreach ($kpis as $k): ?>
        <a href="<?= site_url($k['path']) ?>" class="group rounded-2xl border border-white/10 bg-white/[0.02] p-5 transition hover:border-brand-red/50 hover:bg-white/[0.04]">
            <div class="flex items-center justify-between">
                <span class="text-white/35 transition group-hover:text-brand-red"><?= $icon($k['icon']) ?></span>
                <?php if (($k['week'] ?? null) !== null && $k['week'] > 0): ?>
                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-300">+<?= esc($k['week']) ?> this week</span>
                <?php endif; ?>
            </div>
            <div class="mt-3 text-3xl font-bold tabular-nums"><?= esc(number_format($k['total'])) ?></div>
            <div class="mt-1 text-xs font-medium text-white/50"><?= esc($k['label']) ?></div>
        </a>
    <?php endforeach; ?>
</div>

<!-- Chart + pipeline row -->
<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <!-- 14-day activity chart -->
    <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 xl:col-span-2">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Activity — last 14 days</h3>
            <div class="flex items-center gap-4 text-[11px] text-white/50">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-red"></span> Applications</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-white/35"></span> Messages</span>
            </div>
        </div>
        <?php
        $max = 1;
        foreach ($days as $d) { $max = max($max, $d['apps'], $d['contacts']); }
        ?>
        <div class="flex h-40 items-end gap-1.5 sm:gap-2.5">
            <?php foreach ($days as $d): ?>
                <div class="group relative flex h-full flex-1 items-end justify-center gap-[3px]" title="<?= esc($d['label'] . ': ' . $d['apps'] . ' applications, ' . $d['contacts'] . ' messages', 'attr') ?>">
                    <div class="w-1/2 max-w-[14px] rounded-t bg-brand-red/80 transition group-hover:bg-brand-red" style="height: <?= max(4, (int) round($d['apps'] / $max * 100)) ?>%"></div>
                    <div class="w-1/2 max-w-[14px] rounded-t bg-white/25 transition group-hover:bg-white/45" style="height: <?= max(4, (int) round($d['contacts'] / $max * 100)) ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-2 flex justify-between text-[10px] text-white/35">
            <span><?= esc($days[0]['label'] ?? '') ?></span>
            <span><?= esc($days[(int) (count($days) / 2)]['label'] ?? '') ?></span>
            <span>Today</span>
        </div>
    </div>

    <!-- Application pipeline + storage -->
    <div class="flex flex-col gap-6">
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-widest text-white/60">Application pipeline</h3>
            <?php
            $stages = ['new' => 'bg-brand-red', 'reviewing' => 'bg-sky-400', 'shortlisted' => 'bg-emerald-400', 'interviewed' => 'bg-violet-400', 'hired' => 'bg-emerald-300', 'rejected' => 'bg-white/20'];
            $totalApps = array_sum($pipeline) ?: 0;
            ?>
            <?php if ($totalApps > 0): ?>
                <div class="flex h-2.5 overflow-hidden rounded-full bg-white/5">
                    <?php foreach ($stages as $stage => $color): $n = $pipeline[$stage] ?? 0; if (! $n) { continue; } ?>
                        <div class="<?= $color ?>" style="width: <?= round($n / $totalApps * 100, 1) ?>%"></div>
                    <?php endforeach; ?>
                </div>
                <ul class="mt-4 space-y-2">
                    <?php foreach ($stages as $stage => $color): $n = $pipeline[$stage] ?? 0; if (! $n && $stage !== 'new') { continue; } ?>
                        <li class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 capitalize text-white/65"><span class="h-2 w-2 rounded-full <?= $color ?>"></span><?= esc($stage) ?></span>
                            <span class="font-semibold tabular-nums"><?= esc($n) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= site_url('admin/applications') ?>" class="mt-4 inline-block text-xs font-semibold text-brand-red hover:underline">Review applications →</a>
            <?php else: ?>
                <p class="text-sm text-white/40">No applications yet — they'll appear here as candidates apply.</p>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-widest text-white/60">Library &amp; catalog</h3>
            <ul class="space-y-2.5 text-sm">
                <li class="flex justify-between"><span class="text-white/55">Media storage</span><span class="font-semibold tabular-nums"><?= esc($fmtBytes($storage['bytes'])) ?> · <?= esc($storage['files']) ?> files</span></li>
                <li class="flex justify-between"><span class="text-white/55">Pages</span><span class="font-semibold tabular-nums"><?= esc($counts['pages']) ?></span></li>
                <li class="flex justify-between"><span class="text-white/55">Products</span><span class="font-semibold tabular-nums"><?= esc($counts['products']) ?></span></li>
                <li class="flex justify-between"><span class="text-white/55">Showroom products</span><span class="font-semibold tabular-nums"><?= esc($counts['showroom']) ?></span></li>
                <li class="flex justify-between"><span class="text-white/55">Jobs (all)</span><span class="font-semibold tabular-nums"><?= esc($counts['jobs']) ?></span></li>
            </ul>
        </div>
    </div>
</div>

<!-- Feeds row -->
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <!-- Recent applications -->
    <div class="rounded-2xl border border-white/10 bg-white/[0.02]">
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Recent applications</h3>
            <?php if (($counts['newApps'] ?? 0) > 0): ?>
                <span class="rounded-full bg-brand-red/15 px-2.5 py-0.5 text-[11px] font-bold text-brand-red"><?= esc($counts['newApps']) ?> new</span>
            <?php endif; ?>
        </div>
        <?php if ($applications === []): ?>
            <p class="px-6 py-10 text-center text-sm text-white/40">No applications yet.</p>
        <?php else: ?>
            <ul class="divide-y divide-white/5">
                <?php foreach ($applications as $a): ?>
                    <li>
                        <a href="<?= site_url('admin/applications/' . $a['id']) ?>" class="flex items-center gap-4 px-6 py-3.5 transition hover:bg-white/[0.03]">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/8 text-sm font-bold text-white/70"><?= esc(strtoupper(mb_substr((string) $a['name'], 0, 1))) ?></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium"><?= esc($a['name']) ?></span>
                                <span class="block truncate text-xs text-white/45"><?= esc(t_field(json_decode($a['job_title'] ?? '[]', true) ?: [])) ?: 'General application' ?> · <?= esc(date('j M', strtotime($a['created_at']))) ?></span>
                            </span>
                            <?= $chip((string) $a['status']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Recent messages -->
    <div class="rounded-2xl border border-white/10 bg-white/[0.02]">
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Recent contact messages</h3>
            <?php if (($counts['newMsgs'] ?? 0) > 0): ?>
                <span class="rounded-full bg-brand-red/15 px-2.5 py-0.5 text-[11px] font-bold text-brand-red"><?= esc($counts['newMsgs']) ?> new</span>
            <?php endif; ?>
        </div>
        <?php if ($recent === []): ?>
            <p class="px-6 py-10 text-center text-sm text-white/40">No messages yet.</p>
        <?php else: ?>
            <ul class="divide-y divide-white/5">
                <?php foreach ($recent as $r): ?>
                    <li>
                        <a href="<?= site_url('admin/contacts/' . $r['id'] . '/edit') ?>" class="flex items-center gap-4 px-6 py-3.5 transition hover:bg-white/[0.03]">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/8 text-sm font-bold text-white/70"><?= esc(strtoupper(mb_substr((string) $r['name'], 0, 1))) ?></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium"><?= esc($r['name']) ?> <span class="font-normal text-white/40">· <?= esc($r['email']) ?></span></span>
                                <span class="block truncate text-xs text-white/45"><?= esc($r['subject'] ?: '—') ?></span>
                            </span>
                            <?= $chip((string) $r['status']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Recently updated content -->
<div class="mt-6 rounded-2xl border border-white/10 bg-white/[0.02]">
    <div class="border-b border-white/10 px-6 py-4">
        <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Recently updated content</h3>
    </div>
    <?php if ($content === []): ?>
        <p class="px-6 py-10 text-center text-sm text-white/40">Nothing here yet.</p>
    <?php else: ?>
        <ul class="divide-y divide-white/5">
            <?php foreach ($content as $c):
                $href = $c['kind'] === 'news'
                    ? site_url('admin/news-posts/' . $c['id'] . '/edit')
                    : site_url('admin/pages/' . $c['id'] . '/content'); ?>
                <li>
                    <a href="<?= $href ?>" class="flex items-center gap-4 px-6 py-3 transition hover:bg-white/[0.03]">
                        <span class="rounded-md bg-white/8 px-2 py-1 text-[10px] font-bold uppercase tracking-widest text-white/50"><?= esc($c['kind']) ?></span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium"><?= esc(t_field(json_decode($c['title'] ?? '[]', true) ?: []) ?: $c['slug']) ?></span>
                        <span class="hidden text-xs text-white/40 sm:block"><?= esc($c['updated_at'] ? date('j M Y, H:i', strtotime($c['updated_at'])) : '') ?></span>
                        <?= $chip((string) $c['status']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
