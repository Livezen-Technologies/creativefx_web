<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Catalog\Libraries\CardPricer;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\InstructorModel;

/**
 * The faculty pages.
 *
 * These exist for one reason, and it is not decoration: "who teaches this" is
 * the question a buyer asks immediately after "what does it cost", and a
 * training site that cannot answer it is asking for several hundred pounds on
 * trust. It is also the E-E-A-T half of the SEO plan — experience, expertise,
 * authoritativeness, trustworthiness are assessed against named, evidenced
 * people rather than against adjectives.
 *
 * The awkward part, and the thing this controller is really about, is that at
 * launch there are no named people. Named trainers with photographs and
 * verifiable credentials are the school's to supply, and four invented ones
 * would be a fabrication a buyer could act on — they would pick a course
 * because of a face that does not exist. So `instructors.is_placeholder` marks
 * a row that describes a *faculty* rather than a person, and three consequences
 * follow, all of them handled here:
 *
 *   1. **The page says so, in the open.** The note is a block near the top of
 *      the profile, not a line in a footer. A placeholder that has to be
 *      noticed is a placeholder that will not be.
 *   2. **No `Person` JSON-LD.** `Schema::person()` already returns an empty
 *      array for a placeholder and `Schema::render()` drops empties, so the
 *      graph is built the same way on both kinds of profile and the honest
 *      outcome falls out of it. Nothing here tests the flag a second time; a
 *      second test is a second place to get it wrong.
 *   3. **The page still has to earn its place.** What every instructor on the
 *      track must hold before they take a class, how a cohort is staffed, and
 *      the courses actually taught — all of which are true today, and all of
 *      which are what somebody was trying to find out by asking who teaches.
 */
class Instructors extends BaseController
{
    /** Characters of biography on a listing card, cut on a word boundary. */
    private const EXCERPT = 190;

    // ── Everybody who teaches here ──────────────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $faculty = (new InstructorModel())->live()->findAll();
        $counts  = $this->courseCounts(array_map('intval', array_column($faculty, 'id')));

        $anyPlaceholder = false;
        foreach ($faculty as &$member) {
            $member['course_count'] = $counts[(int) $member['id']] ?? 0;
            $member['excerpt']      = $this->excerpt(t_field($member['bio'] ?? ''));
            $anyPlaceholder         = $anyPlaceholder || ! empty($member['is_placeholder']);
        }
        unset($member);

        $crumbs = [['label' => lang('Catalog.instructors.title')]];

