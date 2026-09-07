<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Modules\Site\Libraries\SiteSearch;

/**
 * The help assistant's lookup.
 *
 * What it is: a search over this site's own content, presented as a
 * conversation. What it is not, and must never pretend to be: a model, a person
 * or a promise.
 *
 * Three rules, all of them about honesty rather than technique:
 *
 *   **Every answer is something somebody here wrote.** It reads the course FAQs
 *   first, then the whole site. It never generates a sentence, so it can never
 *   invent a price, a date or a refund policy — which is exactly what an
 *   improvised answer on a commercial site would eventually do.
 *
 *   **Nothing leaves the server.** No third-party script, no API call. Somebody
 *   asking whether the course covers something embarrassing is not thereby
 *   telling a chat vendor about it.
 *
 *   **When it does not know, it says so.** No near-misses dressed up as
 *   answers; it offers the contact page instead, because a person can answer
 *   properly and a wrong answer costs more than no answer.
 *
 * GET, because it reads and changes nothing — which also means it needs no CSRF
 * token to reach from a widget on a cached page.
 */
class Assistant extends BaseController
{
    private const MAX_QUESTION = 300;

    public function ask(?string $locale = null)
    {
        // catalog too: bestFaq() builds a course URL, and a helper loaded
        // inside the responder would be loaded after the code that needs it.
        helper(['norlanka', 'url', 'catalog']);

        $question = trim((string) $this->request->getGet('q'));
        if ($question === '' || mb_strlen($question) > self::MAX_QUESTION) {
            return $this->respond(['answer' => null, 'links' => []]);
        }

        // The widget is on every page, so this endpoint is as public as the
        // site is. Throttled per address so it cannot be used to walk the
        // catalogue faster than a crawler would.
        if (service('throttler')->check(md5('assistant-' . $this->request->getIPAddress()), 30, MINUTE) === false) {
            return $this->respond(['answer' => lang('Site.assistant.error'), 'links' => []]);
        }

        $faq = $this->bestFaq($question);
        if ($faq !== null) {
            return $this->respond([
                'answer'     => $faq['answer'],
                'linksLabel' => lang('Site.assistant.source'),
                'links'      => $faq['links'],
            ]);
        }

        $found = (new SiteSearch())->search($question, 4, 1);
        if ($found['results'] !== []) {
            return $this->respond([
                'answer'     => lang('Site.assistant.related'),
                'linksLabel' => '',
                'links'      => array_map(static fn (array $r): array => [
                    'title' => $r['title'],
                    'url'   => $r['url'],
                ], $found['results']),
            ]);
        }

        // Nothing. Said plainly, with the way to reach a person.
        return $this->respond([
            'answer'     => lang('Site.assistant.none') . ' ' . lang('Site.assistant.none_help'),
            'linksLabel' => '',
            'links'      => [[
                'title' => lang('Site.nav.contact'),
                'url'   => locale_url('contact'),
            ]],
        ]);
    }

    /**
     * The best-matching course FAQ, if one clearly matches.
     *
     * "Clearly" is doing work: a question has to share most of its content
     * words with the FAQ's question, not merely one of them. A loose match
     * returns an answer about the wrong thing, which is worse than returning
     * nothing, because the reader believes it.
     *
     * @return array{answer:string, links:list<array{title:string,url:string}>}|null
     */
    private function bestFaq(string $question): ?array
    {
        $db = db_connect();
        if (! $db->tableExists('course_faqs')) {
            return null;
        }

        $search = new SiteSearch();
        $words  = $search->keywords($question);
        if ($words === []) {
            return null;
        }

        $rows = $db->table('course_faqs cf')
            ->select('cf.question, cf.answer, c.slug, c.title')
            ->join('courses c', 'c.id = cf.course_id')
            ->where('c.status', 'published')->where('c.deleted_at IS NULL')
            ->get()->getResultArray();

        $best      = null;
        $bestScore = 0.0;

        foreach ($rows as $row) {
            $faqWords = $search->keywords(strip_tags(t_field($row['question'])));
            if ($faqWords === []) {
                continue;
            }

            $shared = count(array_intersect($words, $faqWords));
            // Scored against the *question asked*, not the FAQ, so a long FAQ
            // title cannot win by containing more words to match against.
            $score = $shared / count($words);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $row;
            }
        }

        // Two thirds of the words, and at least two of them. Tuned to refuse
        // rather than to guess.
        if ($best === null || $bestScore < 0.66 || count($words) < 2) {
            return null;
        }

        return [
            'answer' => trim(strip_tags(t_field($best['answer']))),
            'links'  => [[
                'title' => t_field($best['title']),
                'url'   => course_url($best['slug']),
            ]],
        ];
    }

    private function respond(array $payload)
    {
        return $this->response
            ->setContentType('application/json')
            // Nothing here should sit in a shared cache: the answer depends on
            // the question, and the question is somebody's.
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($payload + ['answer' => null, 'linksLabel' => '', 'links' => []]);
    }
}
