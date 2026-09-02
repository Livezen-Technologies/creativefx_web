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

        helper('norlanka');

        // Authored in English only; content_locales() completes the rest from
        // the translation dictionary, so there is one place a wording lives.
        $categories = [
            ['corn-cups',    'Corn in a Cup'],
            ['frozen-packs', 'Frozen Packs'],
            ['diy-packs',    'DIY Packs'],
        ];

        $table = $this->db->table('product_categories');
        foreach ($categories as $i => [$slug, $en]) {
            if ($table->where('slug', $slug)->get()->getRowArray() !== null) {
                $table->resetQuery();
                continue;
            }
            $table->resetQuery();
            $this->db->table('product_categories')->insert([
                'slug'       => $slug,
                'name'       => json_encode(content_locales(['en' => $en]), JSON_UNESCAPED_UNICODE),
                'sort_order' => $i + 1,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
