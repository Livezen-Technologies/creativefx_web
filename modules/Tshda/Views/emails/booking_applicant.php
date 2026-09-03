<?php
helper(['norlanka', 'url']);

ob_start(); ?>
<p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#3b4a42">
  Thank you. Your application to the Hantana National Training Centre has been received.
</p>
<p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6b62">Your reference</p>
<p style="margin:0 0 18px;font-size:22px;font-weight:700;color:#0F6B45;font-family:'Courier New',monospace"><?= esc($reference) ?></p>
<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;line-height:1.6">
  <tr>
    <td style="padding:6px 12px 6px 0;color:#5b6b62;white-space:nowrap;vertical-align:top">Programme</td>
    <td style="padding:6px 0;color:#17231d;font-weight:600"><?= esc(t_field($programme['title'])) ?></td>
  </tr>
  <?php if (! empty($programme['starts_on'])): ?>
    <tr>
      <td style="padding:6px 12px 6px 0;color:#5b6b62;white-space:nowrap;vertical-align:top">Dates</td>
      <td style="padding:6px 0;color:#17231d;font-weight:600"><?= esc(date('j F Y', strtotime((string) $programme['starts_on']))) ?><?= ! empty($programme['ends_on']) && $programme['ends_on'] !== $programme['starts_on'] ? ' – ' . esc(date('j F Y', strtotime((string) $programme['ends_on']))) : '' ?></td>
    </tr>
  <?php endif; ?>
</table>
<p style="margin:20px 0 0;font-size:14px;line-height:1.6;color:#3b4a42">
  <?php if ($status === 'waitlisted'): ?>
    The programme is at capacity, so your application has been placed on the waiting list. The Centre will contact you if a place becomes available.
  <?php else: ?>
    The Centre will confirm, waitlist or decline your application and let you know the outcome. Please keep this reference.
  <?php endif; ?>
</p>
<?php $body = ob_get_clean();

echo view('Modules\Tshda\Views\emails\_layout', [
    'heading' => 'Your application to the Hantana National Training Centre',
    'body'    => $body,
], ['saveData' => false]);
