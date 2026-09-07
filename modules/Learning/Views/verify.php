<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The verification result: one verdict, then the record behind it.
 *
 * Every decision on this page comes from who reads it and how. Not a learner
 * browsing at home — somebody at a desk who has just scanned a square off a
 * piece of paper with their phone, has a stack of other applications in front
 * of them, and wants one thing: is this real. So the verdict is a sentence in
 * the largest type on the page, it is the `<h1>`, and there is nothing above it
 * or beside it. The record that supports it comes after, in a single column,
 * because a two-column table on a phone is a table nobody reads to the end.
 *
 * The verdict is carried three ways over — the words, the shape of the icon and
 * the colour — and never by the colour alone. A page whose meaning is a hue
 * fails for a colour-blind reader, and this one gets printed and photocopied as
 * well, where every hue becomes grey.
 *
 * Three of the four states are answers about a certificate. The fourth,
 * `throttled`, is not: it is the page declining to be swept, and it says so in
 * those words, because "we will not answer you right now" must never be read as
 * "this certificate is not real".
 *
 * The code that was asked for is deliberately not echoed back. It would help
 * somebody who mistyped, but it would also put an attacker's string on a page
 * printed on other people's documents, and `unknown_body` already tells the
 * reader to check the characters against the paper — which is the same help
 * without the reflection.
 *
 * Nothing is shown here that is not printed on the document itself. The
 * controller's `printedFields()` enforces that; this view simply has nothing
 * else to render, and it links nowhere except to the school's contact page.
 *
 * @var string     $outcome     valid | revoked | unknown | throttled
 * @var array|null $certificate the printed fields, or null
 */
helper(['norlanka', 'catalog', 'url']);

$tones = [
    'valid'     => ['ink' => 'text-brand-red', 'edge' => 'border-brand-red', 'wash' => 'bg-brand-red/10'],
    'revoked'   => ['ink' => 'text-gold',      'edge' => 'border-gold',      'wash' => 'bg-gold/10'],
    'unknown'   => ['ink' => 'text-white',     'edge' => 'border-line',      'wash' => 'bg-surface'],
    'throttled' => ['ink' => 'text-white',     'edge' => 'border-line',      'wash' => 'bg-surface'],
];
$tone = $tones[$outcome] ?? $tones['unknown'];

// Formatted in the application's own timezone, which is UTC — exactly what
// CertificateService wrote and exactly what the PDF renders. A screen and a
// piece of paper disagreeing by a day about the same certificate is the kind of
// small discrepancy that makes somebody doubt both of them.
$issuedOn  = empty($certificate['issued_at'])  ? '' : date('j F Y', strtotime((string) $certificate['issued_at']));
$revokedOn = empty($certificate['revoked_at']) ? '' : date('j F Y', strtotime((string) $certificate['revoked_at']));

// Literal keys rather than one built out of $outcome.
// scripts/check-language-keys.php can only verify a key it can read, and a
// string missing from this page prints "Learning.verify.valid_heading" at
// somebody deciding whether to trust a qualification.
$heading = match ($outcome) {
    'valid'   => lang('Learning.verify.valid_heading'),
    'revoked' => lang('Learning.verify.revoked_heading'),
    'unknown' => lang('Learning.verify.unknown_heading'),
    default   => lang('Learning.verify.throttled_heading'),
};
$lead = match ($outcome) {
    'valid'   => lang('Learning.verify.valid_body'),
    'revoked' => lang('Learning.verify.revoked_body'),
    'unknown' => lang('Learning.verify.unknown_body'),
    default   => lang('Learning.verify.throttled_body'),
};

// The record, assembled as data and rendered once below. A row appears only
// when it holds something: an empty "Taught hours —" line invites the reader to
// wonder what is missing from a document that is in fact complete.
$rows = [];
if ($certificate !== null) {
    $rows[] = ['label' => lang('Learning.verify.field_learner'), 'value' => $certificate['learner_name'], 'class' => 'text-lg font-semibold'];
    $rows[] = ['label' => lang('Learning.verify.field_course'), 'value' => $certificate['title'], 'class' => 'font-medium'];

    if ($certificate['mode'] !== '' && ($modeLabel = mode_label($certificate['mode'])) !== '') {
        $rows[] = ['label' => lang('Learning.verify.field_mode'), 'value' => $modeLabel, 'class' => ''];
    }
    if ($certificate['hours'] > 0) {
        $rows[] = ['label' => lang('Learning.verify.field_hours'), 'value' => (string) $certificate['hours'], 'class' => 'tabular-nums'];
    }
    if ($issuedOn !== '') {
        $rows[] = ['label' => lang('Learning.verify.field_issued'), 'value' => $issuedOn, 'class' => ''];
    }

    $rows[] = ['label' => lang('Learning.verify.field_serial'), 'value' => $certificate['serial'], 'class' => 'font-mono'];
    // The document's own label for this field, because that is what the reader
    // is holding. Safe to show — it is already in the address bar and printed
    // under the QR square — and it lets them confirm the page they are looking
    // at is the one they asked for.
    $rows[] = ['label' => lang('Learning.certificate.verify_code'), 'value' => $certificate['verify_code'], 'class' => 'font-mono break-all'];
}
?>

