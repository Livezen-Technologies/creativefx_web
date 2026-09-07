<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Every review the school has, and the form for adding one.
 *
 * The page has to do two opposite things well, and the second is the harder.
 *
 * When there are reviews it is a wall of them, newest first, each naming the
 * course it belongs to and linking back to it, because a review is only
 * evidence if the reader can go and buy the thing it is about.
 *
 * When there are none — which is the state it launches in, since nothing is
 * ever seeded into this table — it says so plainly and says why. That empty
 * panel is not a placeholder waiting to be replaced: it is the argument the
 * whole page exists to make, and it is worth more to a buyer than five
 * testimonials would have been. So it is written as finished copy, it offers
 * the two routes onward that somebody at this point actually wants, and
 * nothing on the page is dimmed or apologetic about it.
 *
 * The form beneath is rendered once per course the visitor may review, rather
 * than as one form with a course chooser. A chooser would have to be populated
 * with courses they are entitled to review anyway, so the list is the same
 * list — and this way the course is a heading a person can read rather than an
 * option they have to find, and the hidden id is never the only thing
 * identifying what is being reviewed on screen.
 *
 * @var list<array>  $reviews     approved, newest first, with course_slug and course_title
 * @var int          $total       across every page, not this one
 * @var int          $page
 * @var int          $perPage
 * @var list<array{id:int, slug:string, title:string}> $reviewable
 * @var bool         $signedIn
 * @var array<string,string> $errors
 * @var list<array>  $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

/**
 * Which form the visitor was in when validation refused it.
 *
 * The page renders one form per course, so the old input and the error
 * messages belong to exactly one of them. Repopulating every form would put
 * somebody's paragraph under three different course headings, and mark two
 * courses invalid that were never submitted.
 */
