<?php

namespace Modules\Learning\Models;

use CodeIgniter\Model;

/**
 * Quizzes and their marking.
 *
 * `answer_json` never reaches the browser. Marking happens here, on the server,
 * because a quiz whose answers are in the page source is a form, not an
 * assessment — and the final quiz issues a certificate.
 */
class QuizModel extends Model
{
    protected $table         = 'quizzes';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['lesson_id', 'title', 'pass_mark', 'attempts_allowed', 'time_limit_min', 'is_final'];

    public function forLesson(int $lessonId): ?array
    {
        return $this->where('lesson_id', $lessonId)->first();
    }

    /**
     * The questions, with the answers stripped.
     *
     * @return list<array>
     */
    public function questionsForDisplay(int $quizId): array
    {
        $rows = $this->db->table('quiz_questions')->where('quiz_id', $quizId)
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();

        foreach ($rows as &$row) {
            unset($row['answer_json'], $row['explanation']);
        }

        return $rows;
    }

    /**
     * Mark an attempt.
     *
     * @param array<int, mixed> $answers question id => chosen option index, or list of them
     * @return array{score:int, max:int, percent:int, passed:bool, detail:list<array>}
     */
    public function mark(int $quizId, array $answers): array
    {
        $quiz      = $this->find($quizId);
        $questions = $this->db->table('quiz_questions')->where('quiz_id', $quizId)
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();

        $score  = 0;
        $max    = 0;
        $detail = [];

        foreach ($questions as $question) {
            $points   = (int) $question['points'];
            $max     += $points;
            $expected = json_decode((string) $question['answer_json'], true);
            $given    = $answers[$question['id']] ?? null;

            // Multiple-choice answers are compared as sorted sets, so the order
            // a learner ticked the boxes in cannot change whether they are right.
            $correct = is_array($expected)
                ? (is_array($given) && $this->sameSet($expected, $given))
                : ((string) $expected === (string) $given);

            if ($correct) {
                $score += $points;
            }

            $detail[] = [
                'question_id' => (int) $question['id'],
                'correct'     => $correct,
                'explanation' => $question['explanation'],
            ];
        }

        $percent = $max === 0 ? 0 : (int) round($score / $max * 100);

        return [
            'score'   => $score,
            'max'     => $max,
            'percent' => $percent,
            'passed'  => $percent >= (int) ($quiz['pass_mark'] ?? 70),
            'detail'  => $detail,
        ];
    }

    private function sameSet(array $a, array $b): bool
    {
        $a = array_map('strval', $a);
        $b = array_map('strval', $b);
        sort($a);
        sort($b);

        return $a === $b;
    }
}
