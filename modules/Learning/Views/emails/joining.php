<?php
helper(['norlanka', 'url']);

/**
 * To the person who is actually coming to the class.
 *
 * The rule this letter follows: **every question somebody has at 8:50 on the
 * morning of the course is answered above the fold.** Where, when, in whose
 * timezone, and what to bring. Everything else — the receipt, the policy, the
 * marketing — belongs somewhere else.
 *
 * @var array       $attendee
 * @var array       $order
 * @var array       $item
 * @var array|null  $session
 * @var array|null  $course
 * @var array|null  $venue
 * @var string      $title
 * @var string      $school
 */
$selfPaced = $session === null || empty($session['start_date']);
$tz        = $session['timezone'] ?? 'Asia/Colombo';

ob_start(); ?>
<p style="margin:0 0 16px;">Hello <?= esc($attendee['name'] ?: $attendee['email']) ?>,</p>

<p style="margin:0 0 20px;">
    Your place on <strong><?= esc($title) ?></strong> is confirmed.
    <?php if (! $selfPaced): ?>
        The calendar file attached to this email will put it in your diary in your own timezone.
    <?php endif; ?>
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #dfe3ef;border-radius:10px;margin:0 0 22px;">
    <?php if ($selfPaced): ?>
        <tr><td style="padding:14px 16px;font-size:14px;">
            <strong>Start whenever you like.</strong><br>
            This is a self-paced course. Everything is already in your account and stays there.
        </td></tr>
    <?php else:
        $start = new DateTimeImmutable($session['start_date'] . ' ' . ($session['daily_start'] ?: '09:00'), new DateTimeZone($tz));
        $end   = new DateTimeImmutable(($session['end_date'] ?: $session['start_date']) . ' ' . ($session['daily_end'] ?: '16:00'), new DateTimeZone($tz));
    ?>
        <tr><td style="padding:14px 16px;font-size:14px;border-bottom:1px solid #eef0f7;">
            <strong>When</strong><br>
            <?= esc($start->format('l j F Y')) ?><?php if ($session['end_date'] && $session['end_date'] !== $session['start_date']): ?>
                &ndash; <?= esc($end->format('l j F Y')) ?>
            <?php endif; ?><br>
            <?php // Both clocks, always. A learner in Dubai reading "09:00" has
                  // no way to know whose nine o'clock it is, and finding out at
                  // 09:00 their time is finding out too late. ?>
            <?= esc(substr((string) $session['daily_start'], 0, 5)) ?>&ndash;<?= esc(substr((string) $session['daily_end'], 0, 5)) ?>
            <?= esc($start->format('T')) ?>
            (<?= esc($start->setTimezone(new DateTimeZone('UTC'))->format('H:i')) ?>&ndash;<?= esc($end->setTimezone(new DateTimeZone('UTC'))->format('H:i')) ?> UTC)
        </td></tr>
        <tr><td style="padding:14px 16px;font-size:14px;">
            <strong>Where</strong><br>
            <?php if ($venue !== null && ($venue['type'] ?? '') === 'classroom'): ?>
                <?= esc($venue['name']) ?><?php if ($address = t_field($venue['address'] ?? '')): ?><br><?= esc($address) ?><?php endif; ?>
            <?php else: ?>
                Live online. Your joining link is on your account page, and is
                sent again the day before.
            <?php endif; ?>
        </td></tr>
    <?php endif; ?>
</table>

<p style="margin:0 0 22px;">
    <a href="<?= esc(rtrim(base_url(), '/') . '/en/account', 'attr') ?>"
       style="display:inline-block;background:#3f35c7;color:#ffffff;text-decoration:none;padding:11px 22px;border-radius:999px;font-weight:bold;font-size:14px;">
        Open your account
    </a>
</p>

<p style="margin:0 0 8px;font-size:14px;color:#55607a;">
    Everything about this booking — materials, the recording afterwards, your
    certificate and the invoice — lives in your account. If you have not set a
    password yet, use "forgot password" with this address and you will be let in.
</p>

<p style="margin:0;font-size:14px;color:#55607a;">
    Need to move to another date? Transfers are free with ten or more working
    days' notice. Reply to this email and we will sort it out.
</p>
<?php
$body = ob_get_clean();

echo view('Modules\Learning\Views\emails\_layout', [
    'title'  => $title,
    'body'   => $body,
    'school' => $school,
], ['saveData' => false]);
