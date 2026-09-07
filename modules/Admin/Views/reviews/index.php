<?php
helper(['url', 'norlanka', 'catalog']);
$this->extend('Modules\Admin\Views\layout');

/**
 * The moderation queue, as cards rather than as a table.
 *
 * A review is a paragraph somebody wrote, and a paragraph in a table cell is
 * either truncated or it wrecks the row height. The decision being made here —
 * publish this, or refuse it — cannot be made from the first forty characters,
 * so the whole thing is on the screen.
 *
 * There is no edit field anywhere on this page, deliberately. The words belong
 * to the person who wrote them; the school's choice is whether to publish them,
 * not what they say.
 */

$btn  = 'rounded-lg border px-3 py-1.5 text-xs font-semibold uppercase tracking-widest transition';
$card = 'rounded-2xl border border-white/10 bg-white/[0.02] p-5';

$statusChip = static function (string $status): string {
    $map = [
        'pending'  => 'bg-amber-500/15 text-amber-300',
        'approved' => 'bg-emerald-500/15 text-emerald-300',
        'rejected' => 'bg-brand-red/15 text-brand-red',
    ];

    return '<span class="rounded-full ' . ($map[$status] ?? 'bg-white/10 text-white/60')
        . ' px-2.5 py-0.5 text-[11px] font-semibold capitalize">' . esc($status) . '</span>';
};

