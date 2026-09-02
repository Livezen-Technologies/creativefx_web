<?php

namespace Modules\Catalog\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Magic Corn sells hot corn in a cup at the outlets, frozen sweet corn to take
 * home or supply in bulk, and the DIY box that puts the two together with the
 * toppings and the cups. Upserts by slug — never deletes.
 */
class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $categories = [
            ['corn-cups',    'Corn in a Cup', 'Maíz en vaso'],
            ['frozen-packs', 'Frozen Packs',  'Paquetes congelados'],
            ['diy-packs',    'DIY Packs',     'Paquetes DIY'],
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
