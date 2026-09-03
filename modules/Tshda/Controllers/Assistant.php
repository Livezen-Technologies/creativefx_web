<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Tshda\Libraries\SiteSearch;
use Modules\Tshda\Models\FaqModel;

/**
 * The help assistant's one endpoint.
 *
 * It answers out of the Authority's own published content — the FAQ first,
 * because a question deserves an answer rather than a link, and then everything
 * the site search reaches. There is no third-party service behind it, no model,
 * and nothing leaves the server: a government portal should not need an
 * external chat vendor to tell somebody which form to fill in, and a visitor
 * asking about their subsidy should not have that question sent to one.
 *
 * That also fixes what it can honestly claim. It is not a person and it does
 * not pretend to be one — the widget says so, and when it has no answer it says
 * that too and offers the ways to reach a human, rather than guessing.
 *
 * GET, not POST: this reads and changes nothing, so it needs no CSRF token and
 * can be cached and retried freely.
 */
class Assistant extends BaseController
{
    /** How many links to offer beside an answer. */
    private const LINKS = 4;

    /** Below this, a FAQ is a coincidence rather than a match. */
    private const ANSWER_THRESHOLD = 2.0;

    public function ask(?string $locale = null): ResponseInterface
    {
        helper(['norlanka', 'url']);

        $locale = $locale ?: current_locale();
        $query  = trim((string) $this->request->getGet('q'));

        if ($query === '' || mb_strlen($query) > 300) {
            return $this->response->setJSON([
                'answer'  => null,
                'results' => [],
                'empty'   => true,
            ]);
        }

        $faq = $this->bestFaq($query);

        $results = $this->search($query, $locale);

        return $this->response->setJSON([
            'answer'   => $faq === null ? null : $faq['answer'],
            'source'   => $faq === null ? null : $faq['url'],
            'results'  => $results,
            'searchUrl' => locale_url('search') . '?q=' . rawurlencode($query),
            'empty'    => $faq === null && $results === [],
        ]);
    }

    /**
     * Pages worth offering for what was typed.
     *
     * SiteSearch matches the whole query as one substring, which is right for
     * the search page — somebody types "replanting subsidy" and means those two
     * words together. It is wrong here, because people type whole questions at
     * a chat box: "how much is the replanting subsidy?" contains the phrase
     * nowhere and returns nothing at all.
     *
     * So: try the phrase first, because an exact match is the best answer there
     * is, and only if that comes up short fall back to the content words,
     * merging what each finds and ranking a page that answers to more of the
     * question above one that answers to less. SiteSearch itself is left alone
     * — changing it would quietly change the search page's meaning too.
     *
     * @return list<array{title:string,url:string,type:string}>
     */
    private function search(string $query, string $locale): array
    {
        try {
            $search = new SiteSearch();
            $found  = $this->present(SiteSearch::sort($search->run($query, $locale), 'relevance'));

            if (count($found) >= self::LINKS) {
                return array_slice($found, 0, self::LINKS);
            }

            $merged = [];
            foreach ($found as $row) {
                $merged[$row['url']] = $row + ['hits' => 99];   // the phrase itself outranks any word
            }

            foreach ($this->keywords($query) as $word) {
                foreach ($this->present(SiteSearch::sort($search->run($word, $locale), 'relevance')) as $row) {
                    if (isset($merged[$row['url']])) {
                        $merged[$row['url']]['hits']++;
                    } else {
                        $merged[$row['url']] = $row + ['hits' => 1];
                    }
                }
            }

            uasort($merged, static fn (array $a, array $b): int => $b['hits'] <=> $a['hits']);

            $out = [];
            foreach (array_slice($merged, 0, self::LINKS) as $row) {
                unset($row['hits']);
                $out[] = $row;
            }

            return $out;
        } catch (\Throwable $e) {
            // A broken search must not take the assistant down with it: an
            // answer from the FAQ is still worth returning on its own.
            log_message('error', 'Assistant search failed: ' . $e->getMessage());

            return [];
        }
    }

    /** @return list<array{title:string,url:string,type:string}> */
    private function present(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'title' => (string) $row['title'],
                'url'   => (string) $row['url'],
                'type'  => (string) $row['type'],
            ];
        }

        return $out;
    }

    /**
     * The words in a question that carry its meaning.
     *
     * The English stop list is short on purpose — it holds the words that turn
     * a request into a question ("how", "what", "can I") and nothing else,
     * because a longer list starts throwing away terms that matter in this
     * domain. Sinhala and Tamil get no list: the length filter does the work,
     * and guessing at a stop list for a language from the outside is how you
     * end up dropping the word somebody actually searched for.
     *
     * @return list<string>
     */
    private function keywords(string $query, int $max = 4): array
    {
        static $stop = [
            'the', 'and', 'for', 'you', 'your', 'can', 'how', 'what', 'when', 'where',
            'who', 'why', 'does', 'did', 'are', 'was', 'were', 'have', 'has', 'had',
            'with', 'from', 'about', 'this', 'that', 'there', 'they', 'them', 'get',
            'much', 'many', 'need', 'want', 'please', 'tell', 'give', 'any', 'all',
        ];

        $words = preg_split('/\s+/u', SiteSearch::normalise($query)) ?: [];
        $keep  = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 4 || in_array($word, $stop, true)) {
                continue;
            }
            if (! in_array($word, $keep, true)) {
                $keep[] = $word;
            }
        }

        // Longest first: the most specific word is the one most worth a query
        // when only a few are run.
        usort($keep, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return array_slice($keep, 0, $max);
    }

    /**
     * The published question that best matches what was typed.
     *
     * Scored on the words the query and the question share, with a whole-phrase
     * match counting for much more than scattered words — the same shape as the
     * site search's ranking, so the assistant and the search results page agree
     * about what is relevant instead of disagreeing in public.
     *
     * Matching runs on SiteSearch::normalise's output, which is what makes this
     * work in Sinhala and Tamil: the same word arrives spelled two ways
     * depending on the keyboard, and the raw strings would not compare equal.
     */
    private function bestFaq(string $query): ?array
    {
        $needle = SiteSearch::normalise($query);
        if ($needle === '') {
            return null;
        }

        $words = array_values(array_filter(
            preg_split('/\s+/u', $needle) ?: [],
            static fn (string $w): bool => mb_strlen($w) > 2
        ));
        if ($words === []) {
            return null;
        }

        $best      = null;
        $bestScore = 0.0;

        try {
            $rows = (new FaqModel())->where('status', 'published')->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'Assistant FAQ lookup failed: ' . $e->getMessage());

            return null;
        }

        foreach ($rows as $row) {
            $question = SiteSearch::normalise(t_field($row['question']));
            $answer   = SiteSearch::normalise(t_field($row['answer']));
            if ($question === '') {
                continue;
            }

            $score = 0.0;
            if (str_contains($question, $needle)) {
                $score += 6.0;
            }
            foreach ($words as $word) {
                if (str_contains($question, $word)) {
                    $score += 1.5;
                } elseif (str_contains($answer, $word)) {
                    $score += 0.5;
                }
            }

            // A question that matches every word beats one that matches some,
            // even when the second is longer and accumulates more hits.
            $hits = 0;
            foreach ($words as $word) {
                if (str_contains($question . ' ' . $answer, $word)) {
                    $hits++;
                }
            }
            if ($hits === count($words)) {
                $score += 2.0;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $row;
            }
        }

        if ($best === null || $bestScore < self::ANSWER_THRESHOLD) {
            return null;
        }

        return [
            'answer' => t_field($best['answer']),
            'url'    => locale_url('faqs') . '#faq-' . $best['id'],
        ];
    }
}
