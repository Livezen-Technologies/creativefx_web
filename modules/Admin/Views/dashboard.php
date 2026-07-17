<?php helper(['url', 'norlanka']); $this->extend('Modules\Admin\Views\layout'); ?>
<?= $this->section('content') ?>
<div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
    <?php foreach ($widgets as [$label, $count, $path]): ?>
        <a href="<?= site_url($path) ?>" class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 transition hover:border-brand-red/60">
            <div class="text-3xl font-bold text-brand-red"><?= esc($count) ?></div>
            <div class="mt-2 text-sm font-medium"><?= esc($label) ?></div>
        </a>
    <?php endforeach; ?>
</div>

<?php if (! empty($applications)): ?>
<div class="mt-10">
    <h2 class="mb-4 text-lg font-semibold">Recent applications</h2>
    <div class="overflow-hidden rounded-xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
                <tr><th class="px-4 py-3">Applicant</th><th class="px-4 py-3">Position</th><th class="px-4 py-3">Applied</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($applications as $a): ?>
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3"><a href="<?= site_url('admin/applications/' . $a['id']) ?>" class="hover:text-brand-red"><?= esc($a['name']) ?></a></td>
                        <td class="px-4 py-3 text-white/70"><?= esc(t_field(json_decode($a['job_title'] ?? '[]', true) ?: [])) ?></td>
                        <td class="px-4 py-3 text-white/50"><?= esc(date('j M Y', strtotime($a['created_at']))) ?></td>
                        <td class="px-4 py-3"><span class="rounded-full bg-white/10 px-2 py-0.5 text-xs capitalize"><?= esc($a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="mt-10">
    <h2 class="mb-4 text-lg font-semibold">Recent contact messages</h2>
    <div class="overflow-hidden rounded-xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-white/50">
                <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($recent as $r): ?>
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3"><a href="<?= site_url('admin/contacts/' . $r['id'] . '/edit') ?>" class="hover:text-brand-red"><?= esc($r['name']) ?></a></td>
                        <td class="px-4 py-3 text-white/70"><?= esc($r['email']) ?></td>
                        <td class="px-4 py-3 text-white/70"><?= esc($r['subject']) ?></td>
                        <td class="px-4 py-3"><span class="rounded-full bg-white/10 px-2 py-0.5 text-xs"><?= esc($r['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recent === []): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-white/40">No messages yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
