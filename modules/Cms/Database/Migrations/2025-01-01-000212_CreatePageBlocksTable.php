<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePageBlocksTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'section_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'richtext'],
            'content'    => ['type' => 'TEXT', 'null' => true],   // JSON (locale-aware payload)
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['section_id', 'sort_order']);
        $this->forge->addForeignKey('section_id', 'page_sections', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('page_blocks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('page_blocks', true);
    }
}
