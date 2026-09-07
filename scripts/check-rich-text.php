<?php

/**
 * What can an editor put on a public page?
 *
 * rich_text() renders administrator-authored HTML — course descriptions, CMS
 * pages, blog posts — and for a while it did so through three regular
 * expressions. Five of the six obvious payloads went through them, because a
 * regular expression reads a string and a browser parses a document.
 *
 * The cases below are the record of that. Each one is asserted twice: the
 * markup that must survive has to still be there, and nothing that can execute
 * may be. Run it with:  php scripts/check-rich-text.php
 */

define('FCPATH', dirname(__DIR__) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
define('ENVIRONMENT', 'development');
CodeIgniter\Boot::bootConsole($paths);

helper('norlanka');

/** Anything a browser would run, or fetch from somewhere we did not name. */
function dangerous(string $html): bool
{
    return (bool) preg_match(
        '/<\s*(script|iframe|object|embed|svg|math|form|input|button|link|meta|base|frame|applet)\b'
        . '|\son[a-z]+\s*=|(?:href|src)\s*=\s*["\']?\s*(?:javascript|data|vbscript):/i',
        $html
    );
}

$attacks = [
    'plain script tag'            => '<p>a</p><script>alert(1)</script>',
    'slash-separated handler'     => '<p>a</p><img/onerror=alert(1) src=x>',
    'space-separated handler'     => '<p>a</p><img src=x onerror=alert(1)>',
    'quote inside javascript:'    => '<p>a</p><a href="javascript:alert(\'1\')">go</a>',
    'entity-encoded colon'        => '<p>a</p><a href="javascript&colon;alert(1)">go</a>',
    'tab inside the scheme'       => "<p>a</p><a href=\"java\tscript:alert(1)\">go</a>",
    'newline inside the scheme'   => "<p>a</p><a href=\"java\nscript:alert(1)\">go</a>",
    'uppercase scheme'           => '<p>a</p><a href="JaVaScRiPt:alert(1)">go</a>',
    'leading NUL'                 => "<p>a</p><a href=\"\x00javascript:alert(1)\">go</a>",
    'svg with a handler'          => '<p>a</p><svg/onload=alert(1)>',
    'iframe'                      => '<p>a</p><iframe src="//evil.example/x"></iframe>',
    'object'                      => '<p>a</p><object data="evil.swf"></object>',
    'data: URL image'             => '<p>a</p><img src="data:text/html;base64,PHNjcmlwdD4=">',
    'form posting elsewhere'      => '<p>a</p><form action="//evil.example"><input name="p"></form>',
    'style block'                 => '<p>a</p><style>body{display:none}</style>',
    'meta refresh'                => '<p>a</p><meta http-equiv="refresh" content="0;url=//evil.example">',
    'unclosed tag swallowing'     => '<p>a</p><div onclick=alert(1)>b',
    'nested handler in a link'    => '<p><a href="#" onmouseover="alert(1)">x</a></p>',
    'base tag rewriting links'    => '<p>a</p><base href="//evil.example/">',
];

$keep = [
    'a paragraph'        => ['<p>Hello <strong>there</strong>.</p>', 'Hello'],
    'a list'             => ['<ul><li>One</li><li>Two</li></ul>', '<li>One</li>'],
    'a heading'          => ['<h2 id="why">Why</h2>', '<h2 id="why">Why</h2>'],
    'an https link'      => ['<p><a href="https://example.com/x" title="t">go</a></p>', 'href="https://example.com/x"'],
    'a relative link'    => ['<p><a href="/en/courses">go</a></p>', 'href="/en/courses"'],
    'a mailto link'      => ['<p><a href="mailto:a@b.example">mail</a></p>', 'href="mailto:a@b.example"'],
    'an image'           => ['<p>x</p><figure><img src="/media/a.jpg" alt="A"></figure>', 'src="/media/a.jpg"'],
    'a table'            => ['<p>x</p><table><tr><th scope="col">H</th><td>C</td></tr></table>', '<th scope="col">H</th>'],
    'a blockquote'       => ['<blockquote><p>Quoted.</p></blockquote>', 'Quoted.'],
    'Sinhala prose'      => ['<p>සිංහල පෙළ</p>', 'සිංහල පෙළ'],
    'an unknown tag'     => ['<p>a</p><bold>keep these words</bold>', 'keep these words'],
];

$fail = 0;

echo "attacks — nothing executable may survive\n";
foreach ($attacks as $what => $payload) {
    $out = rich_text($payload);
    $bad = dangerous($out);
    printf("  %-28s %s%s\n", $what, $bad ? 'SURVIVES' : 'stripped', $bad ? '  ' . trim($out) : '');
    $fail += $bad ? 1 : 0;
}

echo "\ncontent — the markup an editor meant must still be there\n";
foreach ($keep as $what => [$payload, $needle]) {
    $out  = rich_text($payload);
    $gone = ! str_contains($out, $needle);
    printf("  %-28s %s%s\n", $what, $gone ? 'LOST' : 'kept', $gone ? '  ' . trim($out) : '');
    $fail += $gone ? 1 : 0;
}

// A link opening a new tab must not hand window.opener to the destination.
$rel = rich_text('<p><a href="https://example.com" target="_blank">x</a></p>');
$ok  = str_contains($rel, 'rel="noopener noreferrer"');
printf("\n  %-28s %s\n", 'target=_blank gets rel', $ok ? 'ok' : 'FAIL');
$fail += $ok ? 0 : 1;

// Plain prose must not become markup.
$plain = rich_text("Two lines\nof prose <b>not markup</b>");
$ok    = str_contains($plain, '&lt;b&gt;') && str_contains($plain, '<br');
printf("  %-28s %s\n", 'plain prose still escaped', $ok ? 'ok' : 'FAIL');
$fail += $ok ? 0 : 1;

// The blog article view carried its own copy of the old three regular
// expressions, so the same payloads are put through the rendered page — a
// helper that is safe and a view that does not call it is still an XSS.
$base = getenv('BASE_URL') ?: 'http://127.0.0.1:8080';
$db   = db_connect();
$now  = date('Y-m-d H:i:s');
$slug = 'rich-text-check';

$db->table('news_posts')->where('slug', $slug)->delete();
$db->table('news_posts')->insert([
    'slug'         => $slug,
    'title'        => json_encode(['en' => 'Rich text check']),
    'excerpt'      => json_encode(['en' => 'A post carrying every payload above.']),
    'body'         => json_encode(['en' => '<p>Intro.</p>' . implode('', $attacks)]),
    'status'       => 'published',
    'published_at' => $now,
    'created_at'   => $now,
    'updated_at'   => $now,
]);

$page = (string) @file_get_contents($base . '/en/blog/' . $slug);
$db->table('news_posts')->where('slug', $slug)->delete();

echo "\nthe rendered article page\n";
if ($page === '') {
    printf("  %-28s %s\n", 'could not fetch the page', 'SKIPPED (is the dev server up?)');
    $fail++;
} else {
    $body = $page;
    if (preg_match('#<div class="article-body">(.*?)</div>\s*(?=<)#s', $page, $m)) {
        $body = $m[1];
    }
    $bad = dangerous($body);
    printf("  %-28s %s\n", 'article body carries nothing live', $bad ? 'FAIL' : 'ok');
    $fail += $bad ? 1 : 0;

    $kept = str_contains($page, 'Intro.');
    printf("  %-28s %s\n", 'and still carries the prose', $kept ? 'ok' : 'FAIL');
    $fail += $kept ? 0 : 1;
}

echo "\n", $fail === 0 ? "all checks passed\n" : "$fail check(s) FAILED\n";
exit($fail === 0 ? 0 : 1);