// The name the review is published under, falling back to the account it was
// written from. A review with neither is an imported row, and the card says so
// rather than printing a blank where a person should be.
$author = static function (array $r): string {
    $name = trim((string) ($r['author_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    $account = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));

    return $account !== '' ? $account : (string) ($r['user_email'] ?? 'Name not given');
};
?>
<?= $this->section('content') ?>

<div class="mb-6 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3 text-xs leading-relaxed text-white/50">
    <span class="text-white/70">Nothing is ever seeded into this queue, and there is no way to add a review
    from the admin.</span> Every review here was written by somebody who took the course: the public form
    checks for an enrolment before it accepts one, and each card below shows the class the reviewer sat in.
    An invented five-star review is a lie a customer can act on, and a rating in structured data that no
    reviewer produced is the same lie told to a search engine — so the school's only choices on this screen
    are to publish, to refuse, or to delete. The words themselves are not editable here.
</div>

<?= view('Modules\Admin\Views\partials\queue_filter', [
    'base'     => 'admin/reviews',
    'current'  => $current,
    'statuses' => $statuses,
    'counts'   => $counts,
], ['saveData' => false]) ?>

<?php if ($rows === []): ?>
    <div class="<?= $card ?> text-center">
        <p class="text-white/60">
            <?php if ($current !== ''): ?>
                No reviews are <?= esc($current) ?>.
            <?php else: ?>
                No reviews yet.
            <?php endif; ?>
        </p>
        <?php if (array_sum($counts) === 0): ?>
            <p class="mx-auto mt-2 max-w-xl text-xs leading-relaxed text-white/40">
                This is the honest state of a school that has not taught its first class. The course pages
                show an empty state rather than a rating, and the structured data carries no
                <code class="text-white/60">AggregateRating</code> at all until a real reviewer produces one.
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="space-y-4">
    <?php foreach ($rows as $r):
        $id     = (int) $r['id'];
        $rating = max(0, min(5, (int) $r['rating'])); ?>
        <div class="<?= $card ?>">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-base text-amber-400" aria-hidden="true"><?= stars((int) $rating, 'text-white/15') ?></span>
                        <span class="text-xs text-white/45"><?= esc((string) $rating) ?> out of 5</span>
                        <?= $statusChip((string) $r['status']) ?>
                    </div>
                    <p class="mt-2 text-sm font-semibold text-white/80">
                        <?= esc(t_field($r['course_title'] ?? '')) ?>
                    </p>
                </div>
                <div class="text-right text-xs text-white/40">
                    <div><?= esc($r['created_at'] ? date('j M Y', strtotime((string) $r['created_at'])) : '—') ?></div>
                    <?php if (! empty($r['course_slug'])): ?>
                        <a href="<?= esc(course_url((string) $r['course_slug']), 'attr') ?>" target="_blank" rel="noopener" class="hover:text-brand-red">View course page</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (! empty($r['title'])): ?>
                <p class="mt-4 font-semibold"><?= esc($r['title']) ?></p>
            <?php endif; ?>
            <?php if (! empty($r['body'])): ?>
                <?php // Line breaks come from `whitespace-pre-line` rather than
                      // from nl2br, so the body stays a plain escaped string all
                      // the way to the browser. Inserting markup into it first
                      // and escaping afterwards is the shape that ends with a
                      // reviewer's <script> on a moderator's screen. ?>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-white/70"><?= esc($r['body']) ?></p>
            <?php endif; ?>

            <div class="mt-4 flex flex-wrap items-end justify-between gap-4 border-t border-white/5 pt-4">
                <div class="text-xs text-white/45">
                    <div class="font-medium text-white/70"><?= esc($author($r)) ?></div>
                    <?php if (! empty($r['author_role'])): ?>
                        <div><?= esc($r['author_role']) ?></div>
                    <?php endif; ?>
                    <?php if (! empty($r['user_email'])): ?>
                        <div><?= esc($r['user_email']) ?></div>
                    <?php endif; ?>
                    <?php // The trust line. An enrolment behind the review is what
                          // separates it from a form anybody on the internet filled
                          // in, so it is stated on every card either way. ?>
                    <div class="mt-1">
                        <?php if (! empty($r['enrolment_id'])): ?>
                            <span class="text-emerald-300/80">Enrolled on this course<?= empty($r['attended_on']) ? '' : ', ' . esc(date('j M Y', strtotime((string) $r['attended_on']))) ?></span>
                        <?php else: ?>
                            <span class="text-amber-300/80">No enrolment behind this review — source “<?= esc((string) $r['source']) ?>”. Check it before publishing.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <?php if ((string) $r['status'] !== 'approved'): ?>
                        <form method="post" action="<?= site_url('admin/reviews/' . $id) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="approved">
                            <button class="<?= $btn ?> border-emerald-500/40 text-emerald-300 hover:border-emerald-400">Publish</button>
                        </form>
                    <?php endif; ?>

                    <?php if ((string) $r['status'] !== 'rejected'): ?>
                        <form method="post" action="<?= site_url('admin/reviews/' . $id) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="rejected">
                            <button class="<?= $btn ?> border-white/15 text-white/70 hover:border-white hover:text-white">Reject</button>
                        </form>
                    <?php endif; ?>

                    <?php if ((string) $r['status'] !== 'pending'): ?>
                        <form method="post" action="<?= site_url('admin/reviews/' . $id) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="pending">
                            <button class="<?= $btn ?> border-white/15 text-white/50 hover:border-white hover:text-white">Put back</button>
                        </form>
                    <?php endif; ?>

                    <?php // Delete is for spam and duplicates. A rejection is kept,
                          // because it is the record of a complaint somebody made. ?>
                    <form method="post" action="<?= site_url('admin/reviews/' . $id . '/delete') ?>"
                          onsubmit="return confirm('Delete this review permanently? Rejecting it keeps the record and takes it off the site just the same.')">
                        <?= csrf_field() ?>
                        <button class="<?= $btn ?> border-brand-red/40 text-brand-red hover:border-brand-red">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
    <div class="mt-4 flex items-center justify-between text-xs text-white/40">
        <p><?= number_format($total) ?> review<?= $total === 1 ? '' : 's' ?> · page <?= esc((string) $page) ?> of <?= esc((string) $pages) ?></p>
        <div class="flex gap-2">
            <?php $link = static fn (int $p): string => site_url('admin/reviews')
                . '?' . http_build_query(array_filter(['status' => $current, 'page' => $p])); ?>
            <?php if ($page > 1): ?>
                <a href="<?= esc($link($page - 1), 'attr') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Previous</a>
            <?php endif; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= esc($link($page + 1), 'attr') ?>" class="rounded-lg border border-white/15 px-3 py-1.5 hover:border-white">Next</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
