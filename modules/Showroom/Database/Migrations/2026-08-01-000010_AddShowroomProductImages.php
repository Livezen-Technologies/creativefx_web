<?php

namespace Modules\Showroom\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Product photography for the showroom drawer.
 *
 * `image` holds the single shot the 3D room uses as a billboard, and `gallery`
 * was already taken — it stores the colour swatches shown under "Colours". So
 * the drawer's photo set needs a column of its own: a JSON array of paths,
 * displayed in order with the first as the opening frame.
 */
class AddShowroomProductImages extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('showroom_products', [
            'images' => ['type' => 'TEXT', 'null' => true, 'after' => 'image'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('showroom_products', 'images');
    }
}
