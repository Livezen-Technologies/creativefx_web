<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'        => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'description' => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'attributes'  => ['type' => 'TEXT', 'null' => true],   // JSON
            'hero_image'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category_id');
        $this->forge->addForeignKey('category_id', 'product_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('products', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('products', true);
    }
}
