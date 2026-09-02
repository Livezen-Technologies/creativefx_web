<?php

namespace Modules\Catalog\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Magic Corn sells two things: hot corn in a cup at the outlets, and frozen
 * sweet corn to take home or supply in bulk. Upserts by slug — never deletes.
 */
class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $categories = [
            ['corn-cups',    'Corn in a Cup', 'Maíz en vaso'],
            ['frozen-packs', 'Frozen Packs',  'Paquetes congelados'],
        ];

        $table = $this->db->table('product_categories');
        foreach ($categories as $i => [$slug, $en, $es]) {
            if ($table->where('slug', $slug)->get()->getRowArray() !== null) {
                $table->resetQuery();
                continue;
            }
            $table->resetQuery();
            $this->db->table('product_categories')->insert([
                'slug'       => $slug,
                'name'       => json_encode(['en' => $en, 'es' => $es], JSON_UNESCAPED_UNICODE),
                'sort_order' => $i + 1,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
