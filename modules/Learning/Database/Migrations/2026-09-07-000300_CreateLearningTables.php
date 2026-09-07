<?php

namespace Modules\Learning\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * What happens after the money: enrolments, attendance, the self-paced lesson
 * player, quizzes, progress, certificates and transfers.
 *
 * The enrolment is the hinge of the whole system. It is created by a verified
 * payment (or by an admin, for a bank transfer or a retake), and everything a
 * learner can see or download hangs off it — the joining link, the recording,
 * the register, the certificate, the review they are allowed to leave. Nothing
 * here is ever created because somebody reached a URL.
 *
 * `lesson_progress`, not `progress`: the bare word is close enough to reserved
 * in enough engines to be worth avoiding, and it reads better next to
 * `lesson_assets` and `lesson_id` anyway.
 *
 * Certificates carry a serial and a separate verification code. The serial is
 * ours, sequential and printed on the document; the verify code is random and
 * is what the public URL exposes, so that knowing one certificate's number
 * tells you nothing about anybody else's.
 */
class CreateLearningTables extends Migration
{
    public function up(): void
    {
        // ── Enrolments ───────────────────────────────────────────────────────
        // status: active | completed | cancelled | transferred | no_show
        // source: purchase | retake | corporate | comp | import
        //
        // `source` is not decoration. A free retake within six months is an
        // enrolment at zero price, and revenue reporting that cannot tell it
        // from a sale will quietly report a business that is growing when it is
        // not.
        //
        // session_id is nullable for a bundle enrolment that has not yet been
        // scheduled onto dates, and for self-paced study where the session
        // carries no dates at all.
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'order_item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'course_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'session_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'bundle_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'account_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'mode'          => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'LIVE_ONLINE'],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'active'],
            'source'        => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'purchase'],
            'enrolled_at'   => ['type' => 'DATETIME', 'null' => true],
            'completed_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // One enrolment per seat, and a seat is an order item. The unique key is
        // what makes the webhook handler safe to run twice.
        $this->forge->addUniqueKey(['order_item_id', 'user_id']);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey(['session_id', 'status']);
        $this->forge->addKey(['course_id', 'status']);
        $this->forge->createTable('enrolments', true);

