<?php
/**
 * The sitemap document.
 *
 * Written as a view rather than string-built in the controller so the XML is
 * readable as XML. Nothing here is user-facing copy, but every value is escaped
 * anyway: a slug reaches this file from the database, and an ampersand in one
 * would otherwise produce a document no parser will accept.
 *
 * @var list<array{loc:string,alternates:array<string,string>,lastmod:?string,priority:string,changefreq:string}> $entries
 */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($entries as $e): ?>
    <url>
        <loc><?= htmlspecialchars($e['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
<?php     foreach ($e['alternates'] as $locale => $href): ?>
        <xhtml:link rel="alternate" hreflang="<?= htmlspecialchars($locale, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($href, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>"/>
<?php     endforeach; ?>
<?php     if ($e['lastmod'] !== null): ?>
        <lastmod><?= htmlspecialchars($e['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></lastmod>
<?php     endif; ?>
        <changefreq><?= htmlspecialchars($e['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></changefreq>
        <priority><?= htmlspecialchars($e['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
