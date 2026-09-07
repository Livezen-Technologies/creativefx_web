<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Every CMS page renders the prose it stores.
 *
 * A block renderer that drops a payload does not fail. The page returns 200
 * with the right title, the right heading and the right breadcrumbs, and simply
 * has no words in it — which is invisible to a status sweep, to a JavaScript
 * error check, and to a word-count floor, because the pages that are legitimately
 * sparse (an empty careers list, a sign-in form) are shorter than a policy page
 * with all its prose dropped.
 *
 * That is not hypothetical. Every one of the twenty-five richtext blocks on this
 * site stores its text under `body`, the partial read only `text`, and the terms
 * of service, the privacy notice, the refund, reschedule and accessibility
 * policies, About and Why MyLearnPlus were all live, all 200, and all empty for
 * the whole of this build.
 *
 * So the question is asked the only way that separates the two cases: take what
 * the page has *stored*, and check it comes back out. A page storing five
 * thousand characters of prose and rendering three hundred is broken; a page
 * storing nothing and rendering nothing is fine.
 *
 *     php spark check:cms [baseUrl]
 */
class CheckCmsPages extends BaseCommand
{
    protected $group       = 'Checks';
    protected $name        = 'check:cms';
    protected $description = 'Fail if a CMS page stores prose it does not render.';
    protected $usage       = 'check:cms [baseUrl]';

    /**
     * How much of the stored prose has to survive to the page.
     *
     * Not 100%: a block payload carries markup, and a long body is legitimately
     * truncated in places. Well under half is not truncation, it is a block
     * that never rendered.
     */
    private const MIN_RATIO = 0.5;

    /** Below this many characters a page has nothing worth measuring. */
    private const FLOOR = 200;

    public function run(array $params): int
    {
        $base = rtrim((string) ($params[0] ?? 'http://127.0.0.1:8083'), '/');
        $db   = db_connect();

        if (! $db->tableExists('pages') || ! $db->tableExists('page_blocks')) {
            CLI::write('No CMS tables; nothing to check.', 'yellow');

            return EXIT_SUCCESS;
        }

        $locale   = config('App')->defaultLocale;
        $problems = [];
        $checked  = 0;

        foreach ($db->table('pages')->select('id, slug, is_home')->where('status', 'published')->get()->getResultArray() as $page) {
            if ((int) ($page['is_home'] ?? 0) === 1) {
                continue;
            }

            $stored = $this->storedText((int) $page['id']);
            if (mb_strlen($stored) < self::FLOOR) {
                continue;
            }

            $url      = $base . '/' . $locale . '/' . $page['slug'];
            $rendered = $this->renderedText($url);

            if ($rendered === null) {
                $problems[] = sprintf('  %-28s could not be fetched at %s', $page['slug'], $url);

                continue;
            }

            $checked++;
            $ratio = mb_strlen($rendered) / mb_strlen($stored);

            if ($ratio < self::MIN_RATIO) {
                $problems[] = sprintf(
                    '  %-28s stores %d chars of prose, renders %d (%d%%) — a block is being dropped',
                    $page['slug'],
                    mb_strlen($stored),
                    mb_strlen($rendered),
                    (int) round($ratio * 100)
                );
            }
        }

        if ($problems === []) {
            CLI::write(sprintf('All %d CMS page(s) render the prose they store.', $checked), 'green');

            return EXIT_SUCCESS;
        }

        CLI::error(sprintf('%d CMS page(s) do not render what they store:', count($problems)));
        foreach ($problems as $line) {
            CLI::write($line);
        }

        return EXIT_ERROR;
    }

    /** Every string in every block payload, as plain text. */
    private function storedText(int $pageId): string
    {
        $db = db_connect();

        $sectionIds = array_column(
            $db->table('page_sections')->select('id')->where('page_id', $pageId)->get()->getResultArray(),
            'id'
        );
        if ($sectionIds === []) {
            return '';
        }

        $text = '';
        foreach ($db->table('page_blocks')->select('content')->whereIn('section_id', $sectionIds)->get()->getResultArray() as $block) {
            $payload = json_decode((string) $block['content'], true);
            if (! is_array($payload)) {
                continue;
            }
            array_walk_recursive($payload, static function ($value) use (&$text): void {
                if (is_string($value)) {
                    $text .= ' ' . $value;
                }
            });
        }

        return trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    }

    /** The words a reader actually sees, or null if the page could not be read. */
    private function renderedText(string $url): ?string
    {
        $html = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 20, 'ignore_errors' => true],
        ]));

        if ($html === false) {
            return null;
        }

        // <main> only: a shared header and footer would make an empty page look
        // full on every site that has navigation.
        if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $html, $m) === 1) {
            $html = $m[1];
        }

        $html = preg_replace('/<(script|style)\b.*?<\/\1>/is', ' ', $html) ?? $html;

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html))) ?? '');
    }
}
