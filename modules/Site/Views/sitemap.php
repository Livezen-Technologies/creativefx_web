<?php
helper(['url', 'norlanka']);

/**
 * One section's URLs, each emitted once per locale with xhtml:link alternates.
 *
 * The alternates are what stop /en/course/x and /si/course/x competing with
 * each other as duplicates. Every URL in a set must list every alternate,
 * including itself — a set where one member omits the others is ignored
 * silently, which is the usual way hreflang fails.
 *
 * lastmod comes from the row. Where a row has none — the static pages — the
 * element is omitted rather than filled with today's date: a sitemap that says
 * everything changed this morning is one a crawler learns to disregard.
 *
 * @var list<array{path:string, lastmod:?string, priority:string}> $urls
 * @var list<string> $locales
 */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$base = rtrim(base_url(), '/');
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($urls as $url):
    $path = trim((string) $url['path'], '/');
    foreach ($locales as $locale):
        $loc = $base . '/' . $locale . ($path !== '' ? '/' . $path : ''); ?>
    <url>
        <loc><?= esc($loc, 'url') ?></loc>
<?php   if (! empty($url['lastmod'])): ?>
        <lastmod><?= esc(substr((string) $url['lastmod'], 0, 10)) ?></lastmod>
<?php   endif; ?>
        <priority><?= esc($url['priority']) ?></priority>
<?php   foreach ($locales as $alt): ?>
        <xhtml:link rel="alternate" hreflang="<?= esc($alt, 'attr') ?>" href="<?= esc($base . '/' . $alt . ($path !== '' ? '/' . $path : ''), 'attr') ?>"/>
<?php   endforeach; ?>
    </url>
<?php endforeach; endforeach; ?>
</urlset>
