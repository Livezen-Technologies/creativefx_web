<?php

namespace Modules\Esg\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEsgMetricsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 64],
            'label'      => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'value'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'unit'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'pillar'     => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'year'       => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key');
        $this->forge->createTable('esg_metrics', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('esg_metrics', true);
    }
}
