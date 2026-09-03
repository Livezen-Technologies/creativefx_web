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
            // The browser title and the social card. PageController already
            // reads both — meta_title falling back to title, og_image falling
            // back to a default — but nothing here ever wrote them, so a page
            // could not carry an SEO title distinct from its heading, or its
            // own sharing image.
            'meta_title'       => json_encode($def['meta_title'] ?? $def['title'], JSON_UNESCAPED_UNICODE),
            'og_image'         => $def['og_image'] ?? null,
            'template'         => 'default',
            'is_home'          => 0,
            'status'           => 'published',
            'updated_at'       => $now,
        ];

        $existing = $pages->where('slug', $slug)->get()->getRowArray();

        if ($existing === null) {
            $pages->insert($data + ['created_at' => $now]);
        } else {
            $pages->where('slug', $slug)->update($data);
        }
        $pageId = (int) $pages->where('slug', $slug)->get()->getRowArray()['id'];

        // A page an administrator has edited is theirs. The reset below deletes
        // every section and block and writes them again, which is right for a
        // page nobody has touched — that is how this copy stays in version
        // control — and destroys the edit otherwise, silently, one deploy after
        // it was made. See the AddPageCustomFlag migration.
        if ((int) ($existing['is_custom'] ?? 0) === 1) {
            return;
        }

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
            'kalawana'      => $this->kalawana(),
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
                'modal'  => 'booking',
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
                    null, null, '/media/giantforests/Things-to-do-Giants-Forests-Hotel-1.jpg'
                ),
                ['key' => 'activities', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('While you are here'),
                        'items' => [
                            ['title' => $this->loc('Sinharaja tracking'),
                             'image' => '/media/giantforests/Sinharaja-Tracking-1.jpg',
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
                             'image' => '/media/giantforests/Natural-Bathing-1.jpg',
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

    /**
     * Kalawana as a destination — the reason a guest comes this far.
     *
     * The copy is reproduced exactly as the brief specifies it; the only
     * editorial decisions here are which of the hotel's own photographs carry
     * each section, and which sources to cite. Every cited URL was checked for
     * a 200 from a runner before it was written in: UNESCO's Sinharaja page
     * returns 403 to anything that is not a browser and the Forest Department's
     * site did not answer at all, so neither is linked. Citing a source that
     * does not load is worse than not citing one.
     */
    private function kalawana(): array
    {
        $tourism = 'https://www.srilanka.travel/sinharaja-forest-reserve';

        return [
            'title'      => $this->loc('Explore Kalawana'),
            'meta_title' => $this->loc('Discover Kalawana – Gateway to Sinharaja Rain Forest'),
            'meta'       => $this->loc('Plan your Kalawana stay: Sinharaja rainforest walks, waterfalls, birdwatching, tea-country villages, and travel tips for reaching the Kudawa entrance.'),
            'og_image'   => '/media/giantforests/Sinharaja-Tracking-2.jpg',
            'sections'   => [
                // 1. Hero
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    ['pagehero', [
                        'eyebrow'  => $this->loc('Ratnapura District, Sri Lanka'),
                        'title'    => $this->loc('Discover Kalawana'),
                        'subtitle' => $this->loc('Escape into Sri Lanka’s lush wet zone, where rainforest trails, waterfalls, tea-covered hills, and authentic village life meet.'),
                        'image'    => '/media/giantforests/Sinharaja-Tracking-2.jpg',
                        'button'   => $this->loc('Plan Your Stay'),
                        'url'      => 'accommodation',
                    ]],
                ]],

                // 2. Introduction
                ['key' => 'intro', 'type' => 'intro', 'blocks' => [
                    ['two_column', [
                        'title'     => $this->loc('Gateway to Sinharaja'),
                        'body'      => $this->loc('Kalawana is a peaceful nature destination in Sri Lanka’s Ratnapura District and a convenient route to the Kudawa entrance of Sinharaja Rain Forest Reserve. It is ideal for rainforest trekking, birdwatching, waterfall visits, and relaxed eco-travel. Sinharaja is a UNESCO World Heritage Site and one of Sri Lanka’s most important biodiversity areas.'),
                        'layout'    => 'text-left',
                        'image'     => '/media/giantforests/Sinharaja-Tracking-1.jpg',
                        'image_alt' => $this->loc('Dense rainforest interior in Sinharaja, layered green canopy and tall trunks'),
                        'link'      => ['label' => $this->loc('Sri Lanka Tourism'), 'url' => $tourism],
                    ]],
                ]],

                // 3. Things to do
                ['key' => 'things', 'type' => 'things', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Things to Do in Kalawana'),
                        'items' => [
                            [
                                'title'     => $this->loc('Explore Sinharaja Rain Forest'),
                                'text'      => $this->loc('Join a guided walk through dense rainforest, home to endemic birds, butterflies, reptiles, and plant life.'),
                                'image'     => '/media/giantforests/Hiking-Jungle-Tour-1-Giants-Forests-Hotel.jpg',
                                'image_alt' => $this->loc('A guided group walking a narrow trail through thick rainforest'),
                            ],
                            [
                                'title'     => $this->loc('Waterfall adventures'),
                                'text'      => $this->loc('Discover scenic waterfalls and natural pools around the Kalawana and Sinharaja region.'),
                                'image'     => '/media/giantforests/Bopath-Falls-Ratnapura.jpg',
                                'image_alt' => $this->loc('A wide waterfall falling into a rocky pool, with a rainbow in the spray'),
                            ],
                            [
                                'title'     => $this->loc('Birdwatching and wildlife'),
                                'text'      => $this->loc('Look out for the Sri Lanka blue magpie, giant squirrel, colourful butterflies, and rare rainforest species.'),
                                'image'     => '/media/giantforests/Things-to-do-Giants-Forests-Hotel-1.jpg',
                                'image_alt' => $this->loc('Rainforest wildlife near the hotel grounds'),
                            ],
                            [
                                'title'     => $this->loc('Village and tea-country experiences'),
                                'text'      => $this->loc('Enjoy rural landscapes, tea estates, local food, and the slower rhythm of village life.'),
                                'image'     => '/media/giantforests/Cycle-tour-1.jpg',
                                'image_alt' => $this->loc('Two cyclists on a lane between tea fields and wildflowers'),
                            ],
                            [
                                'title'     => $this->loc('Nature photography'),
                                'text'      => $this->loc('Capture misty forest views, rivers, tropical greenery, and waterfalls.'),
                                'image'     => '/media/giantforests/Sinharaja-Tracking-3.jpg',
                                'image_alt' => $this->loc('A bright orange bracket fungus on a wet rainforest branch'),
                            ],
                            // A sixth, added at the client's request: five cards
                            // left a hole in the bottom-right of a three-column
                            // grid. Boating rather than another waterfall, since
                            // the second card already covers falls and pools —
                            // and it is one of the hotel's own activities, so the
                            // photograph is theirs.
                            [
                                'title'     => $this->loc('Boat rides on the reservoir'),
                                'text'      => $this->loc('Head out on Kukuleganga Reservoir between forested banks, with birdlife along the shore and the hills beyond.'),
                                'image'     => '/media/giantforests/Boat-Tour-2-Giants-Forests-Hotel.jpg',
                                'image_alt' => $this->loc('A small boat on a calm river channel with forest along both banks'),
                            ],
                        ],
                    ]],
                ]],

                // 4. Plan your visit
                ['key' => 'plan', 'type' => 'plan', 'blocks' => [
                    ['richtext', [
                        'title' => $this->loc('Plan Your Visit'),
                        'text'  => $this->loc('The main Kudawa entrance to Sinharaja is reached via Kalawana. A local guide is required for entry to the reserve. Bring comfortable walking shoes, rain protection, drinking water, insect repellent, and a camera.'),
                        'link'  => ['label' => $this->loc('Visitor guidance'), 'url' => $tourism],
                    ]],
                ]],

                // 5. Best time to visit
                ['key' => 'when', 'type' => 'when', 'blocks' => [
                    ['richtext', [
                        'title' => $this->loc('Best Time to Visit'),
                        'text'  => $this->loc('Kalawana is in Sri Lanka’s wet zone, so visitors should expect occasional rain throughout the year. January to early March and August to September are often more practical periods for rainforest walks, although the forest is beautiful in every season.'),
                        'link'  => ['label' => $this->loc('Visitor guidance'), 'url' => $tourism],
                    ]],
                ]],

                // 6. Getting here
                ['key' => 'getting-here', 'type' => 'map', 'blocks' => [
                    ['map', [
                        'title' => $this->loc('Getting Here'),
                        'intro' => $this->loc('From Colombo, travel via the Southern Expressway toward Welipenna and continue inland through Kalawana. The journey to the Kudawa entrance generally takes about 4–5 hours by road.'),
                        'items' => [
                            ['region' => $this->loc('Kalawana'), 'detail' => $this->loc('Gateway to the Kudawa entrance of Sinharaja'),
                             'lat' => 6.5386, 'lon' => 80.4020, 'hq' => true],
                        ],
                    ]],
                ]],

                // 7. Call to action
                ['key' => 'cta', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'   => $this->loc('Start Your Kalawana Adventure'),
                        'text'    => $this->loc('Stay close to nature and experience the rainforest, waterfalls, and peaceful surroundings of Kalawana.'),
                        'button'  => $this->loc('Book Your Stay'),
                        'url'     => 'accommodation',
                        'modal'   => 'booking',
                        'button2' => $this->loc('Contact Us'),
                        'url2'    => 'contact',
                        'image'   => '/media/giantforests/Sinharaja-Tracking-1.jpg',
                    ]],
                ]],
            ],
        ];
    }
}
