<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * Courses — the evergreen half of the course/session split.
 *
 * A course has no dates and no price. Everything time-bound or costed hangs off
 * `course_sessions`; see CourseSessionModel. Keeping the two apart is what lets
 * one page rank for "Photoshop training" for years while the dates under it
 * turn over every month.
 */
class CourseModel extends Model
{
    protected $table          = 'courses';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'category_id', 'slug', 'title', 'subtitle', 'summary', 'description',
        'level', 'duration_hours', 'duration_days', 'software_version',
        'certification_alignment', 'hero_image', 'pillar', 'default_mode',
        'price_band', 'is_featured', 'rating_avg', 'rating_count',
        'seo_title', 'seo_description', 'seo_keywords', 'is_custom',
        'status', 'published_at', 'created_by',
    ];

    /** Published, in the order the catalogue presents them. */
    public function live(): self
    {
        $this->where('courses.status', 'published')
            ->groupStart()
                ->where('courses.published_at IS NULL')
                ->orWhere('courses.published_at <=', date('Y-m-d H:i:s'))
            ->groupEnd();

        return $this;
    }

    public function findLive(string $slug): ?array
    {
        return $this->live()->where('courses.slug', $slug)->first();
    }

    /** @return list<array> */
    public function byPillar(string $pillar, int $limit = 0): array
    {
        return $this->live()->where('courses.pillar', $pillar)
            ->orderBy('courses.level', 'ASC')->orderBy('courses.id', 'ASC')
            ->findAll($limit ?: null);
    }

    /** @return list<array> */
    public function featured(int $limit = 6): array
    {
        return $this->live()->where('courses.is_featured', 1)
            ->orderBy('courses.id', 'ASC')->findAll($limit);
    }

    /**
     * The faceted catalogue.
     *
     * An unknown facet value is not silently ignored — the controller turns it
     * into a 404 — because `/courses?level=nonsense` returning everything looks
     * exactly like a filter that matched the whole catalogue.
     *
     * `mode` filters on the delivery modes a course *offers*, not on whether a
     * date happens to be scheduled: a course available in person with no
     * classroom date this month is still an in-person course, and hiding it
     * loses the enquiry.
     *
     * @param array{category?:string, pillar?:string, level?:int, mode?:string, q?:string} $filters
     * @return array{rows:list<array>, total:int}
     */
    public function catalogue(array $filters, int $perPage = 12, int $page = 1): array
    {
        $build = function () use ($filters) {
            $q = $this->live();

            if (! empty($filters['pillar'])) {
                $q->where('courses.pillar', $filters['pillar']);
            }
            if (! empty($filters['category_ids'])) {
                $q->whereIn('courses.category_id', $filters['category_ids']);
            }
            if (! empty($filters['level'])) {
                $q->where('courses.level', (int) $filters['level']);
            }
            if (! empty($filters['mode'])) {
                $q->whereIn('courses.id', function ($sub) use ($filters) {
                    return $sub->select('course_id')->from('course_delivery_modes')
                        ->where('mode', $filters['mode']);
                });
            }
            if (! empty($filters['q'])) {
                // title and summary are JSON locale maps, so a LIKE hits every
                // language at once. That is the intended behaviour: somebody
                // searching in Sinhala for a course whose Sinhala title exists
                // should find it, and the English title is in the same column.
                $q->groupStart()
                    ->like('courses.title', $filters['q'])
                    ->orLike('courses.summary', $filters['q'])
                    ->orLike('courses.slug', $filters['q'])
                  ->groupEnd();
            }

            return $q;
        };

        $total = $build()->countAllResults();

        $rows = $build()
            ->orderBy('courses.pillar', 'ASC')
            ->orderBy('courses.level', 'ASC')
            ->orderBy('courses.id', 'ASC')
            ->findAll($perPage, max(0, ($page - 1) * $perPage));

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Everything the course page renders, in one call.
     *
     * Six small queries rather than one join: the lists are one-to-many in six
     * different directions, and joining them all produces a cartesian product
     * that has to be unpicked in PHP anyway — slower, and the kind of code
     * nobody wants to change later.
     *
     * @return array<string, mixed>
     */
    public function detail(int $courseId): array
    {
        $db = $this->db;

        $lists = [];
        foreach (['outcomes' => 'course_outcomes', 'prerequisites' => 'course_prerequisites', 'audiences' => 'course_audiences'] as $key => $table) {
            $lists[$key] = $db->table($table)->where('course_id', $courseId)
                ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        }

        $includes = $db->table('course_includes')->where('course_id', $courseId)
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();

        $modules = $db->table('course_modules')->where('course_id', $courseId)
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();

        if ($modules !== []) {
            $topics = $db->table('course_topics')
                ->whereIn('module_id', array_column($modules, 'id'))
                ->orderBy('sort_order', 'ASC')->get()->getResultArray();
            foreach ($modules as &$module) {
                $module['topics'] = array_values(array_filter(
                    $topics,
                    static fn (array $t): bool => (int) $t['module_id'] === (int) $module['id']
                ));
            }
            unset($module);
        }

        $faqs = $db->table('course_faqs')->where('course_id', $courseId)
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();

        $modes = array_column(
            $db->table('course_delivery_modes')->where('course_id', $courseId)->get()->getResultArray(),
            'mode'
        );

        $instructors = $db->table('course_instructor ci')
            ->select('i.*')
            ->join('instructors i', 'i.id = ci.instructor_id')
            ->where('ci.course_id', $courseId)
            ->where('i.status', 'published')
            ->orderBy('ci.sort_order', 'ASC')
            ->get()->getResultArray();

        $related = $db->table('course_related cr')
            ->select('c.id, c.slug, c.title, c.summary, c.hero_image, c.level, c.pillar, c.duration_days')
            ->join('courses c', 'c.id = cr.related_id')
            ->where('cr.course_id', $courseId)
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            ->orderBy('cr.sort_order', 'ASC')
            ->get()->getResultArray();

        return $lists + [
            'includes'    => $includes,
            'modules'     => $modules,
            'faqs'        => $faqs,
            'modes'       => $modes,
            'instructors' => $instructors,
            'related'     => $related,
        ];
    }

    /**
     * Recompute a course's rating from its approved reviews.
     *
     * Called when a review is approved or withdrawn, never seeded and never
     * typed. `AggregateRating` in structured data is a claim to Google and to a
     * buyer; a number that no reviewer produced is a lie in a rich snippet.
     */
    public function refreshRating(int $courseId): void
    {
        $row = $this->db->table('course_reviews')
            ->select('COUNT(*) AS n, COALESCE(AVG(rating), 0) AS avg', false)
            ->where('course_id', $courseId)->where('status', 'approved')
            ->get()->getRowArray();

        $this->db->table('courses')->where('id', $courseId)->update([
            'rating_count' => (int) ($row['n'] ?? 0),
            'rating_avg'   => round((float) ($row['avg'] ?? 0), 2),
        ]);
    }
}
