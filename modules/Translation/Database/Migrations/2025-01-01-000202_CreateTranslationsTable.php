<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTranslationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 5],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'general'],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['locale', 'group', 'key']);
        $this->forge->addKey(['locale', 'group']);
        $this->forge->createTable('translations', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('translations', true);
    }
}
