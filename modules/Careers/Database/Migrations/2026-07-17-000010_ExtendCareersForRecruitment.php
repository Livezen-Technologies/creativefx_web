<?php

namespace Modules\Careers\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extends the careers schema to the full recruitment blueprint: richer vacancy
 * fields (experience, qualifications, skills, salary range) and a complete
 * applicant profile with HR pipeline fields (notes, rating).
 */
class ExtendCareersForRecruitment extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('jobs', [
            'experience'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'employment_type'],
            'qualifications' => ['type' => 'TEXT', 'null' => true, 'after' => 'experience'],     // JSON [string,...]
            'skills'         => ['type' => 'TEXT', 'null' => true, 'after' => 'qualifications'], // JSON [string,...]
            'salary_range'   => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true, 'after' => 'skills'],
        ]);

        $this->forge->addColumn('job_applications', [
            'country'    => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true, 'after' => 'phone'],
            'address'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'country'],
            'education'  => ['type' => 'TEXT', 'null' => true, 'after' => 'address'],
            'experience' => ['type' => 'TEXT', 'null' => true, 'after' => 'education'],
            'skills'     => ['type' => 'TEXT', 'null' => true, 'after' => 'experience'],
            'linkedin'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'skills'],
            'notes'      => ['type' => 'TEXT', 'null' => true, 'after' => 'status'],    // HR-internal
            'rating'     => ['type' => 'TINYINT', 'constraint' => 2, 'null' => true, 'after' => 'notes'], // 1..5
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('jobs', ['experience', 'qualifications', 'skills', 'salary_range']);
        $this->forge->dropColumn('job_applications', ['country', 'address', 'education', 'experience', 'skills', 'linkedin', 'notes', 'rating']);
    }
}
