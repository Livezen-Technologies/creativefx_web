<?php
helper(['norlanka', 'url']);

/**
 * The certificate itself, as it is printed.
 *
 * Rendered by dompdf, which is not a browser: no flexbox, no grid, no CSS
 * variables, no web fonts it has to fetch. Everything here is absolute
 * positioning and one built-in font family, because a layout that depends on a
 * feature dompdf silently ignores produces a document that looks fine in a
 * preview and wrong in the customer's hands.
 *
 * The design is deliberately plain. A certificate covered in gradients and
 * flourishes reads as a participation award; what makes this one worth having
 * is the verification code, the QR square and the fact that both resolve to a
 * public record.
 *
 * @var array  $certificate
 * @var string $qr         data: URI of the verification QR code
 * @var string $verifyUrl
 */
$issued = new DateTimeImmutable($certificate['issued_at'] ?? 'now');
$mode   = [
    'LIVE_ONLINE' => 'Live online',
    'CLASSROOM'   => 'In person',
    'SELF_PACED'  => 'Self-paced',
    'PRIVATE'     => 'Private cohort',
][$certificate['mode'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    body {
        margin: 0;
        font-family: "DejaVu Sans", sans-serif;
        color: #141a2e;
        background: #ffffff;
    }
    /* A4 landscape is 297mm x 210mm. The frame sits 10mm inside it. */
    .sheet { position: relative; width: 297mm; height: 210mm; }
    .frame {
        position: absolute; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm;
        border: 1.2mm solid #3f35c7;
    }
    .inner {
        position: absolute; top: 13mm; left: 13mm; right: 13mm; bottom: 13mm;
        border: 0.3mm solid #c9c6ee;
    }
    .rule { position: absolute; left: 40mm; right: 40mm; height: 0.6mm; background: #92400e; }

    .eyebrow {
        position: absolute; top: 26mm; left: 0; right: 0; text-align: center;
        font-size: 10pt; letter-spacing: 4pt; text-transform: uppercase; color: #3f35c7;
    }
    .school {
        position: absolute; top: 34mm; left: 0; right: 0; text-align: center;
        font-size: 20pt; font-weight: bold; letter-spacing: 1pt;
    }
    .awarded {
        position: absolute; top: 58mm; left: 0; right: 0; text-align: center;
        font-size: 11pt; color: #55607a;
    }
    .learner {
        position: absolute; top: 66mm; left: 20mm; right: 20mm; text-align: center;
        font-size: 30pt; font-weight: bold;
    }
    .for {
        position: absolute; top: 88mm; left: 0; right: 0; text-align: center;
        font-size: 11pt; color: #55607a;
    }
    .course {
        position: absolute; top: 96mm; left: 20mm; right: 20mm; text-align: center;
        font-size: 18pt; font-weight: bold; color: #3f35c7;
    }
    .detail {
        position: absolute; top: 116mm; left: 0; right: 0; text-align: center;
        font-size: 10pt; color: #55607a;
    }

    .foot { position: absolute; bottom: 22mm; left: 26mm; right: 26mm; }
    .foot td { vertical-align: bottom; font-size: 8.5pt; color: #55607a; }
    .sigline { border-top: 0.3mm solid #141a2e; padding-top: 1.5mm; width: 62mm; }
    .qr { width: 26mm; height: 26mm; }
    .code { font-size: 8pt; letter-spacing: 0.6pt; }
    .serial { font-size: 8pt; color: #8892a6; }
</style>
</head>
<body>
<div class="sheet">
    <div class="frame"></div>
    <div class="inner"></div>

    <p class="eyebrow">Certificate of completion</p>
    <p class="school"><?= esc(setting('site_name', 'MyLearnPlus')) ?></p>
    <div class="rule" style="top: 46mm;"></div>

    <p class="awarded">This is to certify that</p>
    <p class="learner"><?= esc($certificate['learner_name']) ?></p>

    <p class="for">has successfully completed</p>
    <p class="course"><?= esc($certificate['title']) ?></p>

    <p class="detail">
        <?= esc($mode) ?><?= $mode !== '' && (int) $certificate['hours'] > 0 ? ' &middot; ' : '' ?>
        <?php if ((int) $certificate['hours'] > 0): ?>
            <?= (int) $certificate['hours'] ?> hours of instruction
        <?php endif; ?>
        &middot; <?= esc($issued->format('j F Y')) ?>
    </p>

    <table class="foot" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="40%">
                <div class="sigline">
                    <?php // Not a scanned signature. A picture of somebody's
                          // handwriting on every certificate is worth less than
                          // the verification URL below it, and is a forgery
                          // asset the moment one PDF leaves the building. ?>
                    Authorised on behalf of <?= esc(setting('site_name', 'MyLearnPlus')) ?>
                </div>
            </td>
            <td width="34%" style="text-align: center;">
                <p class="code">
                    Verify at<br>
                    <strong><?= esc(preg_replace('~^https?://~', '', $verifyUrl)) ?></strong>
                </p>
                <p class="serial">Serial <?= esc($certificate['serial']) ?></p>
            </td>
            <td width="26%" style="text-align: right;">
                <?php // The square is the point of the document: it takes
                      // whoever is holding this to the public record, which is
                      // the only thing here that cannot be photocopied. ?>
                <img class="qr" src="<?= esc($qr, 'attr') ?>" alt="Verification QR code">
            </td>
        </tr>
    </table>
</div>
</body>
</html>
