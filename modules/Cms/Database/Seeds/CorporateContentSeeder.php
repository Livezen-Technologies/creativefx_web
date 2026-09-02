<?php

namespace Modules\Cms\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the corporate content pages (Our Story, Our Expertise, Manufacturing,
 * Impact, Careers, Contact, and a coming-soon Showroom) onto the block-based
 * CMS. Idempotent: each page's sections/blocks are cleared and re-seeded.
 *
 * Localization convention: hero/section titles are provided in all 4 locales;
 * longer descriptive copy is English (+ Spanish where short) and falls back via
 * t_field(). Full translation is handled later by the Translation Manager.
 */
class CorporateContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($this->pages() as $slug => $def) {
            $this->seedPage($slug, $def, $now);
        }
    }

    /**
     * Build a locale-map, dropping null locales. Any locale not supplied here
     * is completed from the content translation dictionary, so seeded copy
     * lands fully translated in en/ja/es/zh.
     */
    private function loc(string $en, ?string $es = null, ?string $ja = null, ?string $zh = null): array
    {
        helper('norlanka');

        return content_locales(
            array_filter(['en' => $en, 'es' => $es, 'ja' => $ja, 'zh' => $zh], static fn ($v) => $v !== null)
        );
    }

    private function seedPage(string $slug, array $def, string $now): void
    {
        $pages = $this->db->table('pages');
        $data  = [
            'slug'             => $slug,
            'title'            => json_encode($def['title'], JSON_UNESCAPED_UNICODE),
            'meta_description' => json_encode($def['meta'] ?? $def['title'], JSON_UNESCAPED_UNICODE),
            'template'         => 'default',
            'is_home'          => 0,
            'status'           => 'published',
            'updated_at'       => $now,
        ];

        if ($pages->where('slug', $slug)->get()->getRowArray() === null) {
            $pages->insert($data + ['created_at' => $now]);
        } else {
            $pages->where('slug', $slug)->update($data);
        }
        $pageId = (int) $pages->where('slug', $slug)->get()->getRowArray()['id'];

        // Idempotent reset of this page's structure.
        $ids = array_column(
            $this->db->table('page_sections')->select('id')->where('page_id', $pageId)->get()->getResultArray(),
            'id'
        );
        if ($ids !== []) {
            $this->db->table('page_blocks')->whereIn('section_id', $ids)->delete();
            $this->db->table('page_sections')->where('page_id', $pageId)->delete();
        }

        $order = 0;
        foreach ($def['sections'] as $section) {
            $this->db->table('page_sections')->insert([
                'page_id'    => $pageId,
                'key'        => $section['key'] ?? null,
                'type'       => $section['type'] ?? 'generic',
                'sort_order' => $order++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sectionId = (int) $this->db->insertID();

            $b = 0;
            foreach ($section['blocks'] as [$type, $content]) {
                $this->db->table('page_blocks')->insert([
                    'section_id' => $sectionId,
                    'type'       => $type,
                    'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'sort_order' => $b++,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function pages(): array
    {
        return [
            'about-us'      => $this->aboutUs(),
            'our-business'  => $this->ourBusiness(),
            'our-locations' => $this->ourLocations(),
            'contact'       => $this->contact(),
        ];
    }

    private function hero(array $eyebrow, array $title, array $subtitle, ?string $video = null, ?string $poster = null, ?string $image = null): array
    {
        $content = ['eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle];
        if ($video !== null) {
            $content['video']  = $video;
            $content['poster'] = $poster;
        }
        if ($image !== null) {
            $content['image'] = $image;
        }

        return ['key' => 'hero', 'type' => 'hero', 'blocks' => [['pagehero', $content]]];
    }

    private function ctaContact(): array
    {
        return ['key' => 'cta', 'type' => 'cta', 'blocks' => [
            ['cta', [
                'title'  => $this->loc('Bring Magic Corn to your event'),
                'text'   => $this->loc('Outlet enquiries, bulk orders and event catering — talk to our team.'),
                'button' => $this->loc('Contact us', 'Contáctanos', 'お問い合わせ', '联系我们'),
                'url'    => 'contact',
            ]],
        ]];
    }

    private function aboutUs(): array
    {
        return [
            'title' => $this->loc('About Us', 'Sobre Nosotros', '会社概要', '关于我们'),
            'meta'  => $this->loc('Magic Corn — the original corn in a cup, homegrown in Sri Lanka since 2007.'),
            'sections' => [
                $this->hero(
                    $this->loc('About Us', 'Sobre Nosotros', '会社概要', '关于我们'),
                    $this->loc('Sri Lanka’s original corn in a cup'),
                    $this->loc('A 100% homegrown brand, cultivating sweet corn on a commercial scale since 2007.'),
                    null, null, '/media/magiccorn/corn.jpg'
                ),
                ['key' => 'story', 'type' => 'richtext', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Our story'),
                        'title'   => $this->loc('From one outlet to a household name'),
                        'text'    => $this->loc(
                            'Magic Corn was founded in Sri Lanka in 2007 by Mr. Shanker Viswakula, who introduced '
                            . 'the “Corn in a Cup” concept to the Sri Lankan snack industry. What began as a single '
                            . 'outlet has grown to more than thirty across the island in the space of twelve years. '
                            . 'We cultivate sweet corn on a commercial scale and remain a 100% homegrown brand — '
                            . 'today an ISO 9001 certified company.'
                        ),
                    ]],
                ]],
                ['key' => 'numbers', 'type' => 'metrics', 'blocks' => [
                    ['metrics', [
                        'title' => $this->loc('Magic Corn today'),
                        'items' => [
                            ['value' => '2007', 'suffix' => '', 'label' => $this->loc('Founded in Sri Lanka')],
                            ['value' => '30', 'suffix' => '+', 'label' => $this->loc('Outlets island-wide')],
                            ['value' => '40', 'suffix' => '+', 'label' => $this->loc('Women employed at our factory')],
                            ['value' => '100', 'suffix' => '%', 'label' => $this->loc('Homegrown Sri Lankan brand')],
                        ],
                    ]],
                ]],
                ['key' => 'values', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('What goes in the cup'),
                        'items' => [
                            ['title' => $this->loc('100% natural spices'),
                             'text'  => $this->loc('Precooked sweet corn, steamed and mixed with natural spices, then smothered in toppings to order.')],
                            ['title' => $this->loc('No MSG, no preservatives'),
                             'text'  => $this->loc('No MSG, added flavours or preservatives are used in preparing our corn.')],
                            ['title' => $this->loc('ISO 9001 certified'),
                             'text'  => $this->loc('Our sweet corn processing is run to a certified quality management standard.')],
                            ['title' => $this->loc('Grown and made here'),
                             'text'  => $this->loc('Sweet corn cultivated on a commercial scale in Sri Lanka — a fully homegrown supply chain.')],
                        ],
                    ]],
                ]],
                ['key' => 'gallery', 'type' => 'gallery', 'blocks' => [
                    ['gallery', [
                        'eyebrow' => $this->loc('Magic Corn'),
                        'title'   => $this->loc('From the field to the cup'),
                        'items'   => [
                            ['src' => '/media/magiccorn/5.jpg', 'caption' => $this->loc('Sweet corn grown on a commercial scale in Sri Lanka')],
                            ['src' => '/media/magiccorn/4.jpg', 'caption' => $this->loc('Harvested and brought in for processing')],
                            ['src' => '/media/magiccorn/6.jpg', 'caption' => $this->loc('Husked and prepared at our factory')],
                            ['src' => '/media/magiccorn/2.jpg', 'caption' => $this->loc('Served hot in a cup at our outlets')],
                        ],
                    ]],
                ]],
                ['key' => 'impact', 'type' => 'statement', 'blocks' => [
                    ['statement', [
                        'items' => [
                            ['title' => $this->loc('Livelihoods in rural Sri Lanka'),
                             'text'  => $this->loc(
                                'Our sweet corn processing factory provides job opportunities to more than forty '
                                . 'female staff in rural areas, giving their families an additional income.'
                             )],
                        ],
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }

    private function ourBusiness(): array
    {
        return [
            'title' => $this->loc('Our Business', 'Nuestro Negocio', '事業内容', '我们的业务'),
            'meta'  => $this->loc('Magic Corn — cultivation, processing and retail of sweet corn across Sri Lanka.'),
            'sections' => [
                $this->hero(
                    $this->loc('Our Business', 'Nuestro Negocio', '事業内容', '我们的业务'),
                    $this->loc('From our fields to your cup'),
                    $this->loc('We grow, process and serve sweet corn — the whole chain, under one brand.'),
                    null, null, '/media/magiccorn/5.jpg'
                ),
                ['key' => 'process', 'type' => 'process', 'blocks' => [
                    ['process_steps', [
                        'title' => $this->loc('How Magic Corn is made'),
                        'items' => [
                            ['title' => $this->loc('Cultivation'),
                             'text'  => $this->loc('Sweet corn grown on a commercial scale in Sri Lanka.')],
                            ['title' => $this->loc('Processing'),
                             'text'  => $this->loc('Harvested corn is precooked and frozen at our ISO 9001 certified factory.')],
                            ['title' => $this->loc('Steamed to order'),
                             'text'  => $this->loc('At the outlet the corn is steamed and mixed with 100% natural spices.')],
                            ['title' => $this->loc('Topped your way'),
                             'text'  => $this->loc('Smothered in the toppings you choose, and served hot in a cup.')],
                        ],
                    ]],
                ]],
                ['key' => 'flavours', 'type' => 'cards', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Flavours and toppings'),
                        'intro' => $this->loc('Selected flavours are combined by our team to bring unique combinations to every cup.'),
                        'items' => [
                            ['title' => $this->loc('Butter'),  'text' => $this->loc('The classic — melted through hot, freshly steamed corn.')],
                            ['title' => $this->loc('Garlic'),  'text' => $this->loc('Savoury and aromatic, a favourite with regulars.')],
                            ['title' => $this->loc('Cheese'),  'text' => $this->loc('Rich and generous, stirred right through the cup.')],
                            ['title' => $this->loc('Mayo'),    'text' => $this->loc('Creamy, and the base for many of our combinations.')],
                            ['title' => $this->loc('Minced chicken'), 'text' => $this->loc('For a cup that eats like a meal.')],
                            ['title' => $this->loc('Lime & oyster sauce'), 'text' => $this->loc('Bright and savoury — the combination that regulars come back for.')],
                        ],
                    ]],
                ]],
                ['key' => 'channels', 'type' => 'cards', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Where to find us'),
                        'items' => [
                            ['title' => $this->loc('Our outlets'),
                             'text'  => $this->loc('More than thirty Magic Corn outlets serving corn in a cup across the island.')],
                            ['title' => $this->loc('Frozen retail packs'),
                             'text'  => $this->loc('Take home our 1kg frozen sweet corn pack and make it your own way.')],
                            ['title' => $this->loc('Bulk and wholesale'),
                             'text'  => $this->loc('Frozen sweet corn supplied to hotels, restaurants and caterers.')],
                            ['title' => $this->loc('Events and catering'),
                             'text'  => $this->loc('Magic Corn carts and catering for parties, offices and functions.')],
                        ],
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }

    private function ourLocations(): array
    {
        return [
            'title' => $this->loc('Our Locations', 'Nuestras Ubicaciones', '店舗情報', '门店位置'),
            'meta'  => $this->loc('Magic Corn outlets across Sri Lanka, and our head office in Dehiwala.'),
            'sections' => [
                $this->hero(
                    $this->loc('Our Locations', 'Nuestras Ubicaciones', '店舗情報', '门店位置'),
                    $this->loc('Thirty-plus outlets across the island'),
                    $this->loc('Find your nearest Magic Corn, or talk to us about opening one.'),
                    null, null, '/media/magiccorn/2.jpg'
                ),
                ['key' => 'map', 'type' => 'map', 'blocks' => [
                    ['map', [
                        'title' => $this->loc('Head office and factory'),
                        'intro' => $this->loc('Our head office is in Dehiwala, with sweet corn processing supplying every outlet.'),
                        'items' => [
                            ['region' => $this->loc('Dehiwala'), 'detail' => $this->loc('Head office — No 119, Allen Avenue'),
                             'lat' => 6.8511, 'lon' => 79.8653, 'hq' => true],
                        ],
                    ]],
                ]],
                ['key' => 'outlets', 'type' => 'richtext', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Outlets'),
                        'title'   => $this->loc('Find your nearest cup'),
                        'text'    => $this->loc(
                            'Magic Corn has grown from a single outlet in 2007 to more than thirty across Sri Lanka. '
                            . 'For the outlet nearest you, or to ask about opening a Magic Corn of your own, get in touch '
                            . 'and our team will help.'
                        ),
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }

    private function contact(): array
    {
        return [
            'title' => $this->loc('Contact Us', 'Contacto', 'お問い合わせ', '联系我们'),
            'meta'  => $this->loc('Talk to Magic Corn — orders, outlets, bulk supply and events.'),
            'sections' => [
                $this->hero(
                    $this->loc('Contact Us', 'Contacto', 'お問い合わせ', '联系我们'),
                    $this->loc('Get in touch'),
                    $this->loc('Open every day, 9:00 AM to 8:00 PM.'),
                    null, null, '/media/magiccorn/best-corn.jpg'
                ),
                ['key' => 'form', 'type' => 'contact', 'blocks' => [
                    ['contact_block', [
                        'title' => $this->loc('Send us a message'),
                        'intro' => $this->loc('For orders, outlet enquiries, bulk supply or event catering — we would love to hear from you.'),
                    ]],
                ]],
                ['key' => 'where', 'type' => 'map', 'blocks' => [
                    ['map', [
                        'title' => $this->loc('Where we are'),
                        'intro' => $this->loc('No 119, Allen Avenue, Dehiwala, Sri Lanka.'),
                        'items' => [
                            ['region' => $this->loc('Dehiwala'), 'detail' => $this->loc('Head office — No 119, Allen Avenue'),
                             'lat' => 6.8511, 'lon' => 79.8653, 'hq' => true],
                        ],
                    ]],
                ]],
            ],
        ];
    }
}
