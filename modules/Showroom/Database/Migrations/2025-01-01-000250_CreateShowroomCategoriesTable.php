<?php

namespace Modules\Showroom\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShowroomCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'       => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'theme'      => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'background' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('showroom_categories', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('showroom_categories', true);
    }
}
