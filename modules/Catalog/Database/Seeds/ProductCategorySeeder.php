<?php

namespace Modules\Catalog\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // [slug, en, es]  — names stored as JSON locale-maps; missing locales
        // fall back to English via t_field().
        $categories = [
            ['babywear', 'Babywear', 'Ropa de bebé'],
            ['childrenswear', 'Childrenswear', 'Ropa infantil'],
            ['kids-nightwear', 'Kids Nightwear', 'Pijamas infantiles'],
            ['school-wear', 'School Wear', 'Uniformes escolares'],
            ['accessories', 'Accessories', 'Accesorios'],
            ['true-knits', 'True Knits', 'Tejidos de punto'],
            ['hosiery-toys', 'Hosiery & Toys', 'Calcetería y juguetes'],
            ['adults-woven', 'Adults Woven', 'Tejido plano para adultos'],
            ['adults-jersey', 'Adults Jersey', 'Punto para adultos'],
            ['activewear', 'Activewear', 'Ropa deportiva'],
            ['maternity', 'Maternity', 'Maternidad'],
            ['adults-essentials', 'Adults Essentials', 'Básicos para adultos'],
            ['nightwear', 'Nightwear', 'Ropa de dormir'],
        ];

        $rows = [];
        $order = 0;
        foreach ($categories as [$slug, $en, $es]) {
            $rows[] = [
                'slug'       => $slug,
                'name'       => json_encode(['en' => $en, 'es' => $es], JSON_UNESCAPED_UNICODE),
                'status'     => 'published',
                'sort_order' => $order++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('product_categories')->ignore(true)->insertBatch($rows);
    }
}
