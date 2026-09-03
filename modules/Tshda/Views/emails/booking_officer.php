<?php
helper('norlanka');

$rows = [
    'Reference'    => $reference,
    'Programme'    => t_field($programme['title']),
    'Status'       => ucfirst($status),
    'Applicant'    => (string) ($post['name'] ?? ''),
    'NIC'          => (string) ($post['nic'] ?? ''),
    'Telephone'    => (string) ($post['phone'] ?? ''),
    'Email'        => (string) ($post['email'] ?? ''),
    'District'     => (string) ($post['district'] ?? ''),
    'Society'      => (string) ($post['society'] ?? ''),
    'Participants' => (string) ($post['participants'] ?? ''),
    'Residential'  => empty($post['residential']) ? 'No' : 'Yes',
];

ob_start(); ?>
<p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#3b4a42">
  An application has been received through the website. It is in the booking queue in the administration console, where it can be confirmed, waitlisted or declined.
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;line-height:1.6">
  <?php foreach ($rows as $label => $value): if (trim((string) $value) === '') { continue; } ?>
    <tr>
      <td style="padding:6px 12px 6px 0;color:#5b6b62;white-space:nowrap;vertical-align:top"><?= esc($label) ?></td>
      <td style="padding:6px 0;color:#17231d;font-weight:600"><?= esc($value) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php if (! empty($post['notes'])): ?>
  <p style="margin:18px 0 0;font-size:13px;color:#5b6b62">Notes from the applicant</p>
  <p style="margin:4px 0 0;font-size:14px;line-height:1.6;color:#17231d"><?= nl2br(esc((string) $post['notes'])) ?></p>
<?php endif; ?>
<?php $body = ob_get_clean();

echo view('Modules\Tshda\Views\emails\_layout', [
    'heading' => 'Training application ' . $reference,
    'body'    => $body,
], ['saveData' => false]);
