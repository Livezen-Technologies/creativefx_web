<?php

namespace Modules\Catalog\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The Magic Corn range: the flavours served at the outlets, and the frozen
 * pack sold for home and wholesale. Upserts by slug — never deletes — so
 * merchandising keeps ownership after the first seed.
 *
 * Product photography is not in the repository yet, so hero_image is left
 * null and the grid falls back to its placeholder treatment.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $j   = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);
        $now = date('Y-m-d H:i:s');

        $catId = [];
        foreach ($this->db->table('product_categories')->select('id, slug')->get()->getResultArray() as $row) {
            $catId[$row['slug']] = (int) $row['id'];
        }

        $cupSpecs = $j([
            ['label' => 'Served',      'value' => 'Hot, steamed to order'],
            ['label' => 'Base',        'value' => 'Precooked sweet corn, 100% natural spices'],
            ['label' => 'Additives',   'value' => 'No MSG, no added flavours, no preservatives'],
            ['label' => 'Where',       'value' => 'Magic Corn outlets island-wide'],
            ['label' => 'Origin',      'value' => 'Grown and processed in Sri Lanka'],
        ]);
        $cupFeatures = $j([
            'Steamed fresh at the outlet',
            '100% natural spices',
            'No MSG or preservatives',
            'Toppings combined to your taste',
        ]);

        // The site has one product photograph — the branded cup with cobs. It
        // stands in for every flavour until per-flavour shots are supplied.
        $cupShot = '/media/magiccorn/Magic-Corn-with-corn.png';

        $cups = [
            ['butter-corn-cup', 'Butter Corn Cup', 'Maíz con mantequilla',
                'The classic. Sweet corn steamed to order and stirred through with butter until every kernel is glossy.', 'bestseller', 1],
            ['garlic-corn-cup', 'Garlic Corn Cup', 'Maíz al ajo',
                'Savoury and aromatic — garlic folded through hot sweet corn. A favourite with regulars.', null, 0],
            ['cheese-corn-cup', 'Cheese Corn Cup', 'Maíz con queso',
                'Rich and generous, with cheese stirred right through the cup while the corn is still steaming.', 'popular', 1],
            ['mayo-corn-cup', 'Mayo Corn Cup', 'Maíz con mayonesa',
                'Creamy and mild, and the base for many of our best combinations.', null, 0],
            ['minced-chicken-corn-cup', 'Minced Chicken Corn Cup', 'Maíz con pollo picado',
                'Seasoned minced chicken through buttered sweet corn — a cup that eats like a meal.', null, 0],
            ['lime-oyster-corn-cup', 'Lime & Oyster Sauce Corn Cup', 'Maíz con lima y salsa de ostras',
                'Bright lime against savoury oyster sauce — the combination regulars come back for.', 'new', 1],
        ];

        $products = [];
        foreach ($cups as $i => [$slug, $en, $es, $desc, $label, $featured]) {
            $products[] = [
                'category_id'       => $catId['corn-cups'] ?? null,
                'slug'              => $slug,
                'sku'               => 'MC-CUP-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'collection'        => 'Corn in a Cup',
                'name'              => $j(['en' => $en, 'es' => $es]),
                'short_description' => $j(['en' => $desc]),
                'description'       => $j(['en' => $desc . ' Made with sweet corn we grow ourselves in Sri Lanka, precooked at our ISO 9001 certified factory and steamed fresh at the outlet.']),
                'features'          => $cupFeatures,
                'applications'      => $j(['Outlets', 'Events and catering']),
                'specs'             => $cupSpecs,
                'hero_image'        => $cupShot,
                'gallery'           => $j([$cupShot]),
                'certifications'    => 'ISO 9001',
                'label'             => $label,
                'is_featured'       => $featured,
                'sort_order'        => $i + 1,
                'status'            => 'published',
            ];
        }

        $products[] = [
            'category_id'       => $catId['frozen-packs'] ?? null,
            'slug'              => '1kg-frozen-sweet-corn-pack',
            'sku'               => 'MC-FRZ-001',
            'collection'        => 'Frozen Packs',
            'name'              => $j(['en' => '1kg Frozen Sweet Corn Pack', 'es' => 'Paquete de maíz dulce congelado 1 kg']),
            'short_description' => $j(['en' => 'A kilogram of our precooked sweet corn, frozen — make it your own way at home.']),
            'description'       => $j(['en' => 'The same sweet corn we serve at our outlets, precooked and frozen in a 1kg pack. Steam it, season it and top it however you like. Also supplied in bulk to hotels, restaurants and caterers.']),
            'features'          => $j(['Precooked and frozen', 'Grown in Sri Lanka', 'No preservatives', 'Bulk supply available']),
            'applications'      => $j(['Home', 'Hotels and restaurants', 'Catering']),
            'specs'             => $j([
                ['label' => 'Pack size',  'value' => '1 kg'],
                ['label' => 'Price',      'value' => 'LKR 2,250.00'],
                ['label' => 'Storage',    'value' => 'Keep frozen'],
                ['label' => 'Additives',  'value' => 'No preservatives'],
                ['label' => 'Origin',     'value' => 'Grown and processed in Sri Lanka'],
            ]),
            'hero_image'        => '/media/magiccorn/4.jpg',
            'gallery'           => $j(['/media/magiccorn/4.jpg', '/media/magiccorn/6.jpg']),
            'certifications'    => 'ISO 9001',
            'label'             => null,
            'is_featured'       => 1,
            'sort_order'        => 100,
            'status'            => 'published',
        ];

        $table = $this->db->table('products');
        foreach ($products as $product) {
            $exists = $table->select('id')->where('slug', $product['slug'])->get()->getRowArray();
            $table->resetQuery();
            if ($exists === null && $product['category_id'] !== null) {
                $this->db->table('products')->insert($product + ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}