        // ── Attendance ───────────────────────────────────────────────────────
        // Per day, not per class: a corporate buyer paying for four staff on a
        // two-day course wants to know which of them turned up on day two, and
        // the certificate rule depends on it.
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'enrolment_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'session_day_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'present'], // present|absent|late|excused
            'minutes'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'marked_by'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['enrolment_id', 'session_day_id']);
        $this->forge->createTable('attendance', true);

        // ── Lessons ──────────────────────────────────────────────────────────
        // The self-paced library. A lesson hangs off a course and, optionally,
        // off one of that course's curriculum modules, so the on-demand outline
        // and the classroom outline are the same outline rather than two lists
        // that drift.
        //
        // video_provider / video_ref rather than a URL: a signed playback id
        // from Mux or Bunny is not a link and must not be rendered as one. A
        // local file in the media library is the third provider, so the site
        // works before a video contract is signed.
        //
        // is_preview is the single biggest conversion lever on the on-demand
        // catalogue, and it doubles as indexable content: a preview lesson's
        // transcript is a page a search engine can read.
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'module_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'          => ['type' => 'TEXT', 'null' => true],
            'type'           => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'video'], // video|text|quiz|assignment|file
            'video_provider' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],        // media|mux|bunny
            'video_ref'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'duration_sec'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'body'           => ['type' => 'TEXT', 'null' => true],
            'transcript'     => ['type' => 'TEXT', 'null' => true],
            'is_preview'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['course_id', 'slug']);
        $this->forge->addKey(['course_id', 'sort_order']);
        $this->forge->createTable('lessons', true);

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'lesson_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'path'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'kind'       => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'exercise'], // exercise|slides|reference
            'size_bytes' => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['lesson_id', 'sort_order']);
        $this->forge->createTable('lesson_assets', true);

        // ── Quizzes ──────────────────────────────────────────────────────────
        // A quiz gates the end of a module; the final one issues the
        // certificate at the pass mark. answer_json never reaches the browser —
        // marking happens on the server, because a quiz whose answers are in
        // the page source is a form, not an assessment.
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'lesson_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'title'            => ['type' => 'TEXT', 'null' => true],
            'pass_mark'        => ['type' => 'INT', 'constraint' => 11, 'default' => 70],
            'attempts_allowed' => ['type' => 'INT', 'constraint' => 11, 'default' => 3],
            'time_limit_min'   => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'is_final'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('lesson_id');
        $this->forge->createTable('quizzes', true);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'quiz_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'single'], // single|multiple|boolean
            'prompt'       => ['type' => 'TEXT', 'null' => true],
            'options_json' => ['type' => 'TEXT', 'null' => true],
            'answer_json'  => ['type' => 'TEXT', 'null' => true],
            'explanation'  => ['type' => 'TEXT', 'null' => true],
            'points'       => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'sort_order'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['quiz_id', 'sort_order']);
        $this->forge->createTable('quiz_questions', true);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'quiz_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'enrolment_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'score'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'max_score'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'passed'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'answers_json' => ['type' => 'TEXT', 'null' => true],
            'started_at'   => ['type' => 'DATETIME', 'null' => true],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'quiz_id']);
        $this->forge->createTable('quiz_attempts', true);

        // ── Progress ─────────────────────────────────────────────────────────
        // position_sec is what makes "resume where you left off" work, and it is
        // written often — every few seconds of playback — so this table stays
        // narrow and is keyed exactly for the two reads it serves: one lesson
        // for one learner, and the whole course for the progress ring.
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'course_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'lesson_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'started'], // started|completed
            'position_sec' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'lesson_id']);
        $this->forge->addKey(['user_id', 'course_id']);
        $this->forge->createTable('lesson_progress', true);

        // ── Certificates ─────────────────────────────────────────────────────
        // Public verification is the point. A corporate buyer, an HR check or a
        // visa application needs to confirm a certificate without trusting the
        // PDF in front of them, so /verify/{code} returns the name, the course,
        // the date and whether it still stands.
        //
        // Two identifiers on purpose: `serial` is the sequential number printed
        // on the document, `verify_code` is random and is the only one in a URL.
        // If the public code were the serial, holding one certificate would let
        // anybody enumerate everybody else's.
        //
        // revoked_at rather than a delete: a withdrawn certificate must keep
        // answering the verification endpoint, with "revoked" as the answer.
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'enrolment_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'user_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'course_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'bundle_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'learner_name'  => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'mode'          => ['type' => 'VARCHAR', 'constraint' => 24, 'null' => true],
            'hours'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'serial'        => ['type' => 'VARCHAR', 'constraint' => 32],
            'verify_code'   => ['type' => 'VARCHAR', 'constraint' => 32],
            'issued_at'     => ['type' => 'DATETIME', 'null' => true],
            'pdf_path'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'revoked_at'    => ['type' => 'DATETIME', 'null' => true],
            'revoke_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('serial');
        $this->forge->addUniqueKey('verify_code');
        $this->forge->addKey(['user_id', 'issued_at']);
        $this->forge->createTable('certificates', true);

        // ── Transfers ────────────────────────────────────────────────────────
        // The transfer policy is a promise on the policies page, so it is built
        // rather than only written: free at ten or more business days' notice,
        // a fee inside that, and a school-initiated cancellation below the
        // minimum-to-run gives a free move or a full refund. A policy that only
        // exists as prose is applied inconsistently by whoever answers the
        // email.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'enrolment_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'from_session_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'to_session_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'requested'], // requested|approved|declined|completed
            'fee_cents'       => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'currency'        => ['type' => 'CHAR', 'constraint' => 3, 'null' => true],
            'reason'          => ['type' => 'TEXT', 'null' => true],
            'decision_note'   => ['type' => 'TEXT', 'null' => true],
            'requested_at'    => ['type' => 'DATETIME', 'null' => true],
            'decided_at'      => ['type' => 'DATETIME', 'null' => true],
            'decided_by'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['enrolment_id', 'status']);
        $this->forge->createTable('reschedule_requests', true);
    }

    public function down(): void
    {
        foreach ([
            'reschedule_requests', 'certificates', 'lesson_progress',
            'quiz_attempts', 'quiz_questions', 'quizzes', 'lesson_assets',
            'lessons', 'attendance', 'enrolments',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
