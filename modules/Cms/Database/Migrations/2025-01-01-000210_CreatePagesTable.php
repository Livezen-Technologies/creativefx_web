<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePagesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'            => ['type' => 'TEXT', 'null' => true],            // JSON locale-map
            'meta_title'       => ['type' => 'TEXT', 'null' => true],            // JSON locale-map
            'meta_description' => ['type' => 'TEXT', 'null' => true],            // JSON locale-map
            'template'         => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'default'],
            'is_home'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'parent_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'sort_order'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'draft'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('pages', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('pages', true);
    }
}
