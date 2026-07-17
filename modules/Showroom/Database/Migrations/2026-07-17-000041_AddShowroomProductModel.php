<?php

namespace Modules\Showroom\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * GLB/GLTF support in the virtual showroom (blueprint §8, §11): when a
 * showroom product has a 3D model it renders in the scene instead of the
 * photo billboard / garment silhouette.
 */
class AddShowroomProductModel extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('showroom_products', [
            'model_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'image'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('showroom_products', 'model_path');
    }
}
