<?php

namespace Modules\Careers\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJobApplicationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'job_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 128],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'resume_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'portfolio_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cover_letter' => ['type' => 'TEXT', 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'new'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['job_id', 'status']);
        $this->forge->addForeignKey('job_id', 'jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('job_applications', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('job_applications', true);
    }
}