$repostFor = (int) old('course_id', 0, false);
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'heading' => lang('Catalog.reviews.title'),
    'intro'   => lang('Catalog.reviews.intro'),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x space-y-14 py-12">

    <?php // The outcome of a submission, above everything, because a
          // confirmation found by scrolling is a confirmation somebody misses
          // and then sends a second review to check. ?>
    <?php if ($ok = session()->getFlashdata('review_ok')): ?>
        <p role="status" class="rounded-2xl border border-brand-red/40 bg-brand-red/10 px-5 py-4 text-sm">
            <?= esc($ok) ?>
        </p>
    <?php elseif ($problem = session()->getFlashdata('review_error')): ?>
        <p role="alert" class="rounded-2xl border border-line bg-surface px-5 py-4 text-sm text-brand-red">
            <?= esc($problem) ?>
        </p>
    <?php endif; ?>

    <?php // ── The reviews, or the honest absence of them ──────────────────── ?>
    <?php if ($reviews === []): ?>
        <div class="max-w-3xl rounded-2xl border border-line bg-surface p-6">
            <p class="leading-relaxed text-white/70"><?= esc(lang('Catalog.reviews.none')) ?></p>

            <?php // Somebody who came here to read reviews is deciding whether
                  // to book. With none to read, the next most useful thing is
                  // not an apology but the catalogue and the calendar, which
                  // carry the real dates and the real prices that this page
                  // cannot yet corroborate. ?>
            <div class="mt-6 flex flex-wrap gap-4">
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand"><?= esc(lang('Catalog.courses.all')) ?></a>
                <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Catalog.pillars.schedule_all')) ?></a>
            </div>
        </div>
    <?php else: ?>
        <section aria-labelledby="all-reviews">
            <h2 id="all-reviews" class="section-title"><?= esc(lang('Catalog.course.reviews')) ?></h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($reviews as $review): ?>
                    <?php
                    // Clamped to the scale the page draws rather than trusted.
                    // The column is a TINYINT and the site's own form can only
                    // post 1 to 5, but the table is explicitly built to accept
                    // reviews imported from somewhere else, and a single row
                    // carrying a rating out of ten makes str_repeat() throw and
                    // takes down the whole page — every genuine review on it —
                    // rather than rendering one row oddly.
                    $stars = max(0, min(5, (int) $review['rating']));
                    ?>
                    <blockquote class="flex flex-col rounded-2xl border border-line bg-surface p-5">
                        <?php // The stars are decoration with the rating spelled
                              // out beside them for anybody not looking: five
                              // glyphs read aloud as "black star black star
                              // black star" is noise, and the greyed remainder
                              // is worse. The sentence is a visually hidden
                              // span rather than an aria-label on the paragraph
                              // because a label on an element with no role is
                              // not reliably announced, and the same pattern is
                              // used by the rating control in the form below. ?>
                        <p class="text-gold">
                            <span aria-hidden="true"><?= stars((int) $stars) ?></span>
                            <span class="sr-only"><?= esc(lang('Catalog.course.rating_of', [$stars])) ?></span>
                        </p>

                        <?php if (! empty($review['title'])): ?>
                            <p class="mt-2 font-semibold leading-snug"><?= esc($review['title']) ?></p>
                        <?php endif; ?>

                        <?php // nl2br over esc, never the other way round: escaping the <br> the
                              // other order produces is how a "fix" for line breaks turns into
                              // markup printed at the reader. A learner writes in paragraphs and
                              // esc() alone ran them into one block. ?>
                        <p class="mt-2 text-sm leading-relaxed text-white/75"><?= nl2br(esc($review['body'])) ?></p>

                        <footer class="mt-auto pt-4 text-xs leading-relaxed text-white/50">
                            <?php // A review with no name is signed "A learner"
                                  // rather than left unsigned: an unattributed
                                  // quotation looks like copy, which is exactly
                                  // what this page is trying not to be. ?>
                            <span class="text-white/65"><?= esc($review['author_name'] ?: lang('Catalog.course.review_anon')) ?></span><?php if (! empty($review['author_role'])): ?>, <?= esc($review['author_role']) ?><?php endif; ?>
                            <br>
                            <a href="<?= esc(course_url($review['course_slug'])) ?>" class="underline decoration-line underline-offset-4 hover:text-brand-red">
                                <?= esc(t_field($review['course_title'])) ?>
                            </a>
                            <?php if (! empty($review['published_at'])): ?>
                                <?php $when = new DateTimeImmutable((string) $review['published_at']); ?>
                                <span aria-hidden="true"> · </span>
                                <time datetime="<?= esc($when->format('Y-m-d'), 'attr') ?>"><?= esc($when->format('j F Y')) ?></time>
                            <?php endif; ?>
                        </footer>
                    </blockquote>
                <?php endforeach; ?>
            </div>

            <?= view('Modules\Catalog\Views\partials\pagination', [
                'page'    => $page,
                'total'   => $total,
                'perPage' => $perPage,
                'base'    => locale_url('reviews'),
                'params'  => [],
            ], ['saveData' => false]) ?>
        </section>
    <?php endif; ?>

    <?php // ── Leaving one ─────────────────────────────────────────────────── ?>
    <?php // scroll-mt keeps the heading clear of the floating header when the
          // form is reached by the #leave anchor a failed submission returns
          // to; without it the first thing a rejected submission shows is the
          // underside of the navigation bar. ?>
    <section id="leave" class="scroll-mt-32" aria-labelledby="leave-title">
        <h2 id="leave-title" class="section-title"><?= esc(lang('Catalog.reviews.leave')) ?></h2>

        <?php if ($reviewable !== []): ?>
            <p class="mt-4 max-w-2xl leading-relaxed text-white/65"><?= esc(lang('Reviews.form.moderated')) ?></p>

            <div class="mt-6 space-y-6">
                <?php foreach ($reviewable as $course): ?>
                    <?php
                    $cid = (int) $course['id'];

                    // Old input and errors apply to the one form that was
                    // submitted. Everything else on the page renders empty and
                    // valid, whatever the visitor typed in it a moment ago.
                    $isRepost    = $repostFor === $cid;
                    $fieldErrors = $isRepost ? $errors : [];
                    $oldRating   = $isRepost ? (int) old('rating', 0, false) : 0;
                    $errRating   = (string) ($fieldErrors['rating'] ?? '');
                    $errTitle    = (string) ($fieldErrors['title'] ?? '');
                    $errBody     = (string) ($fieldErrors['body'] ?? '');
                    $errName     = (string) ($fieldErrors['author_name'] ?? '');
                    ?>
                    <form method="post" action="<?= esc(locale_url('reviews')) ?>"
                          class="max-w-2xl rounded-2xl border border-line bg-surface p-5 sm:p-6">
                        <?= csrf_field() ?>

                        <?php // The only thing that makes this id legitimate is
                              // ReviewModel::mayReview() on the way in; it is a
                              // hidden field, so it is a claim, not a fact. ?>
                        <input type="hidden" name="course_id" value="<?= $cid ?>">

                        <h3 class="text-lg font-semibold leading-snug">
                            <a href="<?= esc(course_url($course['slug'])) ?>" class="hover:text-brand-red">
                                <?= esc($course['title']) ?>
                            </a>
                        </h3>

                        <?php // Every id carries the course id. Several forms
                              // share this page, and a duplicated id silently
                              // points every later label at the first form's
                              // field — so a click on the third course's
                              // "Your review" puts the cursor in the first. ?>
                        <fieldset class="mt-5">
                            <legend class="field-label"><?= esc(lang('Reviews.form.rating')) ?></legend>
                            <div class="mt-1 flex flex-wrap gap-x-5 gap-y-3">
                                <?php // Five down to one, so the row reads the
                                      // way the scale does and the fullest set
                                      // of stars is the one nearest the label. ?>
                                <?php for ($n = 5; $n >= 1; $n--): ?>
                                    <span class="inline-flex items-center gap-2">
                                        <input type="radio" id="rating-<?= $cid ?>-<?= $n ?>" name="rating" value="<?= $n ?>" required
                                               class="h-4 w-4 shrink-0 accent-brand-red"
                                               <?= $errRating !== '' ? 'aria-invalid="true" aria-describedby="err-rating-' . $cid . '"' : '' ?>
                                               <?= $oldRating === $n ? 'checked' : '' ?>>
                                        <label for="rating-<?= $cid ?>-<?= $n ?>" class="cursor-pointer text-sm">
                                            <span class="text-gold" aria-hidden="true"><?= str_repeat('★', $n) ?></span>
                                            <span class="sr-only"><?= esc(lang('Catalog.course.rating_of', [$n])) ?></span>
                                        </label>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <?php if ($errRating !== ''): ?><span id="err-rating-<?= $cid ?>" class="field-error"><?= esc($errRating) ?></span><?php endif; ?>
                        </fieldset>

                        <label class="mt-5 block">
                            <span class="field-label"><?= esc(lang('Reviews.form.title')) ?></span>
                            <input id="title-<?= $cid ?>" name="title" type="text" maxlength="191" class="field"
                                   value="<?= esc($isRepost ? old('title', '', false) : '', 'attr') ?>"
                                   <?= $errTitle !== '' ? 'aria-invalid="true" aria-describedby="err-title-' . $cid . '"' : '' ?>>
                            <?php if ($errTitle !== ''): ?><span id="err-title-<?= $cid ?>" class="field-error"><?= esc($errTitle) ?></span><?php endif; ?>
                        </label>

                        <label class="mt-5 block">
                            <span class="field-label"><?= esc(lang('Reviews.form.body')) ?></span>
                            <?php // minlength as well as the server rule, so the
                                  // browser says so before the page is posted.
                                  // The server still decides. ?>
                            <textarea id="body-<?= $cid ?>" name="body" rows="6" required minlength="30" maxlength="5000" class="field"
                                      aria-describedby="hint-body-<?= $cid ?><?= $errBody !== '' ? ' err-body-' . $cid : '' ?>"
                                      <?= $errBody !== '' ? 'aria-invalid="true"' : '' ?>><?= esc($isRepost ? old('body', '', false) : '') ?></textarea>
                            <span id="hint-body-<?= $cid ?>" class="mt-1.5 block text-xs leading-relaxed text-white/50"><?= esc(lang('Reviews.form.body_hint')) ?></span>
                            <?php if ($errBody !== ''): ?><span id="err-body-<?= $cid ?>" class="field-error"><?= esc($errBody) ?></span><?php endif; ?>
                        </label>

                        <label class="mt-5 block">
                            <span class="field-label"><?= esc(lang('Reviews.form.name')) ?></span>
                            <input id="author-<?= $cid ?>" name="author_name" type="text" maxlength="128" autocomplete="nickname" class="field"
                                   value="<?= esc($isRepost ? old('author_name', '', false) : '', 'attr') ?>"
                                   aria-describedby="hint-author-<?= $cid ?><?= $errName !== '' ? ' err-author-' . $cid : '' ?>"
                                   <?= $errName !== '' ? 'aria-invalid="true"' : '' ?>>
                            <span id="hint-author-<?= $cid ?>" class="mt-1.5 block text-xs leading-relaxed text-white/50"><?= esc(lang('Reviews.form.name_hint')) ?></span>
                            <?php if ($errName !== ''): ?><span id="err-author-<?= $cid ?>" class="field-error"><?= esc($errName) ?></span><?php endif; ?>
                        </label>

                        <button type="submit" class="btn-brand mt-6"><?= esc(lang('Catalog.reviews.leave')) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>

        <?php elseif ($signedIn): ?>
            <p class="mt-4 max-w-2xl leading-relaxed text-white/65"><?= esc(lang('Reviews.nothing_to_review')) ?></p>
            <a href="<?= esc(locale_url('courses')) ?>" class="btn-ghost mt-5"><?= esc(lang('Catalog.courses.all')) ?></a>

        <?php else: ?>
            <?php // No form for a signed-out visitor, and no form for anybody
                  // else either. The rule is stated rather than enforced by a
                  // disabled control, because most people reading this page
                  // have no account here and a greyed-out box they cannot use
                  // tells them nothing about why. ?>
            <p class="mt-4 max-w-2xl leading-relaxed text-white/65"><?= esc(lang('Reviews.signed_out')) ?></p>
            <a href="<?= esc(locale_url('account/login')) ?>" class="btn-brand mt-5"><?= esc(lang('Account.login.submit')) ?></a>
        <?php endif; ?>
    </section>
</div>

<?= $this->endSection() ?>
