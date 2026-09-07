<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * Trainers.
 *
 * `is_placeholder` is the honest half of this table. A training school's
 * credibility rests on who teaches, and named trainers with photographs and
 * verifiable credentials are the client's to supply, not ours to invent. Until
 * they arrive the faculty entries describe the team rather than a person, and
 * this flag is what lets the admin list exactly what still needs a real name
 * — and what keeps a fabricated `Person` out of the structured data, where it
 * would be a claim to Google about somebody who does not exist.
 */
class InstructorModel extends Model
{
    protected $table         = 'instructors';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'name', 'headline', 'bio', 'photo', 'credentials_json',
        'links_json', 'is_placeholder', 'sort_order', 'status',
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
    public function coursesFor(int $instructorId): array
    {
        return $this->db->table('course_instructor ci')
            ->select('c.id, c.slug, c.title, c.summary, c.hero_image, c.level, c.pillar')
            ->join('courses c', 'c.id = ci.course_id')
            ->where('ci.instructor_id', $instructorId)
            ->where('c.status', 'published')->where('c.deleted_at IS NULL')
            ->orderBy('c.level', 'ASC')
            ->get()->getResultArray();
    }
}
