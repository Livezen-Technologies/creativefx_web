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
     * lands fully translated in every supported locale.
     */
    private function loc(string $en): array
    {
        helper('norlanka');

        return content_locales(['en' => $en]);
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
            'accommodation' => $this->accommodation(),
            'dining'        => $this->dining(),
            'things-to-do'  => $this->thingsToDo(),
            'gallery'       => $this->gallery(),
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

    /** Every page ends by pointing at the booking request form. */
    private function ctaBook(): array
    {
        return ['key' => 'cta', 'type' => 'cta', 'blocks' => [
            ['cta', [
                'title'  => $this->loc('Reserve your room at Giants Forest'),
                'text'   => $this->loc('Tell us your dates and we will confirm availability by phone or email.'),
                'button' => $this->loc('Book Now'),
                'url'    => 'contact',
            ]],
        ]];
    }

    private function accommodation(): array
    {
        return [
            'title' => $this->loc('Accommodation'),
            'meta'  => $this->loc('Deluxe and Standard rooms at Kukuleganga Giants Forest, Kalawana — balconies over the Sabaragamuwa hills.'),
            'sections' => [
                $this->hero(
                    $this->loc('Accommodation'),
                    $this->loc('Rooms & Suites'),
                    $this->loc('Engage with modern luxuries at your own place.'),
                    null, null, '/media/giantforests/Accommodation-1-Giants-Forests-Hotel.jpg'
                ),
                ['key' => 'rooms', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('Two ways to stay'),
                        'items' => [
                            ['title' => $this->loc('Deluxe Room'),
                             'image' => '/media/giantforests/Deluxe-Room-Giants-forest.jpg',
                             'text'  => $this->loc('Superior double rooms with an open living area, and a balcony that gives you the Sabaragamuwa hills at first light.')],
                            ['title' => $this->loc('Standard Room'),
                             'image' => '/media/giantforests/Single-Room-Giants-forest.jpg',
                             'text'  => $this->loc('Wooden ceilings keep these rooms cool through the day. Every facility of the hotel comes with them.')],
                        ],
                    ]],
                ]],
                ['key' => 'facilities', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'eyebrow' => $this->loc('Our facilities'),
                        'title'   => $this->loc('What comes with the room'),
                        'items'   => [
                            ['title' => $this->loc('24 hour room service'),  'text' => $this->loc('Someone is on call whatever time you need them.')],
                            ['title' => $this->loc('Laundry service'),        'text' => $this->loc('Same-day laundry for guests staying with us.')],
                            ['title' => $this->loc('Conference hall'),        'text' => $this->loc('A hall for meetings, training and small functions.')],
                            ['title' => $this->loc('Parking'),                'text' => $this->loc('Off-road parking on site for every room.')],
                            ['title' => $this->loc('35m swimming pool'),      'text' => $this->loc('A 35 metre pool with a separate baby pool beside it.')],
                            ['title' => $this->loc('A/C and non-A/C rooms'),  'text' => $this->loc('Large rooms in both, so you can take the climate as you prefer it.')],
                        ],
                    ]],
                ]],
                ['key' => 'rooms-gallery', 'type' => 'gallery', 'blocks' => [
                    ['gallery', [
                        'eyebrow' => $this->loc('Inside the rooms'),
                        'title'   => $this->loc('See before you feel'),
                        'items'   => [
                            ['src' => '/media/giantforests/Deluxe-Room-1-Giants-forest-Hotel.jpg', 'caption' => $this->loc('Deluxe Room')],
                            ['src' => '/media/giantforests/Deluxe-Room-2-Giants-forest-Hotel.jpg', 'caption' => $this->loc('Deluxe Room — open living area')],
                            ['src' => '/media/giantforests/Deluxe-Room-3-Giants-forest-Hotel.jpg', 'caption' => $this->loc('Deluxe Room — balcony')],
                            ['src' => '/media/giantforests/Single-Room-1-Giants-forest-Hotel.jpg', 'caption' => $this->loc('Standard Room')],
                            ['src' => '/media/giantforests/Single-Room-2-Giants-forest-Hotel.jpg', 'caption' => $this->loc('Standard Room — wooden ceiling')],
                            ['src' => '/media/giantforests/Pool-Giants-Forests-Hotel.jpg',         'caption' => $this->loc('The 35 metre pool')],
                        ],
                    ]],
                ]],
                $this->ctaBook(),
            ],
        ];
    }

    private function dining(): array
    {
        return [
            'title' => $this->loc('Dining'),
            'meta'  => $this->loc('Sri Lankan and international cooking at Kukuleganga Giants Forest, served overlooking the reservoir.'),
            'sections' => [
                $this->hero(
                    $this->loc('Dining'),
                    $this->loc('Eat well, in the middle of the forest'),
                    $this->loc('Sri Lankan cooking and familiar favourites, prepared fresh through the day.'),
                    null, null, '/media/giantforests/Kukuleganga-Giants-Forest-Dining-Banner.jpg'
                ),
                ['key' => 'story', 'type' => 'richtext', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('The restaurant'),
                        'title'   => $this->loc('Tasty, affordable, and made to order'),
                        'text'    => $this->loc(
                            'Our kitchen cooks Sri Lankan food the way it should be, alongside dishes anyone will '
                            . 'recognise, so a table of guests from anywhere can eat together happily. Meals are '
                            . 'prepared to order rather than held on a buffet, and the dining room looks out over '
                            . 'the valley. Room service runs around the clock if you would rather eat upstairs.'
                        ),
                    ]],
                ]],
                ['key' => 'dining-gallery', 'type' => 'gallery', 'blocks' => [
                    ['gallery', [
                        'title' => $this->loc('At the table'),
                        'items' => [
                            ['src' => '/media/giantforests/Restaurant-Giants-forest-Hotel.jpg', 'caption' => $this->loc('The restaurant')],
                            ['src' => '/media/giantforests/Poolside-Giants-Forests-Hotel.jpg',  'caption' => $this->loc('Poolside')],
                            ['src' => '/media/giantforests/Poolhut-Giants-Forests-Hotel.jpg',   'caption' => $this->loc('The pool hut')],
                        ],
                    ]],
                ]],
                $this->ctaBook(),
            ],
        ];
    }

    private function thingsToDo(): array
    {
        return [
            'title' => $this->loc('Things To Do'),
            'meta'  => $this->loc('Sinharaga tracking, boat tours, jungle hikes, cycling and the waterfalls of Ratnapura.'),
            'sections' => [
                $this->hero(
                    $this->loc('Things To Do'),
                    $this->loc('The forest starts at the door'),
                    $this->loc('Sinharaja is 25km away. Most of what follows is a good deal closer.'),
                    null, null, '/media/giantforests/Things-to-do-Giants-Forests-Hotel.jpg'
                ),
                ['key' => 'activities', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('While you are here'),
                        'items' => [
                            ['title' => $this->loc('Sinharaja tracking'),
                             'image' => '/media/giantforests/Sinharaja-Tracking.jpg',
                             'text'  => $this->loc('Guided walks into Sinharaja, the last major stretch of primary rainforest in Sri Lanka, 25km from the hotel.')],
                            ['title' => $this->loc('Boat tours'),
                             'image' => '/media/giantforests/Boat-Tour-1-Giants-Forests-Hotel.jpg',
                             'text'  => $this->loc('Out onto the Kukuleganga reservoir, which the hotel looks over, in the early morning or towards dusk.')],
                            ['title' => $this->loc('Jungle hikes'),
                             'image' => '/media/giantforests/Hiking-Jungle-Tour-1-Giants-Forests-Hotel.jpg',
                             'text'  => $this->loc('Trails through the surrounding jungle, from a gentle hour to most of a day.')],
                            ['title' => $this->loc('Cycling'),
                             'image' => '/media/giantforests/Cycle-tour-1.jpg',
                             'text'  => $this->loc('Quiet roads through tea land and village, with bicycles arranged from the hotel.')],
                            ['title' => $this->loc('Natural bathing'),
                             'image' => '/media/giantforests/Natural-Bathing.jpg',
                             'text'  => $this->loc('Rock pools and river bathing spots within easy reach of the property.')],
                            ['title' => $this->loc('Waterfalls of Ratnapura'),
                             'image' => '/media/giantforests/Bopath-Falls-Ratnapura.jpg',
                             'text'  => $this->loc('Bopath Ella, Katugas Ella and Makeli Ella are all a comfortable drive away.')],
                        ],
                    ]],
                ]],
                ['key' => 'around', 'type' => 'gallery', 'blocks' => [
                    ['gallery', [
                        'eyebrow' => $this->loc('Nearby'),
                        'title'   => $this->loc('The waterfalls'),
                        'items'   => [
                            ['src' => '/media/giantforests/Bopath-Falls-Ratnapura.jpg',        'caption' => $this->loc('Bopath Ella, Ratnapura')],
                            ['src' => '/media/giantforests/Katugas-Falls-Ratnapura.jpg',       'caption' => $this->loc('Katugas Ella, Ratnapura')],
                            ['src' => '/media/giantforests/Makeli-Ella-Waterfall-Ratnapura.jpg', 'caption' => $this->loc('Makeli Ella, Ratnapura')],
                            ['src' => '/media/giantforests/Pandioya-Falls-Ratnapura.jpg',      'caption' => $this->loc('Pandi Oya Falls, Ratnapura')],
                        ],
                    ]],
                ]],
                $this->ctaBook(),
            ],
        ];
    }

    private function gallery(): array
    {
        return [
            'title' => $this->loc('Gallery'),
            'meta'  => $this->loc('The rooms, the pool and the grounds at Kukuleganga Giants Forest.'),
            'sections' => [
                $this->hero(
                    $this->loc('Gallery'),
                    $this->loc('See before you feel'),
                    $this->loc('Step into our captions at your family’s home. Find surroundings that match your eagerness.'),
                    null, null, '/media/giantforests/Gallery-Giants-Forests-Hotel.jpg'
                ),
                ['key' => 'grid', 'type' => 'gallery', 'blocks' => [
                    ['gallery', [
                        'title' => $this->loc('Our captions at the hotel premises'),
                        'items' => [
                            ['src' => '/media/giantforests/Gallery-16-Giants-forest-hotel.jpg', 'caption' => $this->loc('The grounds')],
                            ['src' => '/media/giantforests/Gallery-17-Giants-forest-hotel.jpg', 'caption' => $this->loc('The grounds')],
                            ['src' => '/media/giantforests/Gallery-18-Giants-forest-hotel.jpg', 'caption' => $this->loc('The grounds')],
                            ['src' => '/media/giantforests/Gallery-19-Giants-forest-hotel.jpg', 'caption' => $this->loc('The grounds')],
                            ['src' => '/media/giantforests/Gallery-20-Giants-forest-hotel.jpg', 'caption' => $this->loc('The grounds')],
                            ['src' => '/media/giantforests/Gallery-21-Giants-forest-hotel.jpg', 'caption' => $this->loc('The grounds')],
                            ['src' => '/media/giantforests/Pool-Giants-Forests-Hotel.jpg',      'caption' => $this->loc('The 35 metre pool')],
                            ['src' => '/media/giantforests/Poolside-Giants-Forests-Hotel.jpg',  'caption' => $this->loc('Poolside')],
                            ['src' => '/media/giantforests/Poolhut-Giants-Forests-Hotel.jpg',   'caption' => $this->loc('The pool hut')],
                            ['src' => '/media/giantforests/Deluxe-Room-Giants-forest.jpg',      'caption' => $this->loc('Deluxe Room')],
                            ['src' => '/media/giantforests/Single-Room-Giants-forest.jpg',      'caption' => $this->loc('Standard Room')],
                            ['src' => '/media/giantforests/Restaurant-Giants-forest-Hotel.jpg', 'caption' => $this->loc('The restaurant')],
                        ],
                    ]],
                ]],
                $this->ctaBook(),
            ],
        ];
    }

    private function contact(): array
    {
        return [
            'title' => $this->loc('Contact Us'),
            'meta'  => $this->loc('Book a room at Kukuleganga Giants Forest — Dam Site, Project Road, Kukuleganga, Kalawana.'),
            'sections' => [
                $this->hero(
                    $this->loc('Contact Us'),
                    $this->loc('Reserve your room'),
                    $this->loc('Tell us your dates and we will come back to you with availability.'),
                    null, null, '/media/giantforests/Kukuleganga-Giants-Forest-Contact-Us-1.jpg'
                ),
                ['key' => 'form', 'type' => 'contact', 'blocks' => [
                    ['contact_block', [
                        'title' => $this->loc('Request a booking'),
                        'intro' => $this->loc('Send us your dates, the room you would like and how many are coming. We confirm every request by phone or email — nothing is charged on this site.'),
                    ]],
                ]],
                ['key' => 'where', 'type' => 'map', 'blocks' => [
                    ['map', [
                        'title' => $this->loc('Finding us'),
                        'intro' => $this->loc('Dam Site, Project Road, Kukuleganga, Kalawana 70450. Two and a half hours from Bandaranaike International Airport, 46km from the Dodangoda highway exit.'),
                        'items' => [
                            ['region' => $this->loc('Kukuleganga'), 'detail' => $this->loc('Dam Site, Project Road, Kalawana 70450'),
                             'lat' => 6.5586, 'lon' => 80.3242, 'hq' => true],
                        ],
                    ]],
                ]],
            ],
        ];
    }
}
