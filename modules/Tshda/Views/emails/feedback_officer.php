<?php
helper('norlanka');

$rows = [
    'Reference' => $reference,
    'Type'      => ucfirst($kind),
    'From'      => (string) ($post['name'] ?? ''),
    'Email'     => (string) ($post['email'] ?? ''),
    'Telephone' => (string) ($post['phone'] ?? ''),
    'District'  => (string) ($post['district'] ?? ''),
    'Subject'   => (string) ($post['subject'] ?? ''),
];

ob_start(); ?>
<p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#3b4a42">
  A submission has been received through the website. It is in the feedback queue in the administration console, with a response due date attached.
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;line-height:1.6">
  <?php foreach ($rows as $label => $value): if (trim((string) $value) === '') { continue; } ?>
    <tr>
      <td style="padding:6px 12px 6px 0;color:#5b6b62;white-space:nowrap;vertical-align:top"><?= esc($label) ?></td>
      <td style="padding:6px 0;color:#17231d;font-weight:600"><?= esc($value) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<p style="margin:18px 0 0;font-size:13px;color:#5b6b62">Message</p>
<p style="margin:4px 0 0;font-size:14px;line-height:1.6;color:#17231d"><?= nl2br(esc((string) ($post['message'] ?? ''))) ?></p>
<?php $body = ob_get_clean();

echo view('Modules\Tshda\Views\emails\_layout', [
    'heading' => ucfirst($kind) . ' ' . $reference,
    'body'    => $body,
], ['saveData' => false]);
