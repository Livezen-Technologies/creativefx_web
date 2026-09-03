<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [
        ['label' => lang('Site.feedback.title'), 'url' => locale_url('feedback')],
        ['label' => lang('Site.feedback.track_title')],
    ],
    'eyebrow' => lang('Site.nav.contact'),
    'heading' => lang('Site.feedback.track_title'),
    'intro'   => lang('Site.feedback.track_intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-2xl">
        <form method="get" class="flex flex-wrap items-end gap-3">
            <label class="min-w-[16rem] flex-1">
                <span class="field-label"><?= esc(lang('Site.feedback.track_label')) ?></span>
                <input type="text" name="reference" value="<?= esc($reference, 'attr') ?>" class="field font-mono" placeholder="FBK-0000-XXXXXX">
            </label>
            <button type="submit" class="btn-brand"><?= esc(lang('Site.feedback.track_go')) ?></button>
        </form>

        <?php if ($notFound): ?>
            <p role="alert" class="mt-8 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.feedback.track_none')) ?></p>
        <?php elseif ($submission !== null): ?>
            <?php
            // Deliberately not the citizen's own message, name or attachment.
            // A reference is enough to look this up, so anything shown here is
            // shown to anyone who guesses one — the status of a case is safe to
            // show; its contents are not.
            $status  = (string) $submission['status'];
            $overdue = in_array($status, ['open', 'assigned'], true)
                && ! empty($submission['due_at'])
                && $submission['due_at'] < date('Y-m-d H:i:s');
            ?>
            <div class="mt-8 rounded-2xl border border-line bg-surface p-6">
                <p class="font-mono text-sm text-white/60"><?= esc($submission['reference']) ?></p>
                <p class="mt-2 text-lg font-semibold"><?= esc($submission['subject']) ?></p>

                <dl class="mt-6 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.feedback.status')) ?></dt>
                        <dd class="mt-1">
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold <?= $overdue ? 'bg-brand-red text-white' : 'bg-brand-red/10 text-brand-red' ?>">
                                <?= esc(lang('Site.feedback.status_' . $status)) ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.feedback.submitted_on')) ?></dt>
                        <dd class="mt-1 text-white/80"><?= esc(date('j F Y', strtotime((string) $submission['created_at']))) ?></dd>
                    </div>
                    <?php if (! empty($submission['due_at']) && empty($submission['answered_at'])): ?>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.feedback.due_by')) ?></dt>
                            <dd class="mt-1 text-white/80"><?= esc(date('j F Y', strtotime((string) $submission['due_at']))) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (! empty($submission['answered_at'])): ?>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.feedback.answered_on')) ?></dt>
                            <dd class="mt-1 text-white/80"><?= esc(date('j F Y', strtotime((string) $submission['answered_at']))) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (! empty($submission['response'])): ?>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-white/50"><?= esc(lang('Site.feedback.response')) ?></dt>
                            <dd class="mt-1 leading-relaxed text-white/80"><?= nl2br(esc($submission['response'])) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
