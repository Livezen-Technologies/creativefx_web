<?php

namespace Modules\Esg\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEsgReportsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'title'        => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'summary'      => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'year'         => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'file_path'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'draft'],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('esg_reports', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('esg_reports', true);
    }
}
