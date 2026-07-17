<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Product management (blueprint §6–9): commercial fields (SKU, code,
 * collection), rich content (short description, features, applications),
 * the custom specification builder (specs JSON), media (gallery, GLB/GLTF
 * 3D model, brochure), merchandising labels and per-product SEO.
 */
class ExtendProductsForCatalog extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('products', [
            'sku'               => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'slug'],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'sku'],
            'collection'        => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true, 'after' => 'code'],
            'short_description' => ['type' => 'TEXT', 'null' => true, 'after' => 'name'],          // JSON locale-map
            'features'          => ['type' => 'TEXT', 'null' => true, 'after' => 'description'],   // JSON list
            'applications'      => ['type' => 'TEXT', 'null' => true, 'after' => 'features'],      // JSON list
            'specs'             => ['type' => 'TEXT', 'null' => true, 'after' => 'applications'],  // JSON [{label,value}]
            'gallery'           => ['type' => 'TEXT', 'null' => true, 'after' => 'hero_image'],    // JSON list of image paths
            'model_path'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'gallery'],   // GLB/GLTF
            'brochure_path'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'model_path'],
            'certifications'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'brochure_path'], // comma-separated
            'label'             => ['type' => 'VARCHAR', 'constraint' => 24, 'null' => true, 'after' => 'certifications'], // new|bestseller|popular
            'is_featured'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'label'],
            'meta_title'        => ['type' => 'TEXT', 'null' => true, 'after' => 'is_featured'],       // JSON locale-map
            'meta_description'  => ['type' => 'TEXT', 'null' => true, 'after' => 'meta_title'],        // JSON locale-map
        ]);
    }

    public function down(): void
    {
        foreach (['sku', 'code', 'collection', 'short_description', 'features', 'applications', 'specs',
            'gallery', 'model_path', 'brochure_path', 'certifications', 'label', 'is_featured',
            'meta_title', 'meta_description'] as $col) {
            $this->forge->dropColumn('products', $col);
        }
    }
}
