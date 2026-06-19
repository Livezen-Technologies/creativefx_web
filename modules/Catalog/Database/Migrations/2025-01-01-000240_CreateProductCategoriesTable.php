<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'        => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'description' => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'image_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'parent_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('product_categories', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('product_categories', true);
    }
}
