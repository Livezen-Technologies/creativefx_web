<?php

namespace Modules\Learning\Models;

use CodeIgniter\Model;

/**
 * Where a learner has got to.
 *
 * Written often — every few seconds of video playback — so the table is narrow
 * and this model does one upsert rather than a read followed by a write.
 */
class ProgressModel extends Model
{
    protected $table         = 'lesson_progress';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'course_id', 'lesson_id', 'status', 'position_sec', 'completed_at'];

    public function record(int $userId, int $courseId, int $lessonId, int $positionSec, bool $complete = false): void
    {
        $existing = $this->where('user_id', $userId)->where('lesson_id', $lessonId)->first();

        $data = [
            'user_id'      => $userId,
            'course_id'    => $courseId,
            'lesson_id'    => $lessonId,
            'status'       => $complete ? 'completed' : 'started',
            // Never rewinds. A player that reports position 3 because the page
            // reloaded must not throw away twenty minutes of watched video.
            'position_sec' => max($positionSec, (int) ($existing['position_sec'] ?? 0)),
            'completed_at' => $complete ? ($existing['completed_at'] ?? date('Y-m-d H:i:s')) : ($existing['completed_at'] ?? null),
        ];

        // Once complete, always complete: re-watching the first minute of a
        // finished lesson must not un-finish it.
        if (($existing['status'] ?? '') === 'completed') {
            $data['status'] = 'completed';
        }

        $existing === null ? $this->insert($data) : $this->update((int) $existing['id'], $data);
    }

    /** @return array<int, array> keyed by lesson id */
    public function forCourse(int $userId, int $courseId): array
    {
        return array_column(
            $this->where('user_id', $userId)->where('course_id', $courseId)->findAll(),
            null,
            'lesson_id'
        );
    }

    /** Percentage complete, 0–100. */
    public function percent(int $userId, int $courseId): int
    {
        $total = $this->db->table('lessons')
            ->where('course_id', $courseId)->where('status', 'published')->countAllResults();
        if ($total === 0) {
            return 0;
        }

        $done = $this->where('user_id', $userId)->where('course_id', $courseId)
            ->where('status', 'completed')->countAllResults();

        return (int) round($done / $total * 100);
    }
}
