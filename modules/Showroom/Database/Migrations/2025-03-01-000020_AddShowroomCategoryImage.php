<?php

namespace Modules\Showroom\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a photo column to showroom categories (path under /public). Used as the
 * category-card image in the lobby; cards without one keep the palette gradient.
 */
class AddShowroomCategoryImage extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('showroom_categories', [
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'features'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('showroom_categories', 'image');
    }
}
