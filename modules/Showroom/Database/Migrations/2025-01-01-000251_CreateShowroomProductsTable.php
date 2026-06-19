<?php

namespace Modules\Showroom\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShowroomProductsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'showroom_category_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'slug'                  => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'                  => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'description'           => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'hotspot'               => ['type' => 'TEXT', 'null' => true],   // JSON {x,y,z}
            'gallery'               => ['type' => 'TEXT', 'null' => true],   // JSON [paths]
            'materials'             => ['type' => 'TEXT', 'null' => true],   // JSON
            'brochure_path'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'            => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'                => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('showroom_category_id');
        $this->forge->addForeignKey('showroom_category_id', 'showroom_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('showroom_products', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('showroom_products', true);
    }
}
