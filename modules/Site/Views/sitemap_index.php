<?php
helper('url');
/**
 * The sitemap index: one entry per section file.
 *
 * @var list<array{loc:string, lastmod:string}> $sections
 */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($sections as $section): ?>
    <sitemap>
        <loc><?= esc($section['loc'], 'url') ?></loc>
        <lastmod><?= esc($section['lastmod']) ?></lastmod>
    </sitemap>
<?php endforeach; ?>
</sitemapindex>
