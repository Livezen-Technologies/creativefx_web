<?php

namespace Modules\Learning\Models;

use CodeIgniter\Model;

/**
 * The self-paced library.
 *
 * A lesson hangs off a course and, optionally, off one of that course's
 * curriculum modules — so the on-demand outline and the classroom outline are
 * the same outline, rather than two lists that drift apart the first time
 * somebody edits one of them.
 */
class LessonModel extends Model
{
    protected $table         = 'lessons';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'course_id', 'module_id', 'slug', 'title', 'type', 'video_provider',
        'video_ref', 'duration_sec', 'body', 'transcript', 'is_preview',
        'sort_order', 'status',
    ];

    /** @return list<array> */
    public function forCourse(int $courseId): array
    {
        return $this->where('course_id', $courseId)->where('status', 'published')
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findInCourse(int $courseId, string $slug): ?array
    {
        return $this->where('course_id', $courseId)->where('slug', $slug)
            ->where('status', 'published')->first();
    }

    /**
     * The free preview, if the course has one.
     *
     * The single biggest conversion lever on an on-demand catalogue, and it
     * doubles as indexable content: a preview lesson's transcript is a page a
     * search engine can actually read.
     */
    public function previewFor(int $courseId): ?array
    {
        return $this->where('course_id', $courseId)->where('is_preview', 1)
            ->where('status', 'published')->orderBy('sort_order', 'ASC')->first();
    }

    /** The course's lessons grouped under their curriculum modules. */
    public function outline(int $courseId): array
    {
        $lessons = $this->forCourse($courseId);
        $modules = $this->db->table('course_modules')->where('course_id', $courseId)
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();

        foreach ($modules as &$module) {
            $module['lessons'] = array_values(array_filter(
                $lessons,
                static fn (array $l): bool => (int) $l['module_id'] === (int) $module['id']
            ));
        }
        unset($module);

        // Lessons attached to no module still have to appear, or an editor who
        // forgot to pick one loses them from the player with no error anywhere.
        $loose = array_values(array_filter($lessons, static fn (array $l): bool => empty($l['module_id'])));
        if ($loose !== []) {
            $modules[] = ['id' => 0, 'title' => null, 'summary' => null, 'lessons' => $loose];
        }

        return $modules;
    }
}
