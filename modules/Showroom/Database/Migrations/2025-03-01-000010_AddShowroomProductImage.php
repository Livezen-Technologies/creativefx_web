<?php

namespace Modules\Showroom\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a product photo column to the showroom. Holds a path under /public
 * (e.g. /media/showroom/kids/denim-dungarees.jpg). Nullable so products without
 * a photo fall back to the colour-swatch display.
 */
class AddShowroomProductImage extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('showroom_products', [
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'gallery'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('showroom_products', 'image');
    }
}
