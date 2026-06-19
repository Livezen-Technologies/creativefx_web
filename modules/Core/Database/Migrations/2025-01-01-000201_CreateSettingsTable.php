<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'general'],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 128],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'string'],
            'is_public'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['group', 'key']);
        $this->forge->createTable('settings', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('settings', true);
    }
}
