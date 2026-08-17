<?php

namespace Modules\Gear\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gear rental categories: the kit families a rental item is filed under
 * (cameras, lenses, lighting, audio …).
 *
 * They group the cards in the gear_grid CMS block and are what an editor picks
 * from when adding a new item, so the slug is the stable handle a block payload
 * filters on ({"category": "cameras"}) — rename the name, never the slug.
 */
class CreateGearCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'       => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('gear_categories', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('gear_categories', true);
    }
}