<?= $this->section('content') ?>

<?php // ── The verdict. Nothing above it, nothing beside it. ──────────────── ?>
<section class="relative overflow-hidden border-b border-line pb-12 pt-32 sm:pt-36">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-40"></div>
    <div class="container-x">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc(lang('Learning.verify.eyebrow')) ?></p>

        <div class="mt-7 flex flex-col gap-6 sm:flex-row sm:items-start sm:gap-8">
            <?php // Three unmistakably different glyphs in one constant circle:
                  // a tick, a stroke through it, and a bare dash. Keeping the
                  // frame the same puts the whole difference on the mark inside
                  // it, which is what survives being seen small, in grey, on a
                  // cracked screen. ?>
            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border <?= $tone['edge'] ?> <?= $tone['wash'] ?> <?= $tone['ink'] ?> sm:h-20 sm:w-20" aria-hidden="true">
                <svg class="h-9 w-9 sm:h-11 sm:w-11" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.5"/>
                    <?php if ($outcome === 'valid'): ?>
                        <path d="M7.6 12.4l3 3 5.9-6.6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <?php elseif ($outcome === 'revoked'): ?>
                        <path d="M8.1 15.9L15.9 8.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <?php else: ?>
                        <path d="M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <?php endif; ?>
                </svg>
            </span>

            <div class="min-w-0">
                <h1 class="text-3xl font-bold leading-tight sm:text-5xl <?= $tone['ink'] ?>"><?= esc($heading) ?></h1>
                <p class="mt-4 max-w-2xl text-lg leading-relaxed text-white/70"><?= esc($lead) ?></p>

                <?php // The date of a withdrawal belongs in the verdict, not
                      // buried in the table below it. A revoked certificate has
                      // to keep answering — going quiet is indistinguishable
                      // from never having been issued — and "when" is the first
                      // thing the person holding it will be asked. ?>
                <?php if ($outcome === 'revoked' && $revokedOn !== ''): ?>
                    <p class="mt-3 text-lg font-semibold <?= $tone['ink'] ?>"><?= esc(lang('Learning.verify.revoked_on', [$revokedOn])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="container-x py-12 sm:py-14">
    <div class="max-w-3xl space-y-10">

        <?php // ── The record ─────────────────────────────────────────────── ?>
        <?php if ($rows !== []): ?>
            <section aria-labelledby="record" class="rounded-3xl border border-line bg-surface p-6 sm:p-8">
                <h2 id="record" class="section-title"><?= esc(lang('Learning.certificate.heading')) ?></h2>

                <?php // A description list, so a screen reader announces each
                      // value with the label it belongs to. Stacked on a phone
                      // and two columns from the small breakpoint up: a label
                      // sitting above its value is far easier to read at arm's
                      // length than a cramped pair squeezed side by side. ?>
                <dl class="mt-6 border-t border-line">
                    <?php foreach ($rows as $row): ?>
                        <div class="grid gap-1 border-b border-line py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
                            <dt class="text-sm text-white/55"><?= esc($row['label']) ?></dt>
                            <dd class="min-w-0 <?= $row['class'] ?>"><?= esc($row['value']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>
        <?php endif; ?>

        <?php // ── What this page is, and the one thing it is not ─────────── ?>
        <section class="space-y-4 text-sm leading-relaxed text-white/60">
            <p><?= esc(lang('Learning.verify.privacy_note')) ?></p>

            <?php // Said wherever a certificate is shown, and most of all here,
                  // where the reader is a stranger deciding what a document in
                  // their hand is worth. The school issues its own certificate
                  // of completion; Adobe Certified Professional is set and
                  // awarded by Adobe through Certiport, and this is the one page
                  // where letting the two blur would cost somebody something. ?>
            <?php if ($certificate !== null): ?>
                <p><?= esc(lang('Learning.certificate.scope_note')) ?></p>
            <?php endif; ?>

            <?php // The only link on the page. The unknown and throttled states
                  // both end by telling the reader to get in touch, and leaving
                  // them to find the contact page themselves is how a genuine
                  // certificate ends up dismissed as a fake. ?>
            <p>
                <a href="<?= esc(locale_url('contact')) ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4">
                    <?= esc(lang('Site.nav.contact')) ?>
                </a>
            </p>
        </section>
    </div>
</div>

<?= $this->endSection() ?>
