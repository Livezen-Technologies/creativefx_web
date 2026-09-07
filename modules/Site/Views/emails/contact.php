<?php
helper(['norlanka', 'url']);

/**
 * The enquiry, as it reaches the school.
 *
 * Plain and complete. Whoever opens this is about to reply, so the reply
 * address is the sender's — set as Reply-To by the controller — and every field
 * they typed is here rather than summarised.
 *
 * @var array $data
 */
?>
<h2 style="font-family:Helvetica,Arial,sans-serif;">New enquiry</h2>
<table cellpadding="6" cellspacing="0" style="font-family:Helvetica,Arial,sans-serif;font-size:14px;border-collapse:collapse;">
    <?php foreach ([
        'Name'    => $data['name'],
        'Email'   => $data['email'],
        'Phone'   => $data['phone'],
        'Subject' => $data['subject'],
    ] as $label => $value): ?>
        <?php if (trim((string) $value) !== ''): ?>
            <tr>
                <th align="left" style="color:#55607a;font-weight:600;"><?= esc($label) ?></th>
                <td><?= esc($value) ?></td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
</table>
<p style="font-family:Helvetica,Arial,sans-serif;font-size:14px;white-space:pre-wrap;"><?= esc($data['message']) ?></p>
<p style="font-family:Helvetica,Arial,sans-serif;font-size:12px;color:#55607a;">
    Sent from <?= esc(base_url()) ?> — reply to this email to answer directly.
</p>