        return view('Modules\Catalog\Views\instructors\index', [
            'faculty'        => $faculty,
            'anyPlaceholder' => $anyPlaceholder,
            'crumbs'         => $crumbs,
            // Person is mapped over every row rather than over the named ones:
            // the placeholders come back empty and render() discards them, so
            // at launch this emits an organisation and a breadcrumb trail and
            // no claim about anybody. The day a real trainer is entered, their
            // Person graph appears with no change here.
            'schema'         => Schema::render(array_merge(
                [Schema::organisation()],
                array_map(static fn (array $row): array => Schema::person($row), $faculty),
                [Schema::breadcrumbs($crumbs)]
            )),
            'title'           => lang('Catalog.instructors.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.instructors.meta'),
        ]);
    }

    // ── One profile ─────────────────────────────────────────────────────────

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $instructors = new InstructorModel();
        $instructor  = $instructors->findLive((string) $slug);
        if ($instructor === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $currency = current_currency();

        // Two things worth naming on one line. Reusing the model instance is
        // safe only because coursesFor() builds its own query on the connection
        // rather than on the model's builder — findLive() has already left a
        // status and a slug condition on that builder, and a second findAll()
        // through it would silently inherit them. And the prices come from
        // CardPricer rather than from three queries written again here, because
        // it is the one place that knows self-paced must be kept out of the
        // "from" figure: counted in, every card on this page read "From $49"
        // while the taught seat behind it cost fifteen times that.
        $courses = (new CardPricer())->decorate($instructors->coursesFor((int) $instructor['id']), $currency);

        $crumbs = [
            ['label' => lang('Catalog.instructors.title'), 'url' => locale_url('instructors')],
            ['label' => (string) $instructor['name']],
        ];

        return view('Modules\Catalog\Views\instructors\show', [
            'instructor'  => $instructor,
            // Decoded here so the view never parses JSON. Both columns are
            // editable text in the admin, so both are treated as untrusted
            // shapes rather than as the shape the seeder happens to write.
            'credentials' => $this->credentials($instructor['credentials_json'] ?? null),
            'links'       => $this->links($instructor['links_json'] ?? null),
            'courses'     => $courses,
            'currency'    => $currency,
            'crumbs'      => $crumbs,
            'schema'      => Schema::render([
                Schema::organisation(),
                // Empty for a placeholder, and dropped by render(). This is the
                // only place the decision is made; see the class docblock.
                Schema::person($instructor),
                Schema::breadcrumbs($crumbs),
            ]),
            'title'           => $instructor['name'] . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($instructor['headline'] ?? '') ?: $this->excerpt(t_field($instructor['bio'] ?? '')),
            'ogImage'         => ! empty($instructor['photo']) ? media_src($instructor['photo']) : null,
            'lastUpdated'     => $instructor['updated_at'] ?? null,
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * How many published courses each of these faculty teaches, in one query.
     *
     * The join to `courses` is what makes the number honest: `course_instructor`
     * keeps its row when a course is unpublished or soft-deleted, so counting
     * the pivot alone advertises a faculty as teaching four courses of which a
     * visitor can find two.
     *
     * @param  list<int> $ids
     * @return array<int,int> instructor id => course count
     */
    private function courseCounts(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = db_connect()->table('course_instructor ci')
            ->select('ci.instructor_id, COUNT(*) AS course_count', false)
            ->join('courses c', 'c.id = ci.course_id')
            ->whereIn('ci.instructor_id', $ids)
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            ->groupBy('ci.instructor_id')
            ->get()->getResultArray();

        return array_map('intval', array_column($rows, 'course_count', 'instructor_id'));
    }

    /**
     * `credentials_json` as a list of sentences.
     *
     * @return list<string>
     */
    private function credentials(?string $json): array
    {
        $decoded = json_decode((string) $json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $line) {
            // Scalars only. An editor pasting a nested structure into this
            // field would otherwise reach esc() as an array and take the page
            // down with a TypeError.
            if (is_scalar($line) && trim((string) $line) !== '') {
                $out[] = trim((string) $line);
            }
        }

        return $out;
    }

    /**
     * `links_json` as a list of `{label, url}`.
     *
     * Two shapes are accepted because both are natural to type and neither is
     * documented anywhere an editor will read: a map of label to URL, and a
     * list of objects. Anything without an http(s) URL is dropped rather than
     * rendered — a `javascript:` href on a profile page is a stored XSS, and a
     * relative one is a broken link dressed as a portfolio.
     *
     * @return list<array{label:string, url:string}>
     */
    private function links(?string $json): array
    {
        $decoded = json_decode((string) $json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $key => $value) {
            if (is_array($value)) {
                $label = (string) ($value['label'] ?? $value['title'] ?? $key);
                $url   = (string) ($value['url'] ?? $value['href'] ?? '');
            } else {
                $label = is_string($key) ? $key : (string) $value;
                $url   = (string) $value;
            }

            $url = trim($url);
            if ($url === '' || preg_match('~^https?://~i', $url) !== 1) {
                continue;
            }

            $out[] = ['label' => trim($label) !== '' ? trim($label) : $url, 'url' => $url];
        }

        return $out;
    }

    /**
     * A card-length sentence of plain text from an editorial biography.
     *
     * Tags become a space rather than nothing. `strip_tags()` deletes them, so
     * `<p>…objectives.</p><p>Classes are…` collapses to "objectives.Classes",
     * which is the kind of defect that survives review because it looks like a
     * missing space rather than like a bug.
     */
    private function excerpt(string $html, int $limit = self::EXCERPT): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/<[^>]*>/', ' ', $html)));

        if ($text === '' || mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut   = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space === false ? $cut : mb_substr($cut, 0, $space), " \t\n\r,;:.") . '…';
    }
}
