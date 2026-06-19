<?php

namespace Modules\Showroom\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Enrich the showroom for the "MAS-style" blueprint: per-category theme name,
 * environment tagline, colour palette and feature list; per-product fabric
 * composition, MOQ, size run and collection.
 */
class AddShowroomBlueprintFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('showroom_categories', [
            'theme_name' => ['type' => 'TEXT', 'null' => true, 'after' => 'theme'],   // JSON locale-map
            'tagline'    => ['type' => 'TEXT', 'null' => true, 'after' => 'theme_name'], // JSON locale-map (environment)
            'palette'    => ['type' => 'TEXT', 'null' => true, 'after' => 'background'], // JSON [hex,...]
            'features'   => ['type' => 'TEXT', 'null' => true, 'after' => 'palette'],     // JSON [string,...]
        ]);

        $this->forge->addColumn('showroom_products', [
            'fabric'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'materials'],
            'moq'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'fabric'],
            'sizes'      => ['type' => 'TEXT', 'null' => true, 'after' => 'moq'],          // JSON [string,...]
            'collection' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true, 'after' => 'sizes'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('showroom_categories', ['theme_name', 'tagline', 'palette', 'features']);
        $this->forge->dropColumn('showroom_products', ['fabric', 'moq', 'sizes', 'collection']);
    }
}
