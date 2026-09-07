<?php
helper(['norlanka', 'url']);

/**
 * The shell every transactional email is rendered into.
 *
 * Tables and inline styles, because an email client is not a browser: Outlook
 * ignores most of a stylesheet, Gmail strips <style> in some contexts, and a
 * layout built on flexbox arrives as a column of unstyled text. This is the one
 * place in the codebase where that markup is correct rather than lazy.
 *
 * @var string $title
 * @var string $body   already-escaped HTML
 * @var string $school
 */
?>
<!DOCTYPE html>
<html lang="<?= esc(current_locale()) ?>">
<head><meta charset="UTF-8"><title><?= esc($title) ?></title></head>
<body style="margin:0;padding:0;background:#f4f5fa;font-family:Helvetica,Arial,sans-serif;color:#141a2e;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5fa;padding:24px 12px;">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
        <tr><td style="background:#3f35c7;padding:20px 28px;">
            <span style="color:#ffffff;font-size:18px;font-weight:bold;letter-spacing:0.4px;"><?= esc($school) ?></span>
        </td></tr>
        <tr><td style="padding:28px;font-size:15px;line-height:1.65;">
            <?= $body ?>
        </td></tr>
        <tr><td style="padding:18px 28px;background:#f4f5fa;font-size:12px;color:#55607a;line-height:1.6;">
            <?= esc($school) ?><?php if ($email = setting('email', '', 'contact')): ?> &middot; <a href="mailto:<?= esc($email, 'attr') ?>" style="color:#3f35c7;"><?= esc($email) ?></a><?php endif; ?><br>
            <a href="<?= esc(base_url(), 'attr') ?>" style="color:#3f35c7;"><?= esc(preg_replace('~^https?://~', '', rtrim(base_url(), '/'))) ?></a>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
