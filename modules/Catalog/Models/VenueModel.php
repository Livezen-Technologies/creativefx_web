<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * Where a class happens — a classroom in a city, or the virtual room a live
 * online class runs in.
 *
 * Both kinds are venues because a session needs a timezone whether or not
 * anybody travels to it, and because the timezone is the thing that makes a
 * schedule readable to somebody three hours away.
 *
 * The city landing pages are built from these rows. `body` is each city's own
 * prose: the blueprint is explicit that templated location pages are spam, and
 * four near-identical pages with the city name swapped is precisely what a
 * search engine is looking for when it decides a site is thin.
 */
class VenueModel extends Model
{
    protected $table         = 'venues';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'name', 'type', 'address', 'city', 'country', 'timezone',
        'capacity', 'map_url', 'directions', 'body', 'heading', 'summary',
        'seo_title', 'seo_description', 'seo_keywords', 'sort_order', 'status',
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

    /**
     * Upcoming dates at one venue — what makes a city page a real page rather
     * than a keyword landing pad.
     *
     * @return list<array>
     */
    public function upcomingAt(int $venueId, int $limit = 12): array
    {
        return $this->db->table('course_sessions cs')
            ->select('cs.*, c.slug AS course_slug, c.title AS course_title, c.level, c.pillar')
            ->join('courses c', 'c.id = cs.course_id')
            ->where('cs.venue_id', $venueId)
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', ['open', 'confirmed', 'waitlist'])
            ->groupStart()->where('cs.start_date IS NULL')->orWhere('cs.start_date >=', date('Y-m-d'))->groupEnd()
            ->where('c.status', 'published')->where('c.deleted_at IS NULL')
            ->orderBy('cs.start_date', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();
    }
}
