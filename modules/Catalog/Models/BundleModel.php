<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * Bootcamps and certificate programmes: several courses sold together at a
 * discount.
 *
 * The single biggest lever on average order value, and the reason the reference
 * school sells an introduction and an advanced class as one four-day bootcamp
 * rather than hoping the buyer comes back in three months. A certificate
 * programme does the same over four to six courses, and mirrors the Adobe
 * Specialty Credential pairings so the discount and the credential point the
 * same way.
 */
class BundleModel extends Model
{
    protected $table         = 'bundles';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'type', 'title', 'subtitle', 'summary', 'description',
        'hero_image', 'seo_title', 'seo_description', 'seo_keywords',
        'is_custom', 'sort_order', 'status',
    ];

    public function live(): self
    {
        $this->where('status', 'published')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');

        return $this;
    }

    public function findLive(string $slug): ?array
    {
        return $this->live()->where('slug', $slug)->first();
    }

    /** @return list<array> */
    public function ofType(string $type, int $limit = 0): array
    {
        return $this->live()->where('type', $type)->findAll($limit ?: null);
    }

    /**
     * The courses in a bundle.
     *
     * Published only, by default, and that default is load-bearing in two
     * places. A draft course has no public page, so listing one on a programme
     * gives the reader a link that 404s; and `saving()` sums these prices to
     * say what the courses cost bought separately, so counting a course nobody
     * can buy inflates the saving into a number that is not true.
     *
     * A published bundle containing a draft course is a data problem for the
     * administrator to fix, not something the shop page should paper over —
     * hence `$publishedOnly = false`, for an admin screen that needs to show
     * the whole bundle in order to point the problem out.
     *
     * @return list<array>
     */
    public function courses(int $bundleId, bool $publishedOnly = true): array
    {
        $builder = $this->db->table('bundle_items bi')
            ->select('c.*, bi.is_required, bi.sort_order AS bundle_sort')
            ->join('courses c', 'c.id = bi.course_id')
            ->where('bi.bundle_id', $bundleId)
            ->where('c.deleted_at IS NULL');

        if ($publishedOnly) {
            $builder->where('c.status', 'published');
        }

        return $builder->orderBy('bi.sort_order', 'ASC')->get()->getResultArray();
    }

    /**
     * What the bundle saves against buying its courses separately, in minor
     * units, or null when the sum cannot be worked out in this currency.
     *
     * Null rather than zero: a missing price for one course in the bundle means
     * the saving is unknown, and printing "save 0" is worse than printing
     * nothing at all.
     */
    public function saving(int $bundleId, string $currency): ?int
    {
        $bundle = $this->db->table('bundle_prices')
            ->select('price_cents')
            ->where('bundle_id', $bundleId)->where('currency', $currency)
            ->get()->getRowArray();

        if ($bundle === null) {
            return null;
        }

        $courseIds = array_column($this->courses($bundleId), 'id');
        if ($courseIds === []) {
            return null;
        }

        // The cheapest published price per course, so the saving is measured
        // against what somebody would actually have paid buying them one at a
        // time rather than against the dearest date in the calendar.
        $sum = 0;
        foreach ($courseIds as $courseId) {
            $row = $this->db->table('course_sessions cs')
                ->select('MIN(sp.price_cents) AS p', false)
                ->join('session_prices sp', 'sp.session_id = cs.id')
                ->where('cs.course_id', (int) $courseId)
                ->where('sp.currency', $currency)
                ->whereIn('cs.status', ['open', 'confirmed', 'waitlist'])
                ->get()->getRowArray();

            if ($row === null || $row['p'] === null) {
                return null;
            }
            $sum += (int) $row['p'];
        }

        return max(0, $sum - (int) $bundle['price_cents']);
    }
}
