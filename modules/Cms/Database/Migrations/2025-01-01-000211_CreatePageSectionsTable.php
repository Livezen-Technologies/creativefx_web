<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePageSectionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'page_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'generic'],
            'settings'   => ['type' => 'TEXT', 'null' => true],   // JSON
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['page_id', 'sort_order']);
        $this->forge->addForeignKey('page_id', 'pages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('page_sections', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('page_sections', true);
    }
}
