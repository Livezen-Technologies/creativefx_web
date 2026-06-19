<?php

namespace Modules\Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeadsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 128],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 191],
            'company'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'country'    => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true],
            'interest'   => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'message'    => ['type' => 'TEXT', 'null' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'new'],
            'source'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('leads', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('leads', true);
    }
}
