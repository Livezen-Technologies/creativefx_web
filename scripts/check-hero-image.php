<?php

/**
 * Does promoting an image to the hero actually change the home page?
 *
 * This is the check the feature was missing. `spark hero:images` carried its own
 * copy of the "folder hero OR tag hero" query while the home page read no media
 * at all, so the command reported an image as promoted, printed it in a table,
 * and the front page never changed. Nothing was wrong with the command; nothing
 * was reading it.
 *
 * So the assertion here is deliberately made against the rendered HTML rather
 * than the model: the model returning a row proves nothing about the page.
 *
 * Start the server first:
 *   PHP_CLI_SERVER_WORKERS=6 php spark serve --port 8080
 *   php scripts/check-hero-image.php
 *
 * It writes to the database and to public/media, and removes both afterwards.
 */

define('FCPATH', dirname(__DIR__) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
define('ENVIRONMENT', 'development');
CodeIgniter\Boot::bootConsole($paths);

use Modules\Media\Models\MediaModel;

$base = getenv('BASE_URL') ?: 'http://127.0.0.1:8080';
$db   = db_connect();
$now  = date('Y-m-d H:i:s');

$fail  = 0;
$check = static function (string $what, $got, $want) use (&$fail): void {
    $ok = $got === $want;
    printf("  %-52s %s (got %s, want %s)\n", $what, $ok ? 'ok' : 'FAIL', var_export($got, true), var_export($want, true));
    $fail += $ok ? 0 : 1;
};

// Entity-decoded, because esc(..., 'attr') encodes even the slashes in a URL
// — valid HTML that every browser reads correctly, and unreadable to a
// substring match. Asserting against the decoded form keeps the expectations
// written the way somebody would write them by hand.
/**
 * Does the hero section carry the `isolate` class?
 *
 * Matched inside the class attribute rather than as the literal string
 * "relative isolate": the first version of this asserted the two were adjacent
 * and started failing the moment layout utilities were added between them,
 * which is a test reporting on the order somebody typed class names in.
 */
$isolated = static fn (string $html): bool => (bool) preg_match(
    '/<section[^>]*class="[^"]*\bisolate\b/',
    $html
);

$home = static fn (): string => html_entity_decode(
    (string) @file_get_contents($base . '/en'),
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
);

// A stand-in photograph. Deliberately not the real one: this asserts the
// plumbing, and a check that depends on a particular picture being present
// starts failing the day somebody changes it.
$dir  = FCPATH . 'media/hero';
$rel  = 'media/hero/check-hero-image.jpg';
@mkdir($dir, 0775, true);
$im = imagecreatetruecolor(1600, 1200);
imagefilledrectangle($im, 0, 0, 1600, 1200, imagecolorallocate($im, 210, 140, 40));
imagejpeg($im, FCPATH . $rel, 82);
imagedestroy($im);

$media = new MediaModel();
$db->table('media_library')->where('path', $rel)->delete();

try {
    // ── Nothing promoted ────────────────────────────────────────────────────
    $before = $home();
    $check('with no hero image, no <img> in the hero',
        str_contains($before, 'fetchpriority="high"'), false);
    // The aurora is an opaque panel — its background stack ends in a bare
    // rgb(var(--bg)) — so with no photograph it is the hero's backdrop, and
    // with one it would simply cover it.
    $check('and the aurora is the backdrop',
        str_contains($before, 'hero-aurora'), true);
    $check('and nothing is isolated',
        $isolated($before), false);
    $check('the headline is still there',
        str_contains($before, 'site-hero'), true);

    // ── Promoted by folder ──────────────────────────────────────────────────
    $db->table('media_library')->insert([
        'disk' => 'public', 'path' => $rel, 'url' => '/' . $rel,
        'original_name' => 'check-hero-image.jpg', 'mime_type' => 'image/jpeg',
        'size_bytes' => filesize(FCPATH . $rel), 'width' => 1600, 'height' => 1200,
        'alt' => 'A designer retouching a portrait on a wide monitor',
        'folder' => 'hero', 'created_at' => $now, 'updated_at' => $now,
    ]);
    $id = (int) $db->insertID();

    $after = $home();
    $check('promoting one puts an <img> in the hero',
        str_contains($after, 'fetchpriority="high"'), true);
    // Three things the photograph needs in order to be seen at all, each of
    // which was missing at some point while this was written.
    $check('the section is isolated so -z layers paint',
        $isolated($after), true);
    $check('the opaque aurora steps aside',
        str_contains($after, 'hero-aurora'), false);
    $check('a scrim is laid over the photograph',
        str_contains($after, 'bg-gradient-to-r from-brand-black'), true);
    $check('the file is the one that was promoted',
        str_contains($after, '/' . $rel), true);
    // The bug this check was written and immediately caught: `path` has no
    // leading slash, so used raw the src is relative and 404s on /en.
    $check('and its src is site-absolute, not relative',
        (bool) preg_match('#src="/' . preg_quote($rel, '#') . '#', $after), true);
    $check('its dimensions are reserved',
        str_contains($after, 'width="1600" height="1200"'), true);
    $check('its alt text is carried through',
        str_contains($after, 'alt="A designer retouching a portrait on a wide monitor"'), true);
    $check('the dates strip survives',
        str_contains($after, 'hero'), true);

    // ── A tag promotes it just as well as the folder ─────────────────────────
    $media->update($id, ['folder' => 'uploads', 'tags' => 'photography,hero']);
    $check('the hero tag promotes it too',
        str_contains($home(), '/' . $rel), true);

    // "heroine" is not a request to put a photograph on the front page.
    $media->update($id, ['tags' => 'heroine']);
    $check('a tag that merely contains "hero" does not',
        str_contains($home(), '/' . $rel), false);

    // ── A row whose file has gone must not render broken ─────────────────────
    $media->update($id, ['folder' => 'hero', 'tags' => null]);
    $check('back on the page before the file is removed',
        str_contains($home(), '/' . $rel), true);

    // The assertion markup cannot make: does the photograph actually paint?
    // Everything above passes while the image is completely hidden — which it
    // was, twice, first under the section's own background and then under the
    // opaque `.hero-aurora`. Handed off to a browser because it takes a
    // screenshot to answer.
    echo "\n";
    $node = escapeshellcmd((string) (getenv('NODE_BIN') ?: 'node'));
    passthru(sprintf(
        '%s %s %s %s 2>&1',
        $node,
        escapeshellarg(dirname(__DIR__) . '/scripts/check-hero-visible.mjs'),
        escapeshellarg($base),
        escapeshellarg(FCPATH . $rel)
    ), $visibleStatus);
    $check('the photograph reaches the screen', $visibleStatus === 0, true);
    echo "\n";

    // check-hero-visible.mjs overwrote the file with its own probes.
    imagejpeg((static function () { $im = imagecreatetruecolor(1600, 1200);
        imagefilledrectangle($im, 0, 0, 1600, 1200, imagecolorallocate($im, 210, 140, 40));
        return $im; })(), FCPATH . $rel, 82);
    unlink(FCPATH . $rel);
    $check('a promoted row with no file on disk is skipped',
        str_contains($home(), '/' . $rel), false);
    $check('and the hero falls back to the aurora',
        str_contains($home(), 'hero-aurora'), true);
} finally {
    $db->table('media_library')->where('path', $rel)->delete();
    @unlink(FCPATH . $rel);
    @rmdir($dir);
}

echo "\n", $fail === 0 ? "all checks passed\n" : "$fail check(s) FAILED\n";
exit($fail === 0 ? 0 : 1);
