<?php

namespace Modules\Catalog\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Catalog starter products: the NORLANKA KIDSWEAR denim line (real product
 * photography supplied by the client) plus one representative style per major
 * category using the portfolio photography already on the site. Sample specs
 * (sizes, MOQ) are placeholders for merchandising to refine in Admin →
 * Products. Upserts by slug — never deletes — so edits survive redeploys.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $j   = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);
        $now = date('Y-m-d H:i:s');

        // slug => id for category references.
        $catId = [];
        foreach ($this->db->table('product_categories')->select('id, slug')->get()->getResultArray() as $row) {
            $catId[$row['slug']] = (int) $row['id'];
        }

        $specs = static fn (string $fabric, string $sizes): string => json_encode([
            ['label' => 'Fabric', 'value' => $fabric],
            ['label' => 'Size range', 'value' => $sizes],
            ['label' => 'Colour variants', 'value' => 'Per collection — custom lab-dips available'],
            ['label' => 'MOQ', 'value' => 'On request'],
            ['label' => 'Compliance', 'value' => 'SEDEX · GOTS · GRS · HIGG'],
            ['label' => 'Country of origin', 'value' => 'Sri Lanka'],
        ], JSON_UNESCAPED_UNICODE);

        // The KIDSWEAR denim line — same styles as the virtual showroom.
        $kidsFeatures = $j(['Full-package development from sketch to delivery', 'In-house printing, embroidery and appliqué', 'Virtual 3D sampling available', 'Responsible washes and trims']);
        $kids = [
            ['denim-pinafore-set', 'Denim Pinafore Set', 'Conjunto de pichi vaquero', 'Cotton chambray denim + cotton jersey',
                'A soft-wash denim pinafore dress layered over a mustard leopard-print long-sleeve tee — a ready-to-wear two-piece finished with a "good vibes grow here" patch.',
                '/media/showroom/kids/denim-pinafore-set.jpg', 'popular', 1],
            ['denim-dungarees', 'Denim Dungarees', 'Peto vaquero', 'Cotton denim, light wash',
                'Classic light-wash denim dungarees with adjustable buckle straps, a bib pocket and roomy front pockets — an everyday play staple.',
                '/media/showroom/kids/denim-dungarees.jpg', 'new', 0],
            ['applique-denim-jacket', 'Appliqué Denim Jacket', 'Chaqueta vaquera con parches', 'Cotton denim',
                'A light-wash denim jacket finished with unicorn, rainbow "Bright Days" and strawberry appliqués, puff shoulders and a rounded collar.',
                '/media/showroom/kids/denim-jacket.jpg', 'bestseller', 1],
            ['striped-shirt-leggings-set', 'Striped Shirt & Leggings Set', 'Conjunto de camisa a rayas y leggings', 'Yarn-dyed cotton + cotton-elastane jersey',
                'A frill-collar blue-stripe shirt paired with a white tee and soft navy leggings — an easy coordinated three-piece.',
                '/media/showroom/kids/striped-shirt-set.jpg', null, 0],
            ['lace-collar-denim-set', 'Lace-Collar Denim Set', 'Conjunto vaquero con cuello de encaje', 'Cotton denim + cotton jersey',
                'A denim shirt with a statement lace frill collar, matched with animal-spot print leggings.',
                '/media/showroom/kids/lace-collar-denim-set.jpg', null, 0],
            ['ruffle-denim-set', 'Ruffle Denim Set', 'Conjunto vaquero con volantes', 'Cotton denim + cotton jersey',
                'A tiered-ruffle denim shirt with mono grid-check leggings — texture-led and playful.',
                '/media/showroom/kids/ruffle-denim-set.jpg', null, 0],
            ['ruffle-denim-gingham-set', 'Ruffle Denim & Gingham Set', 'Conjunto vaquero y vichy', 'Cotton chambray + cotton jersey',
                'A soft-wash ruffle-tier chambray shirt teamed with blue gingham leggings.',
                '/media/showroom/kids/ruffle-gingham-set.jpg', 'new', 0],
        ];

        $products = [];
        foreach ($kids as $i => [$slug, $en, $es, $fabric, $desc, $img, $label, $featured]) {
            $products[] = [
                'category_id'       => $catId['childrenswear'] ?? null,
                'slug'              => $slug,
                'sku'               => 'NL-KW-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'collection'        => 'Kidswear Denim',
                'name'              => $j(['en' => $en, 'es' => $es]),
                'short_description' => $j(['en' => $desc]),
                'description'       => $j(['en' => $desc . ' Developed end-to-end by Norlanka — design, sampling, production and delivery.']),
                'features'          => $kidsFeatures,
                'applications'      => $j(['Girlswear', 'Everyday play', 'Coordinated sets']),
                'specs'             => $specs($fabric, '1–10 years'),
                'hero_image'        => $img,
                'gallery'           => $j([$img]),
                'certifications'    => 'GOTS,GRS,SEDEX,HIGG',
                'label'             => $label,
                'is_featured'       => $featured,
                'sort_order'        => $i + 1,
                'status'            => 'published',
            ];
        }

        // One representative style per major category (portfolio photography).
        $range = [
            ['babywear', 'organic-babygrow-collection', 'Organic Babygrow Collection', 'Colección de pijamas de bebé',
                'Supersoft babygrows and sleepsuits in combed organic cotton, with nickel-free poppers and flat seams for delicate skin.',
                'Combed organic cotton interlock', '0–24 months', '/media/showroom/categories/babywear.jpg', 1],
            ['kids-nightwear', 'star-print-pyjama-set', 'Star-Print Pyjama Set', 'Pijama estampado de estrellas',
                'Cosy two-piece kids pyjamas in brushed jersey with all-over print and contrast rib trims.',
                'Brushed cotton jersey', '1–12 years', '/media/showroom/categories/kids-nightwear.jpg', 0],
            ['school-wear', 'classic-school-polo', 'Classic School Polo', 'Polo escolar clásico',
                'A durable easy-iron school polo engineered for daily wear and industrial washing.',
                'Cotton-rich piqué', '3–16 years', '/media/showroom/categories/school-wear.jpg', 0],
            ['activewear', 'kids-active-set', 'Kids Active Tee & Short Set', 'Conjunto deportivo infantil',
                'Lightweight, quick-dry tee and short set with reflective trims — built for movement.',
                'Recycled polyester interlock', '3–14 years', '/media/showroom/categories/activewear.jpg', 0],
            ['maternity', 'maternity-lounge-set', 'Maternity Lounge Set', 'Conjunto premamá',
                'Softly draped maternity loungewear with adaptable panels designed to grow and recover.',
                'Modal-cotton jersey with elastane', 'XS–XXL', '/media/showroom/categories/maternity.jpg', 0],
            ['adults-essentials', 'everyday-cotton-tee', 'Everyday Cotton Tee', 'Camiseta básica de algodón',
                'A wardrobe-staple crew-neck tee cut from ring-spun cotton — colour-matched across men\'s and women\'s blocks.',
                'Ring-spun cotton single jersey', 'XS–3XL', '/media/showroom/categories/adults-essentials.jpg', 0],
        ];
        foreach ($range as $i => [$cat, $slug, $en, $es, $desc, $fabric, $sizes, $img, $featured]) {
            $products[] = [
                'category_id'       => $catId[$cat] ?? null,
                'slug'              => $slug,
                'sku'               => 'NL-' . strtoupper(substr($cat, 0, 2)) . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'collection'        => 'Core Range',
                'name'              => $j(['en' => $en, 'es' => $es]),
                'short_description' => $j(['en' => $desc]),
                'description'       => $j(['en' => $desc . ' Developed end-to-end by Norlanka — design, sampling, production and delivery.']),
                'features'          => $kidsFeatures,
                'applications'      => $j(['Private label', 'Retail programmes']),
                'specs'             => $specs($fabric, $sizes),
                'hero_image'        => $img,
                'gallery'           => $j([$img]),
                'certifications'    => 'GOTS,GRS,SEDEX,HIGG',
                'label'             => null,
                'is_featured'       => $featured,
                'sort_order'        => 100 + $i,
                'status'            => 'published',
            ];
        }

        $table = $this->db->table('products');
        foreach ($products as $product) {
            $exists = $table->select('id')->where('slug', $product['slug'])->get()->getRowArray();
            $table->resetQuery();
            if ($exists === null && $product['category_id'] !== null) {
                $this->db->table('products')->insert($product + ['created_at' => $now, 'updated_at' => $now]);
            }
            // Existing rows are left untouched — merchandising owns them after first seed.
        }
    }
}
