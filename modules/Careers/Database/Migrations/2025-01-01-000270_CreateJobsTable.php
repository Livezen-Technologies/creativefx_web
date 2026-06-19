<?php

namespace Modules\Careers\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJobsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'           => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'description'     => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'department'      => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true],
            'country'         => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true],
            'location'        => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'employment_type' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'open'],
            'posted_at'       => ['type' => 'DATETIME', 'null' => true],
            'closes_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['department', 'country']);
        $this->forge->createTable('jobs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('jobs', true);
    }
}
