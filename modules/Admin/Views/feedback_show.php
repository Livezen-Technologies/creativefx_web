<?php
helper(['url', 'norlanka']);
$this->extend('Modules\Admin\Views\layout');
?>
<?= $this->section('content') ?>

<p class="mb-6"><a href="<?= site_url('admin/feedback') ?>" class="text-sm font-semibold text-brand-red hover:underline">&larr; All submissions</a></p>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="min-w-0 space-y-6">
        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <p class="font-mono text-xs text-white/50"><?= esc($submission['reference']) ?></p>
            <h2 class="mt-1 text-lg font-semibold"><?= esc($submission['subject']) ?></h2>
            <p class="mt-4 whitespace-pre-line rounded-lg border border-white/10 bg-black/20 p-4 text-sm leading-relaxed text-white/80"><?= esc($submission['message']) ?></p>

            <?php if (! empty($submission['attachment'])): ?>
                <p class="mt-4">
                    <a href="<?= site_url('admin/feedback/' . (int) $submission['id'] . '/attachment') ?>"
                       class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold transition hover:border-brand-red hover:text-brand-red">
                        Download the attachment
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= site_url('admin/feedback/' . (int) $submission['id']) ?>"
              class="space-y-5 rounded-2xl border border-white/10 bg-white/[0.02] p-6">
            <?= csrf_field() ?>
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Status</span>
                    <select name="status" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= esc($s, 'attr') ?>" <?= $submission['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Responsible division</span>
                    <input type="text" name="division" value="<?= esc($submission['division'], 'attr') ?>" maxlength="64"
                           class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm">
                </label>
            </div>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">Response</span>
                <textarea name="response" rows="7" class="w-full rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm leading-relaxed"><?= esc($submission['response']) ?></textarea>
                <span class="mt-1.5 block text-xs text-white/45">
                    Saving a response emails it to the person who submitted, if they gave an address, and shows it against the
                    reference on the public tracking page.
                </span>
            </label>
            <button type="submit" class="rounded-lg bg-brand-red px-5 py-2.5 text-sm font-semibold text-white">Save</button>
        </form>
    </div>

    <aside>
        <dl class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 text-sm">
            <?php foreach ([
                'Type'      => ucfirst((string) $submission['kind']),
                'From'      => $submission['name'],
                'Email'     => $submission['email'],
                'Telephone' => $submission['phone'],
                'District'  => $submission['district'],
                'Language'  => strtoupper((string) $submission['locale']),
                'Received'  => date('j M Y H:i', strtotime((string) $submission['created_at'])),
                'Due'       => empty($submission['due_at']) ? '' : date('j M Y', strtotime((string) $submission['due_at'])),
                'Answered'  => empty($submission['answered_at']) ? '' : date('j M Y', strtotime((string) $submission['answered_at'])),
                'From page' => $submission['page_url'],
            ] as $label => $value): ?>
                <?php if (trim((string) $value) === '') { continue; } ?>
                <dt class="mt-4 text-xs uppercase tracking-widest text-white/40 first:mt-0"><?= esc($label) ?></dt>
                <dd class="mt-0.5 break-words text-white/80"><?= esc((string) $value) ?></dd>
            <?php endforeach; ?>
        </dl>
    </aside>
</div>

<?= $this->endSection() ?>
