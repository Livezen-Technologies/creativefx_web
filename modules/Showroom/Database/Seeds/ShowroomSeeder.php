<?php

namespace Modules\Showroom\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the 12 themed virtual-showroom categories from the blueprint, each with
 * a theme name, environment tagline, colour palette, environment features and
 * its real product line — enriched with fabric composition, MOQ, size run and
 * collection. Idempotent: clears products + categories then reseeds.
 *
 * Names/themes/taglines carry en + es locale-maps (t_field falls back to en for
 * ja/zh); the environment feature list + product specs are stored as data.
 */
class ShowroomSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $j   = static fn ($v): string => json_encode($v, JSON_UNESCAPED_UNICODE);

        $sizeSets = [
            'baby'      => ['NB', '0-3M', '3-6M', '6-12M', '12-18M', '18-24M'],
            'kids'      => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-12Y', '13-14Y'],
            'school'    => ['4Y', '6Y', '8Y', '10Y', '12Y', '14Y', '16Y'],
            'adult'     => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'accessory' => ['One Size'],
        ];
        $moqs        = ['300 pcs / colour', '500 pcs / colour', '750 pcs / colour', '1,000 pcs / style'];
        $collections = ['Core', 'SS25', 'AW25', 'Eco Line'];

        // Each: slug, en, es, theme(3D keyword), theme_name(en/es), tagline(en/es),
        // palette[3], features[], sizeset, products[[en, es, fabric]].
        $cats = [
            [
                'slug' => 'babywear', 'en' => 'Babywear', 'es' => 'Ropa de bebé', 'theme' => 'clouds',
                'tname_en' => 'Soft Nursery World', 'tname_es' => 'Mundo de Guardería',
                'tag_en' => 'Floating clouds, moon & stars and soft wooden toy shelves under a gentle glow.',
                'tag_es' => 'Nubes flotantes, luna y estrellas y suaves estantes de madera bajo una luz tenue.',
                'palette' => ['#a9d4ff', '#ffc9de', '#fff6e9'],
                'features' => ['Floating clouds', 'Moon & stars', 'Wooden toy shelves', 'Soft ambient lighting'],
                'sizeset' => 'baby',
                'products' => [
                    ['Rompers', 'Ranitas', '100% organic cotton interlock',
                        'A timeless baby romper in soft cheesecloth gingham, finished with an embroidered '
                        . 'frill collar for a charming heritage-inspired look.',
                        null, null,
                        '/media/models/meadow-p2-ss27-colorway-1.glb',
                        // Meadow romper renders, front first as the opening frame.
                        ['/media/showroom/babywear/meadow-romper-front.webp',
                         '/media/showroom/babywear/meadow-romper-side.webp',
                         '/media/showroom/babywear/meadow-romper-back.webp'],
                        0.9],
                    // Models matched to what each one actually depicts: UK138 is a
                    // teal two-piece, PH2065 a knit-and-shorts outfit, UK136 a
                    // full-length sleepsuit.
                    ['Bodysuits', 'Bodies', 'GOTS organic cotton rib',
                        'Soft TENCEL™ top and pants set featuring a scalloped embroidered collar, an '
                        . 'authentic soft wash, and elasticated hem bottoms for a comfortable, timeless finish',
                        null, null,
                        '/media/models/meadow-uk138-colorway-1.glb',
                        ['/media/showroom/babywear/denim-set-uk138-front.webp',
                         '/media/showroom/babywear/denim-set-uk138-side.webp',
                         '/media/showroom/babywear/denim-set-uk138-back.webp']],
                    ['Baby Sets', 'Conjuntos de bebé', 'Combed cotton jersey',
                        'Two-piece set featuring a soft peached finish brush back sweat top paired with '
                        . 'textured seersucker crinkle jersey bottoms. The sweatshirt is elevated with a '
                        . 'branded woven label and a striking combination of flat and puff print on the '
                        . 'back, adding depth and a modern look. Designed for all-day comfort with a '
                        . 'contemporary finish, this versatile set is perfect for everyday wear.',
                        null, null,
                        '/media/models/prless-ph2065-colorway-1.glb',
                        ['/media/showroom/babywear/knit-set-ph2065-front.webp',
                         '/media/showroom/babywear/knit-set-ph2065-side.webp',
                         '/media/showroom/babywear/knit-set-ph2065-back.webp']],
                    ['Sleepwear', 'Pijamas de bebé', 'Bamboo-cotton blend',
                        'Soft interlock sleepsuit featuring a contrast neck trim and delicate floral '
                        . 'embroidery on the front, adding a beautifully elevated touch to this everyday essential',
                        null, null,
                        '/media/models/meadow-uk136-colorway-1.glb',
                        ['/media/showroom/babywear/sleepsuit-uk136-front.webp',
                         '/media/showroom/babywear/sleepsuit-uk136-side.webp',
                         '/media/showroom/babywear/sleepsuit-uk136-back.webp']],
                ],
            ],
            [
                'slug' => 'childrenswear', 'en' => 'Kidswear', 'es' => 'Ropa infantil', 'theme' => 'playground',
                'tname_en' => 'Denim Play Studio', 'tname_es' => 'Estudio Vaquero',
                'tag_en' => 'A bright kids studio styled around Norlanka\'s denim-led collection — dungarees, pinafores, jackets and coordinated sets.',
                'tag_es' => 'Un estudio infantil luminoso en torno a la colección vaquera de Norlanka: petos, pichis, chaquetas y conjuntos.',
                'palette' => ['#5b86b5', '#d1a03f', '#1e2749'],
                'features' => ['Denim styling wall', 'Coordinated set displays', 'Playful appliqué details', 'Soft daylight studio'],
                'sizeset' => 'kids',
                // NORLANKA KIDSWEAR denim line. Each: en, es, fabric, description, swatches.
                'products' => [
                    ['Denim Pinafore Set', 'Conjunto de pichi vaquero', 'Cotton chambray denim + cotton jersey',
                        'A soft-wash denim pinafore dress layered over a mustard leopard-print long-sleeve tee — a ready-to-wear two-piece finished with a "good vibes grow here" patch.',
                        ['#5b86b5', '#d1a03f', '#3a3f4a'], '/media/showroom/kids/denim-pinafore-set.jpg'],
                    ['Denim Dungarees', 'Peto vaquero', 'Cotton denim, light wash',
                        'Classic light-wash denim dungarees with adjustable buckle straps, a bib pocket and roomy front pockets — an everyday play staple.',
                        ['#7fa8d0', '#4f7bb0', '#efe9dd'], '/media/showroom/kids/denim-dungarees.jpg'],
                    ['Appliqué Denim Jacket', 'Chaqueta vaquera con parches', 'Cotton denim',
                        'A light-wash denim jacket finished with unicorn, rainbow "Bright Days" and strawberry appliqués, puff shoulders and a rounded collar.',
                        ['#8fb4dd', '#f4a9c0', '#5bbf6a'], '/media/showroom/kids/denim-jacket.jpg'],
                    ['Striped Shirt & Leggings Set', 'Conjunto de camisa a rayas y leggings', 'Yarn-dyed cotton + cotton-elastane jersey',
                        'A frill-collar blue-stripe shirt paired with a white tee and soft navy leggings — an easy coordinated three-piece.',
                        ['#9ec3e0', '#f5f5f5', '#1e2749'], '/media/showroom/kids/striped-shirt-set.jpg'],
                    ['Lace-Collar Denim Set', 'Conjunto vaquero con cuello de encaje', 'Cotton denim + cotton jersey',
                        'A denim shirt with a statement lace frill collar, matched with animal-spot print leggings.',
                        ['#4f7bb0', '#f5f5f5', '#2b2f36'], '/media/showroom/kids/lace-collar-denim-set.jpg'],
                    ['Ruffle Denim Set', 'Conjunto vaquero con volantes', 'Cotton denim + cotton jersey',
                        'A tiered-ruffle denim shirt with mono grid-check leggings — texture-led and playful.',
                        ['#4a72a8', '#2b2f36', '#f5f5f5'], '/media/showroom/kids/ruffle-denim-set.jpg'],
                    ['Ruffle Denim & Gingham Set', 'Conjunto vaquero y vichy', 'Cotton chambray + cotton jersey',
                        'A soft-wash ruffle-tier chambray shirt teamed with blue gingham leggings.',
                        ['#8fb4dd', '#6f97c4', '#f5f5f5'], '/media/showroom/kids/ruffle-gingham-set.jpg'],
                ],
            ],
            [
                'slug' => 'kids-nightwear', 'en' => "Kids' Nightwear", 'es' => 'Pijamas infantiles', 'theme' => 'night',
                'tname_en' => 'Dreamland', 'tname_es' => 'País de los Sueños',
                'tag_en' => 'A moonlit bedroom with floating stars, dream clouds and magical lighting.',
                'tag_es' => 'Un dormitorio iluminado por la luna con estrellas flotantes y luz mágica.',
                'palette' => ['#1b2a6b', '#7b5cd6', '#c9d1e0'],
                'features' => ['Moonlit bedroom', 'Floating stars', 'Dream clouds', 'Magical lighting'],
                'sizeset' => 'kids',
                'products' => [
                    ['Pajama Sets', 'Conjuntos de pijama', 'Organic cotton interlock'],
                    ['Sleepwear Collections', 'Colecciones de dormir', 'Cotton-modal blend'],
                    ['Loungewear', 'Ropa de estar', 'Brushed cotton fleece'],
                ],
            ],
            [
                'slug' => 'school-wear', 'en' => 'School Wear', 'es' => 'Uniformes escolares', 'theme' => 'classroom',
                'tname_en' => 'Modern Learning Campus', 'tname_es' => 'Campus de Aprendizaje',
                'tag_en' => 'School corridors, classroom displays, library zones and a sports arena.',
                'tag_es' => 'Pasillos escolares, aulas, zonas de biblioteca y un pabellón deportivo.',
                'palette' => ['#1f2a52', '#9aa3b2', '#f4f6fb'],
                'features' => ['School corridor', 'Classroom displays', 'Library zones', 'Sports arena'],
                'sizeset' => 'school',
                'products' => [
                    ['School Uniforms', 'Uniformes escolares', 'Poly-cotton poplin'],
                    ['Sports Uniforms', 'Uniformes deportivos', 'Recycled performance polyester'],
                    ['Blazers', 'Blazers', 'Wool-blend suiting'],
                    ['Accessories', 'Accesorios', 'Woven poly-cotton'],
                ],
            ],
            [
                'slug' => 'accessories', 'en' => 'Accessories', 'es' => 'Accesorios', 'theme' => 'luxury',
                'tname_en' => 'Luxury Fashion Gallery', 'tname_es' => 'Galería de Moda de Lujo',
                'tag_en' => 'Glass showcases and premium display cabinets under focused spotlights.',
                'tag_es' => 'Vitrinas de cristal y vitrinas premium bajo focos dirigidos.',
                'palette' => ['#d4af37', '#111114', '#f5f5f5'],
                'features' => ['Glass showcases', 'Premium display cabinets', 'Spotlight lighting'],
                'sizeset' => 'accessory',
                'products' => [
                    ['Bags', 'Bolsos', 'Recycled canvas & PU'],
                    ['Caps', 'Gorras', 'Cotton twill'],
                    ['Belts', 'Cinturones', 'Vegan leather'],
                    ['Gloves', 'Guantes', 'Knitted acrylic-wool'],
                    ['Fashion Accessories', 'Accesorios de moda', 'Mixed materials'],
                ],
            ],
            [
                'slug' => 'true-knits', 'en' => 'True Knits', 'es' => 'Tejidos de punto', 'theme' => 'textile',
                'tname_en' => 'Artisan Knit Studio', 'tname_es' => 'Estudio de Punto Artesanal',
                'tag_en' => 'Yarn walls, textile displays and live knitting-machine exhibits.',
                'tag_es' => 'Paredes de hilo, muestras textiles y exhibiciones de máquinas de tejer.',
                'palette' => ['#f3ead6', '#d9c4a3', '#8a5a3b'],
                'features' => ['Yarn walls', 'Textile displays', 'Knitting machine exhibits', 'Fabric technology demos'],
                'sizeset' => 'adult',
                'products' => [
                    ['Knitwear Collections', 'Colecciones de punto', 'Merino wool-cotton'],
                    ['Premium Knit Fabrics', 'Tejidos de punto premium', 'Organic cotton interlock'],
                ],
            ],
            [
                'slug' => 'hosiery-toys', 'en' => 'Hosiery & Toys', 'es' => 'Calcetería y juguetes', 'theme' => 'toys',
                'tname_en' => 'Fun Factory', 'tname_es' => 'Fábrica de Diversión',
                'tag_en' => 'A toy-manufacturing world of colourful production lines and play stations.',
                'tag_es' => 'Un mundo de fabricación de juguetes con líneas coloridas y estaciones de juego.',
                'palette' => ['#ef3e36', '#ffce3a', '#2f9bd6'],
                'features' => ['Toy manufacturing world', 'Colourful production lines', 'Interactive play stations'],
                'sizeset' => 'accessory',
                'products' => [
                    ['Hosiery', 'Calcetería', 'Combed cotton-elastane'],
                    ['Socks', 'Calcetines', 'Cotton-nylon terry'],
                    ['Plush Toys', 'Peluches', 'Recycled-PET plush'],
                    ['Educational Toys', 'Juguetes educativos', 'Organic cotton & wood'],
                ],
            ],
            [
                'slug' => 'adults-woven', 'en' => 'Adults – Woven', 'es' => 'Adultos – Tejido plano', 'theme' => 'boutique',
                'tname_en' => 'Executive Fashion Avenue', 'tname_es' => 'Avenida de Moda Ejecutiva',
                'tag_en' => 'A luxury shopping boulevard of boutique storefronts and premium retail.',
                'tag_es' => 'Un bulevar de compras de lujo con escaparates boutique y retail premium.',
                'palette' => ['#2b2d33', '#1f2a52', '#c9a227'],
                'features' => ['Luxury shopping boulevard', 'Boutique storefronts', 'Premium retail experience'],
                'sizeset' => 'adult',
                'products' => [
                    ['Shirts', 'Camisas', 'Organic cotton poplin'],
                    ['Dresses', 'Vestidos', 'Tencel-cotton twill'],
                    ['Trousers', 'Pantalones', 'Cotton-stretch chino'],
                    ['Formal Collections', 'Colecciones formales', 'Wool-blend suiting'],
                ],
            ],
            [
                'slug' => 'adults-jersey', 'en' => 'Adults – Jersey', 'es' => 'Adultos – Punto', 'theme' => 'urban',
                'tname_en' => 'Urban Lifestyle Loft', 'tname_es' => 'Loft de Estilo Urbano',
                'tag_en' => 'A modern apartment with city-skyline views and contemporary interiors.',
                'tag_es' => 'Un apartamento moderno con vistas al skyline e interiores contemporáneos.',
                'palette' => ['#f4f6fb', '#8d99ae', '#15161a'],
                'features' => ['Modern apartment setting', 'City skyline views', 'Contemporary interiors'],
                'sizeset' => 'adult',
                'products' => [
                    ['T-Shirts', 'Camisetas', 'Combed cotton single jersey'],
                    ['Casual Wear', 'Ropa casual', 'Cotton-elastane jersey'],
                    ['Lounge Collections', 'Colecciones de estar', 'Organic French terry'],
                ],
            ],
            [
                'slug' => 'activewear', 'en' => 'Activewear', 'es' => 'Ropa deportiva', 'theme' => 'arena',
                'tname_en' => 'Performance Arena', 'tname_es' => 'Arena de Rendimiento',
                'tag_en' => 'A running track, gym environment and outdoor adventure zones.',
                'tag_es' => 'Una pista de atletismo, gimnasio y zonas de aventura al aire libre.',
                'palette' => ['#2d6cff', '#57e389', '#111114'],
                'features' => ['Running track', 'Gym environment', 'Outdoor adventure zones', 'Performance tech displays'],
                'sizeset' => 'adult',
                'products' => [
                    ['Sportswear', 'Ropa deportiva', 'Recycled polyester-elastane'],
                    ['Fitness Apparel', 'Ropa de fitness', 'Seamless performance knit'],
                    ['Outdoor Wear', 'Ropa de exterior', 'Recycled ripstop'],
                ],
            ],
            [
                'slug' => 'maternity', 'en' => 'Maternity', 'es' => 'Maternidad', 'theme' => 'wellness',
                'tname_en' => 'Comfort & Care Lounge', 'tname_es' => 'Salón de Confort y Cuidado',
                'tag_en' => 'Wellness-inspired interiors and an elegant home setting in soft natural light.',
                'tag_es' => 'Interiores inspirados en el bienestar y un hogar elegante con luz natural suave.',
                'palette' => ['#c7b8ea', '#f7efe1', '#e6b9a6'],
                'features' => ['Wellness-inspired interiors', 'Elegant home setting', 'Soft natural lighting'],
                'sizeset' => 'adult',
                'products' => [
                    ['Maternity Wear', 'Ropa de maternidad', 'Cotton-modal stretch'],
                    ['Nursing Wear', 'Ropa de lactancia', 'Organic cotton jersey'],
                    ['Comfort Essentials', 'Básicos de confort', 'Bamboo-cotton blend'],
                ],
            ],
            [
                'slug' => 'adults-essentials', 'en' => "Adults' Essentials & Nightwear", 'es' => 'Básicos y ropa de dormir', 'theme' => 'bedroom',
                'tname_en' => 'Luxury Bedroom Suite', 'tname_es' => 'Suite de Dormitorio de Lujo',
                'tag_en' => 'A premium hotel suite with a relaxing bedroom ambiance.',
                'tag_es' => 'Una suite de hotel premium con un ambiente de dormitorio relajante.',
                'palette' => ['#1f2a52', '#e3d5bd', '#d8b367'],
                'features' => ['Premium hotel suite', 'Relaxing bedroom ambiance'],
                'sizeset' => 'adult',
                'products' => [
                    ['Nightwear', 'Ropa de dormir', 'Cotton-modal sateen'],
                    ['Loungewear', 'Ropa de estar', 'Organic French terry'],
                    ['Innerwear', 'Ropa interior', 'Micro-modal rib'],
                    ['Everyday Essentials', 'Básicos diarios', 'Combed cotton jersey'],
                ],
            ],
        ];

        $catTable  = $this->db->table('showroom_categories');
        $prodTable = $this->db->table('showroom_products');

        // Reset (children first for FK safety).
        $prodTable->where('id >', 0)->delete();
        $catTable->where('id >', 0)->delete();

        $order = 0;
        foreach ($cats as $c) {
            $catTable->insert([
                'slug'       => $c['slug'],
                'name'       => $j(['en' => $c['en'], 'es' => $c['es']]),
                'theme'      => $c['theme'],
                'theme_name' => $j(['en' => $c['tname_en'], 'es' => $c['tname_es']]),
                'tagline'    => $j(['en' => $c['tag_en'], 'es' => $c['tag_es']]),
                'background' => $c['palette'][0],
                'palette'    => $j($c['palette']),
                'features'   => $j($c['features']),
                // Category photo from the company profile portfolio (optional).
                'image'      => file_exists(FCPATH . 'media/showroom/categories/' . $c['slug'] . '.jpg')
                    ? '/media/showroom/categories/' . $c['slug'] . '.jpg' : null,
                'sort_order' => $order++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryId = (int) $this->db->insertID();

            $sizes = $sizeSets[$c['sizeset']];
            $n     = count($c['products']);
            foreach ($c['products'] as $i => $p) {
                [$pen, $pes, $fabric] = $p;
                // Optional per-product description (index 3), colour swatches
                // (index 4), photo path (index 5), 3D model (index 6) and drawer
                // photo set (index 7); otherwise
                // fall back to a generic line + the category palette and no photo
                // (swatch display). A model, where present, supersedes the photo
                // on the 3D stage.
                $desc     = $p[3] ?? ('Responsibly manufactured ' . strtolower($pen)
                    . ' for the ' . $c['en'] . ' segment — engineered for quality, comfort and scale, '
                    . 'with full-package development from fabric to finished garment.');
                $swatches = $p[4] ?? $c['palette'];
                $image    = $p[5] ?? null;
                $model    = $p[6] ?? null;
                $shots    = $p[7] ?? null;   // drawer photo set
                $scale    = $p[8] ?? null;   // optional 3D size multiplier
                $prodTable->insert([
                    'showroom_category_id' => $categoryId,
                    'slug'        => $c['slug'] . '-' . ($i + 1),
                    'name'        => $j(['en' => $pen, 'es' => $pes]),
                    'description' => $j(['en' => $desc]),
                    'hotspot'     => $j($this->position($i, $n, $scale)),
                    'gallery'     => $j($swatches),
                    'image'       => $image,
                    'model_path'  => $model,
                    'images'      => $shots ? $j($shots) : null,
                    'materials'   => $j($this->materials($order + $i)),
                    'fabric'      => $fabric,
                    'moq'         => $moqs[($order + $i) % count($moqs)],
                    'sizes'       => $j($sizes),
                    'collection'  => $collections[$i % count($collections)],
                    'sort_order'  => $i + 1,
                    'status'      => 'published',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    /** Spread N product pedestals along a gentle front arc for the 3D stage. */
    private function position(int $i, int $n, ?float $scale = null): array
    {
        $x = $n > 1 ? -3.6 + (7.2 * $i / ($n - 1)) : 0.0;
        $z = 0.2 + 0.55 * sin($i * 1.1);

        $spot = ['x' => round($x, 2), 'y' => 1.1, 'z' => round($z, 2)];
        // `s` scales just this product's model on the 3D stage.
        if ($scale !== null && $scale > 0 && $scale !== 1.0) {
            $spot['s'] = $scale;
        }

        return $spot;
    }

    /** Two material filter tags per product from a shared pool. */
    private function materials(int $seed): array
    {
        $pool = ['Organic Cotton', 'Recycled Polyester', 'Bamboo', 'Performance Knit', 'Cotton Jersey', 'French Terry', 'Merino Wool', 'Linen Blend'];

        return [$pool[$seed % count($pool)], $pool[($seed + 3) % count($pool)]];
    }
}
