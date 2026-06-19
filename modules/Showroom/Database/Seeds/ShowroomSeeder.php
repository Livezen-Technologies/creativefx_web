<?php

namespace Modules\Showroom\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the 13 themed showroom categories (from the blueprint) and a few
 * products each, with 3D hotspot coordinates, colour-swatch galleries and
 * material lists. Idempotent: clears products + categories then reseeds.
 */
class ShowroomSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $j   = static fn (array $m): string => json_encode($m, JSON_UNESCAPED_UNICODE);

        // [slug, en, es, theme, accent hex]
        $categories = [
            ['babywear', 'Babywear', 'Ropa de bebé', 'clouds', '#9fc6ff'],
            ['childrenswear', 'Childrenswear', 'Ropa infantil', 'playground', '#ffd166'],
            ['kids-nightwear', 'Kids Nightwear', 'Pijamas infantiles', 'night', '#6c7bff'],
            ['school-wear', 'School Wear', 'Uniformes escolares', 'classroom', '#4cc9a0'],
            ['accessories', 'Accessories', 'Accesorios', 'luxury', '#d4af37'],
            ['true-knits', 'True Knits', 'Tejidos de punto', 'textile', '#e07a5f'],
            ['hosiery-toys', 'Hosiery & Toys', 'Calcetería y juguetes', 'toys', '#ff6b6b'],
            ['adults-woven', 'Adults Woven', 'Tejido plano', 'boutique', '#c9a227'],
            ['adults-jersey', 'Adults Jersey', 'Punto para adultos', 'urban', '#8d99ae'],
            ['activewear', 'Activewear', 'Ropa deportiva', 'arena', '#2ec4b6'],
            ['maternity', 'Maternity', 'Maternidad', 'wellness', '#f4a6c0'],
            ['adults-essentials', 'Adults Essentials', 'Básicos', 'minimal', '#cfcfcf'],
            ['nightwear', 'Nightwear', 'Ropa de dormir', 'bedroom', '#b388eb'],
        ];

        $materialPool = ['Organic Cotton', 'Recycled Polyester', 'Bamboo', 'Performance Knit', 'Cotton Jersey', 'French Terry'];
        $seasons      = ['SS25', 'AW25', 'Core'];
        $collections  = ['Essentials', 'Premium', 'Eco'];

        $catTable  = $this->db->table('showroom_categories');
        $prodTable = $this->db->table('showroom_products');

        // Reset (children first for FK safety).
        $prodTable->where('id >', 0)->delete();
        $catTable->where('id >', 0)->delete();

        $order = 0;
        foreach ($categories as [$slug, $en, $es, $theme, $accent]) {
            $catTable->insert([
                'slug'       => $slug,
                'name'       => $j(['en' => $en, 'es' => $es]),
                'theme'      => $theme,
                'background' => $accent,
                'sort_order' => $order++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryId = (int) $this->db->insertID();

            $positions = [[-3.2, 1.1, 0.2], [0, 1.3, -0.4], [3.2, 1.1, 0.2], [-1.6, 1.0, 1.4]];
            for ($p = 1; $p <= 3; $p++) {
                $hex   = $this->shade($accent, $p);
                $prodTable->insert([
                    'showroom_category_id' => $categoryId,
                    'slug'        => $slug . '-style-' . $p,
                    'name'        => $j(['en' => $en . ' — Style 0' . $p, 'es' => $es . ' — Estilo 0' . $p]),
                    'description' => $j(['en' => 'A signature ' . strtolower($en) . ' piece, responsibly made and finished to a premium standard.']),
                    'hotspot'     => $j(['x' => $positions[$p - 1][0], 'y' => $positions[$p - 1][1], 'z' => $positions[$p - 1][2]]),
                    'gallery'     => $j([$accent, $hex, $this->shade($accent, $p + 2)]),
                    'materials'   => $j([
                        $materialPool[($order + $p) % count($materialPool)],
                        $materialPool[($order + $p + 1) % count($materialPool)],
                    ]),
                    'sort_order'  => $p,
                    'status'      => 'published',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
        // Stash season/collection metadata in the brochure_path-free way is overkill;
        // filters use category + material which are already present.
        unset($seasons, $collections);
    }

    /** Lighten/darken a hex colour deterministically for swatch variety. */
    private function shade(string $hex, int $step): string
    {
        $hex = ltrim($hex, '#');
        $r   = max(0, min(255, hexdec(substr($hex, 0, 2)) + ($step * 18) - 30));
        $g   = max(0, min(255, hexdec(substr($hex, 2, 2)) + ($step * 12) - 20));
        $b   = max(0, min(255, hexdec(substr($hex, 4, 2)) + ($step * 8) - 10));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
