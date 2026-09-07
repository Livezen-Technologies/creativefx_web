<?php
helper('norlanka');
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $pageTheme = $pageTheme ?? ''; ?>
<div<?= $pageTheme !== '' ? ' class="' . esc($pageTheme, 'attr') . '"' : '' ?>>
<?php
/**
 * Full-bleed CMS renderer: every block partial is a self-contained <section>
 * and owns its own layout. The first block of a page is normally a `pagehero`,
 * which provides the top padding the fixed header needs.
 *
 * Two things here were wrong before and are worth explaining rather than
 * quietly fixing.
 *
 * **The fallback used to be a bare `catch (\Throwable)`.** It was written to
 * handle a block type with no partial, but it caught everything: a TypeError,
 * an undefined index, a fatal inside a perfectly valid block — all of it was
 * swallowed and the richtext partial was rendered over the top with the same
 * payload. The page came back looking almost right, with no error in the log,
 * on screen or in the console. So the missing-partial case is now decided
 * *before* rendering, by asking whether the file exists, and anything that goes
 * wrong inside a block that does exist is logged; in development it is re-
 * thrown, because a broken block should be loud where somebody can fix it.
 *
 * **saveData was true.** CodeIgniter's shared renderer keeps a view's data when
 * saveData is on, so each block's `$content` leaked into the next block that
 * did not set one — a block with an empty payload would render the previous
 * block's. Off, every block sees only its own.
 */
foreach ($page['sections'] ?? [] as $section):
    foreach ($section['blocks'] ?? [] as $block):
        // Sanitised because the type reaches the filesystem: a block type is
        // editable in the admin, and a slash in it would otherwise address any
        // view in the application.
        $type    = preg_replace('/[^a-z0-9_]/i', '', $block['type'] ?? 'richtext');
        $content = json_decode($block['content'] ?? '[]', true) ?: [];
        $partial = 'Modules\Site\Views\cms\blocks\\' . $type;

        if (! is_file(ROOTPATH . 'modules/Site/Views/cms/blocks/' . $type . '.php')) {
            // A block type nobody has written a partial for. Rendering its
            // payload as rich text is the useful failure: the words still
            // appear, which is better than a hole in the middle of the page.
            log_message('warning', 'CMS block type "{type}" has no partial; rendered as rich text.', ['type' => $type]);
            echo view('Modules\Site\Views\cms\blocks\richtext', ['content' => $content], ['saveData' => false]);

            continue;
        }

        try {
            echo view($partial, ['content' => $content], ['saveData' => false]);
        } catch (\Throwable $e) {
            log_message('error', 'CMS block "{type}" failed to render on page {page}: {msg}', [
                'type' => $type,
                'page' => $page['slug'] ?? '?',
                'msg'  => $e->getMessage(),
            ]);

            if (ENVIRONMENT !== 'production') {
                throw $e;
            }

            echo view('Modules\Site\Views\cms\blocks\richtext', ['content' => $content], ['saveData' => false]);
        }
    endforeach;
endforeach;

// ── When this policy took effect ────────────────────────────────────────────
//
// The five policy pages each carried the sentence "They take effect on
// {effective date}" in their body copy, and nothing ever filled it in, so every
// one of them told visitors it took effect on "{effective date}". A policy is
// exactly the wrong document to have a visible gap in.
//
// The date is not written into the copy at all now. It is read from the page
// record, which already knows when this version was published, so it is true by
// construction and stays true when somebody edits the policy in the admin — a
// hand-typed date in the prose would be wrong the first time it is revised and
// nobody would notice. It also belongs in a labelled line rather than buried
// mid-paragraph, where a reader looking for "which version am I bound by" has
// to find it.
if (in_array($page['template'] ?? '', ['policy', 'legal'], true) || str_starts_with((string) ($page['slug'] ?? ''), 'policies-')):
    $effective = $page['publish_at'] ?: ($page['updated_at'] ?? null);
    if ($effective): ?>
        <section class="border-t border-line">
            <div class="container-x py-8">
                <p class="text-sm text-white/50">
                    <?= esc(lang('Site.policies.in_effect', [date('j F Y', strtotime((string) $effective))])) ?>
                </p>
            </div>
        </section>
    <?php endif;
endif;
?>
</div>
<?= $this->endSection() ?>
