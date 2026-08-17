<?php helper(['url', 'norlanka']); $this->extend('Modules\Admin\Views\layout');
$jobTitle = t_field(json_decode($row['job_title'] ?? '[]', true) ?: []);
?>
<?= $this->section('content') ?>

<a href="<?= site_url('admin/applications') ?>" class="text-xs uppercase tracking-widest text-white/50 hover:text-white">← All applications</a>

<div class="mt-4 grid gap-6 lg:grid-cols-3">
    <!-- Applicant profile -->
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold"><?= esc($row['name']) ?></h2>
                    <p class="mt-1 text-sm text-white/60">Applied for <span class="text-brand-red"><?= esc($jobTitle) ?></span> · <?= esc(date('j M Y, H:i', strtotime($row['created_at']))) ?></p>
                </div>
                <?php if ($row['resume_path']): ?>
                    <a href="<?= site_url('admin/applications/' . $row['id'] . '/cv') ?>" class="rounded-lg bg-brand-red px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-brand-ink hover:bg-brand-red-dark">Download CV</a>
                <?php endif; ?>
            </div>

            <dl class="mt-6 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                <?php
                $fields = [
                    'Email' => $row['email'], 'Phone' => $row['phone'], 'Country' => $row['country'],
                    'Education' => $row['education'], 'Experience' => $row['experience'], 'Skills' => $row['skills'],
                ];
                foreach ($fields as $label => $value): if (! $value) { continue; } ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40"><?= esc($label) ?></dt>
                        <dd class="mt-1 text-white/85"><?= esc($value) ?></dd>
                    </div>
                <?php endforeach; ?>
                <?php if ($row['linkedin']): ?>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-white/40">LinkedIn / Portfolio</dt>
                        <dd class="mt-1"><a href="<?= esc($row['linkedin'], 'attr') ?>" target="_blank" rel="noopener" class="text-brand-red hover:underline"><?= esc($row['linkedin']) ?></a></dd>
                    </div>
                <?php endif; ?>
            </dl>

            <?php if ($row['cover_letter']): ?>
                <h3 class="mt-8 text-xs uppercase tracking-widest text-white/40">Cover letter</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-white/75"><?= esc($row['cover_letter']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- HR pipeline controls -->
    <div>
        <form method="post" action="<?= site_url('admin/applications/' . $row['id']) ?>" class="rounded-2xl border border-white/10 bg-white/[0.02] p-7">
            <?= csrf_field() ?>
            <h3 class="text-sm font-semibold uppercase tracking-widest text-white/60">Pipeline</h3>

            <label class="mt-4 block text-xs uppercase tracking-widest text-white/40">Status</label>
            <select name="status" class="mt-2 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm capitalize focus:border-brand-red focus:outline-none">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= esc($s, 'attr') ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="mt-5 block text-xs uppercase tracking-widest text-white/40">Internal rating</label>
            <select name="rating" class="mt-2 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
                <option value="">— Unrated —</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= (int) ($row['rating'] ?? 0) === $i ? 'selected' : '' ?>><?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?></option>
                <?php endfor; ?>
            </select>

            <label class="mt-5 block text-xs uppercase tracking-widest text-white/40">Notes (HR-internal)</label>
            <textarea name="notes" rows="6" class="mt-2 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none" placeholder="Interview notes, next steps…"><?= esc($row['notes'] ?? '') ?></textarea>

            <button type="submit" class="mt-5 w-full rounded-lg bg-brand-red px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-brand-ink hover:bg-brand-red-dark">Save</button>
            <a href="mailto:<?= esc($row['email'], 'attr') ?>?subject=<?= rawurlencode('Your application — ' . $jobTitle . ' at Norlanka') ?>"
               class="mt-3 block rounded-lg border border-white/15 px-5 py-2.5 text-center text-xs font-semibold uppercase tracking-widest hover:border-white">Email candidate</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
