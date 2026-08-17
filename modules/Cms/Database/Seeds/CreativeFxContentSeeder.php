<?php

namespace Modules\Cms\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Every CreativeFX CMS page: the home page, Our Story, the Services overview,
 * the six service pages under `services/<slug>`, and Contact.
 *
 * Idempotent by design — the page row is upserted by slug and its sections and
 * blocks are cleared and re-seeded, so re-running never duplicates a section.
 * That also means an editor's changes to *these* pages are overwritten on a
 * re-seed: the seeder owns the launch copy, and anything the client changes in
 * Admin -> Pages should be re-seeded only deliberately.
 *
 * Localization: copy is authored in English and stored as a locale map. Sinhala
 * and Tamil are completed by the content translation dictionary (see
 * content_locales()) where it has an entry, and otherwise fall back to English
 * through t_field() until a translator fills them in.
 *
 * Imagery: every path points at the generated placeholder art in
 * public/media/placeholders/ (scripts/gen-placeholders.mjs). Blocks test the
 * file exists before rendering it, so replacing one with real photography is a
 * media upload, not a code change.
 */
class CreativeFxContentSeeder extends Seeder
{
    /** Generated placeholder artwork, replaced by the client's own media later. */
    private const ART = '/media/placeholders/';

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($this->pages() as $slug => $def) {
            $this->seedPage($slug, $def, $now);
        }
    }

    /**
     * Build a locale-map, dropping null locales. Any locale not supplied here is
     * completed from the content translation dictionary, so seeded copy lands as
     * translated as the dictionary allows and falls back to English otherwise.
     */
    private function loc(string $en, ?string $si = null, ?string $ta = null): array
    {
        helper('norlanka');

        return content_locales(
            array_filter(['en' => $en, 'si' => $si, 'ta' => $ta], static fn ($v) => $v !== null)
        );
    }

    private function seedPage(string $slug, array $def, string $now): void
    {
        $json   = static fn (array $map): string => json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $isHome = ! empty($def['home']);
        $pages  = $this->db->table('pages');

        $data = [
            'slug'             => $slug,
            'title'            => $json($def['title']),
            'meta_title'       => $json($def['metaTitle'] ?? $def['title']),
            'meta_description' => $json($def['meta'] ?? $def['title']),
            'template'         => $def['template'] ?? 'default',
            'is_home'          => $isHome ? 1 : 0,
            'status'           => 'published',
            'updated_at'       => $now,
        ];

        if ($pages->where('slug', $slug)->get()->getRowArray() === null) {
            $pages->insert($data + ['created_at' => $now]);
        } else {
            $pages->where('slug', $slug)->update($data);
        }
        $pageId = (int) $pages->where('slug', $slug)->get()->getRowArray()['id'];

        // findHome() takes the first published page flagged is_home, so a second
        // one decides the home page by row order. The Norlanka install still
        // carries its own `home` row: demote anything else claiming the flag.
        if ($isHome) {
            $this->db->table('pages')
                ->where('id !=', $pageId)
                ->where('is_home', 1)
                ->update(['is_home' => 0, 'updated_at' => $now]);
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
                    'content'    => $json($content),
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
            ''                                  => $this->home(),
            'our-story'                         => $this->ourStory(),
            'services'                          => $this->servicesOverview(),
            'services/photography-videography'  => $this->photographyVideography(),
            'services/podcast-studio'           => $this->podcastStudio(),
            'services/live-streaming'           => $this->liveStreaming(),
            'services/gear-renting'             => $this->gearRenting(),
            'services/social-media-advertising' => $this->socialMediaAdvertising(),
            'services/digital-marketing'        => $this->digitalMarketing(),
            'contact'                           => $this->contact(),
        ];
    }

    // -----------------------------------------------------------------
    // Shared section builders
    // -----------------------------------------------------------------

    /**
     * Page hero. No launch films are shot yet, so only the poster is seeded: the
     * hero renders its animated aurora until someone adds a background clip in
     * Admin, at which point the poster is the still it paints while the film
     * buffers.
     */
    private function hero(array $eyebrow, array $title, array $subtitle, string $poster): array
    {
        return ['key' => 'hero', 'type' => 'hero', 'blocks' => [
            ['pagehero', [
                'eyebrow'  => $eyebrow,
                'title'    => $title,
                'subtitle' => $subtitle,
                'poster'   => self::ART . $poster,
            ]],
        ]];
    }

    /**
     * "What We Offer" on a service page.
     *
     * @param list<array{0:array,1:array}> $items title/text pairs
     */
    private function whatWeOffer(array $title, array $intro, array $items): array
    {
        $offer = [];
        foreach ($items as [$name, $text]) {
            $offer[] = ['title' => $name, 'text' => $text];
        }

        return ['key' => 'offer', 'type' => 'features', 'blocks' => [
            ['service_features', [
                'eyebrow' => $this->loc('What we offer'),
                'title'   => $title,
                'intro'   => $intro,
                'columns' => 3,
                'items'   => $offer,
            ]],
        ]];
    }

    /**
     * @param list<array{0:array,1:array}> $steps title/text pairs
     */
    private function processSteps(array $title, array $steps): array
    {
        $items = [];
        foreach ($steps as [$name, $text]) {
            $items[] = ['title' => $name, 'text' => $text];
        }

        return ['key' => 'process', 'type' => 'process', 'blocks' => [
            ['process_steps', ['title' => $title, 'items' => $items]],
        ]];
    }

    /**
     * Pricing tiers. Every tier ends at the quote flow — nothing is sold on the
     * site — and the highlighted one carries the ribbon.
     *
     * @param list<array{name:array,price:string,currency?:string,period?:array,summary:array,features:list<array>,featured?:bool,cta?:array,ribbon?:array}> $tiers
     */
    private function packages(array $title, array $intro, array $tiers): array
    {
        $items = [];
        foreach ($tiers as $tier) {
            $item = [
                'name'     => $tier['name'],
                'price'    => $tier['price'],
                'currency' => $tier['currency'] ?? 'LKR',
                'period'   => $tier['period'] ?? [],
                'summary'  => $tier['summary'],
                'features' => $tier['features'],
                'url'      => 'quote',
                'cta'      => $tier['cta'] ?? $this->loc('Request a Quote'),
                'featured' => ! empty($tier['featured']),
            ];
            if (! empty($tier['featured'])) {
                $item['ribbon'] = $tier['ribbon'] ?? $this->loc('Most popular');
            }
            $items[] = $item;
        }

        return ['key' => 'packages', 'type' => 'packages', 'blocks' => [
            ['packages', [
                'eyebrow' => $this->loc('Packages'),
                'title'   => $title,
                'intro'   => $intro,
                'items'   => $items,
            ]],
        ]];
    }

    /**
     * @param list<array{0:array,1:array}> $items question/answer pairs
     */
    private function faqs(array $items): array
    {
        $rows = [];
        foreach ($items as [$question, $answer]) {
            $rows[] = ['question' => $question, 'answer' => $answer];
        }

        return ['key' => 'faq', 'type' => 'faq', 'blocks' => [
            ['faq', [
                'eyebrow' => $this->loc('Questions'),
                'title'   => $this->loc('Frequently asked questions'),
                'intro'   => $this->loc('The answers we give most often before a project starts. Anything not covered here, ask us directly.'),
                'items'   => $rows,
            ]],
        ]];
    }

    /** The closing banner every service page ends on. */
    private function serviceCta(array $text): array
    {
        return ['key' => 'cta', 'type' => 'cta', 'blocks' => [
            ['cta', [
                'title'  => $this->loc('Ready to start your project?'),
                'text'   => $text,
                'button' => $this->loc('Request a Quote'),
                'url'    => 'quote',
            ]],
        ]];
    }

    // -----------------------------------------------------------------
    // Home
    // -----------------------------------------------------------------

    private function home(): array
    {
        return [
            'home'      => true,
            'template'  => 'home',
            'title'     => $this->loc('CreativeFX'),
            'metaTitle' => $this->loc('CreativeFX — Photography, Video, Podcast & Digital Marketing in Colombo'),
            'meta'      => $this->loc('A Colombo creative media team: photography and videography, a podcast studio, live streaming, equipment rental, social media advertising and digital marketing.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Creative media & digital services · Colombo'),
                    $this->loc('We Create Stories That Make Brands Stand Out.'),
                    $this->loc('Photography, videography, podcast production, live streaming, advertising, and digital marketing — all under one creative team.'),
                    'hero-home.svg'
                ),

                // Services preview — all six, each card linking to its own page.
                ['key' => 'services', 'type' => 'services', 'blocks' => [
                    ['services_grid', [
                        'eyebrow' => $this->loc('What we do'),
                        'title'   => $this->loc('Six services, one production team'),
                        'intro'   => $this->loc('Book one of them or all six. They are built to work together — the shoot feeds the campaign, and the campaign tells the next shoot what to make.'),
                        'items'   => [
                            [
                                'title' => $this->loc('Photography & Videography'),
                                'text'  => $this->loc('Corporate, product, event, fashion and campaign work, graded and delivered in every format you publish in.'),
                                'image' => self::ART . 'service-photography-videography.svg',
                                'icon'  => 'camera',
                                'url'   => 'services/photography-videography',
                                'cta'   => $this->loc('Explore the service'),
                            ],
                            [
                                'title' => $this->loc('Podcast Studio'),
                                'text'  => $this->loc('A Colombo studio booked by the hour: multi-camera recording, editing, branding and short-form clips from every session.'),
                                'image' => self::ART . 'service-podcast-studio.svg',
                                'icon'  => 'mic',
                                'url'   => 'services/podcast-studio',
                                'cta'   => $this->loc('Explore the service'),
                            ],
                            [
                                'title' => $this->loc('Live Streaming'),
                                'text'  => $this->loc('Switched multi-camera broadcasts to YouTube, Facebook, Instagram and LinkedIn, with live graphics and a monitored connection.'),
                                'image' => self::ART . 'service-live-streaming.svg',
                                'icon'  => 'broadcast',
                                'url'   => 'services/live-streaming',
                                'cta'   => $this->loc('Explore the service'),
                            ],
                            [
                                'title' => $this->loc('Gear Renting'),
                                'text'  => $this->loc('Cameras, lenses, lighting, audio, tripods, gimbals and drones on daily and weekly LKR rates, tested before every collection.'),
                                'image' => self::ART . 'service-gear-renting.svg',
                                'icon'  => 'box',
                                'url'   => 'services/gear-renting',
                                'cta'   => $this->loc('Explore the service'),
                            ],
                            [
                                'title' => $this->loc('Social Media Advertising'),
                                'text'  => $this->loc('Paid campaigns on Meta, TikTok and YouTube, built on creative we produce and optimised against the cost per result.'),
                                'image' => self::ART . 'service-social-media-advertising.svg',
                                'icon'  => 'megaphone',
                                'url'   => 'services/social-media-advertising',
                                'cta'   => $this->loc('Explore the service'),
                            ],
                            [
                                'title' => $this->loc('Digital Marketing'),
                                'text'  => $this->loc('SEO, content, Google Ads, email and analytics working to one plan, so the traffic you pay for turns into enquiries you can count.'),
                                'image' => self::ART . 'service-digital-marketing.svg',
                                'icon'  => 'chart',
                                'url'   => 'services/digital-marketing',
                                'cta'   => $this->loc('Explore the service'),
                            ],
                        ],
                    ]],
                ]],

                // Featured work teaser. The captions name the same projects the
                // Portfolio module seeds, so the two never disagree.
                ['key' => 'work', 'type' => 'portfolio', 'blocks' => [
                    ['gallery', [
                        'eyebrow' => $this->loc('Selected work'),
                        'title'   => $this->loc('Recent projects'),
                        'intro'   => $this->loc('Films, photography, podcasts and campaigns delivered for clients across Sri Lanka.'),
                        'items'   => [
                            ['src' => self::ART . 'project-01.svg', 'caption' => $this->loc('Highland Harvest — an origin film for Ceylon Tea Exports')],
                            ['src' => self::ART . 'project-02.svg', 'caption' => $this->loc('Festive season campaign for Colombo City Centre')],
                            ['src' => self::ART . 'project-03.svg', 'caption' => $this->loc('Suite Stories — property photography for Kandy Heritage Hotels')],
                            ['src' => self::ART . 'project-04.svg', 'caption' => $this->loc('Made in Ceylon — a twelve-episode studio season')],
                            ['src' => self::ART . 'project-05.svg', 'caption' => $this->loc('Three-stage live stream for the Lanka Fintech Summit')],
                            ['src' => self::ART . 'project-06.svg', 'caption' => $this->loc('Couture lookbook for Araliya Bridal')],
                        ],
                    ]],
                    ['cta', [
                        'title'  => $this->loc('See the work in full'),
                        'text'   => $this->loc('Every case study carries the brief, the approach and the numbers the work delivered.'),
                        'button' => $this->loc('Explore Our Work'),
                        'url'    => 'portfolio',
                    ]],
                ]],

                ['key' => 'why', 'type' => 'features', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Why choose CreativeFX'),
                        'intro' => $this->loc('Eight reasons clients keep the whole project with one team instead of splitting it across three suppliers.'),
                        'items' => [
                            ['title' => $this->loc('Professional Creative Team'),
                             'text'  => $this->loc('Directors, camera operators, editors, designers and marketers working from the same brief, instead of handing it down a chain.')],
                            ['title' => $this->loc('High-End Production Equipment'),
                             'text'  => $this->loc('Cinema cameras, prime and zoom glass, LED lighting, wireless audio, gimbals and drones — maintained and tested before every job.')],
                            ['title' => $this->loc('Studio Facilities'),
                             'text'  => $this->loc('A Colombo studio with a podcast set, a lighting grid, edit suites and a control room, so a shoot never depends on finding a location.')],
                            ['title' => $this->loc('Fast Turnaround'),
                             'text'  => $this->loc('Event galleries within 48 hours, social cuts within three working days, and a delivery date written into every quote.')],
                            ['title' => $this->loc('Creative Strategy'),
                             'text'  => $this->loc('Every project starts with what the work has to achieve — the channel, the audience and the message — before a single frame is planned.')],
                            ['title' => $this->loc('End-to-End Production'),
                             'text'  => $this->loc('Concept, script, shoot, edit, grade, sound and delivery in one place, so nothing is lost in the gaps between suppliers.')],
                            ['title' => $this->loc('Social Media Expertise'),
                             'text'  => $this->loc('Creative built for the feed it runs in, and campaigns on Meta, TikTok and YouTube managed against the cost per result.')],
                            ['title' => $this->loc('Digital Marketing Experience'),
                             'text'  => $this->loc('SEO, Google Ads, content and analytics run by the same team that makes the assets, so the media and the message match.')],
                        ],
                    ]],
                ]],

                $this->processSteps($this->loc('Our creative process'), [
                    [$this->loc('Discover'), $this->loc('We start with the goal, the audience and the deadline, and agree what success looks like in numbers.')],
                    [$this->loc('Plan'), $this->loc('Concept, script, shot list, locations, crew and schedule, priced in one document you sign off.')],
                    [$this->loc('Create'), $this->loc('Art direction, sets, casting and the creative work that has to exist before anyone calls action.')],
                    [$this->loc('Produce'), $this->loc('The shoot itself — crew, kit, sound and direction, with a producer keeping the day on schedule.')],
                    [$this->loc('Deliver'), $this->loc('Edit, grade, sound mix and versioning, delivered in the sizes and formats each channel needs.')],
                    [$this->loc('Grow'), $this->loc('Publishing, paid distribution and reporting, so the work keeps earning after it goes live.')],
                ]),

                ['key' => 'stats', 'type' => 'metrics', 'blocks' => [
                    ['metrics', [
                        'title' => $this->loc('CreativeFX in numbers'),
                        'items' => [
                            ['value' => '640', 'suffix' => '+', 'label' => $this->loc('Projects completed')],
                            ['value' => '180', 'suffix' => '+', 'label' => $this->loc('Clients served')],
                            ['value' => '950', 'suffix' => '+', 'label' => $this->loc('Videos produced')],
                            ['value' => '410', 'suffix' => '+', 'label' => $this->loc('Photoshoots completed')],
                            ['value' => '120', 'suffix' => '+', 'label' => $this->loc('Live events covered')],
                            ['value' => '190', 'suffix' => '+', 'label' => $this->loc('Campaigns delivered')],
                        ],
                    ]],
                ]],

                ['key' => 'testimonials', 'type' => 'testimonials', 'blocks' => [
                    ['testimonials', [
                        'eyebrow' => $this->loc('Testimonials'),
                        'title'   => $this->loc('What clients say'),
                        'intro'   => $this->loc('Feedback from the teams we have worked with across Sri Lanka.'),
                        'items'   => [
                            [
                                'quote'   => $this->loc('They arrived with a plan for the whole day, not just a camera. The origin film has been the centrepiece of every buyer meeting since, and the six shorter cuts covered a year of social without another shoot.'),
                                'name'    => $this->loc('Dilani Perera'),
                                'role'    => $this->loc('Marketing Director'),
                                'company' => $this->loc('Ceylon Tea Exports'),
                                'rating'  => 5,
                            ],
                            [
                                'quote'   => $this->loc('We booked the studio for one episode and stayed for the season. The clips come back cut, captioned and sized for each platform, which is the part we could never keep up with ourselves.'),
                                'name'    => $this->loc('Ayesha Jayawardena'),
                                'role'    => $this->loc('Head of Content'),
                                'company' => $this->loc('Serendib Podcast Network'),
                                'rating'  => 5,
                            ],
                            [
                                'quote'   => $this->loc('Two days, three stages and not a dropped minute on the stream. Their team tested the connection at the venue a week before, which is why nothing surprised anyone on the day.'),
                                'name'    => $this->loc('Nuwan Rajapaksa'),
                                'role'    => $this->loc('Event Director'),
                                'company' => $this->loc('Lanka Fintech Summit'),
                                'rating'  => 5,
                            ],
                            [
                                'quote'   => $this->loc('The campaign ran on creative they produced themselves, so when a piece stopped working it was replaced the same week. Our cost per booking fell every month of the retainer.'),
                                'name'    => $this->loc('Roshan Fernando'),
                                'role'    => $this->loc('Centre Manager'),
                                'company' => $this->loc('Colombo City Centre'),
                                'rating'  => 5,
                            ],
                        ],
                    ]],
                    // Placeholder wordmarks: the client replaces these plates with
                    // the real logos they have permission to show.
                    ['logo_wall', [
                        'eyebrow'   => $this->loc('Clients'),
                        'title'     => $this->loc('Teams we have worked with'),
                        'grayscale' => true,
                        'items'     => [
                            ['name' => $this->loc('Ceylon Tea Exports'), 'logo' => self::ART . 'client-01.svg'],
                            ['name' => $this->loc('Colombo City Centre'), 'logo' => self::ART . 'client-02.svg'],
                            ['name' => $this->loc('Kandy Heritage Hotels'), 'logo' => self::ART . 'client-03.svg'],
                            ['name' => $this->loc('Serendib Podcast Network'), 'logo' => self::ART . 'client-04.svg'],
                            ['name' => $this->loc('Lanka Fintech Summit'), 'logo' => self::ART . 'client-05.svg'],
                            ['name' => $this->loc('Sapphire Logistics Lanka'), 'logo' => self::ART . 'client-06.svg'],
                            ['name' => $this->loc('Nexus Bank Ceylon'), 'logo' => self::ART . 'client-07.svg'],
                            ['name' => $this->loc('Villa Serene Bentota'), 'logo' => self::ART . 'client-08.svg'],
                        ],
                    ]],
                ]],

                ['key' => 'cta', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'  => $this->loc('Have a project in mind?'),
                        'text'   => $this->loc('Let us talk it through. Tell us the goal, the date and the budget you are working with, and we will come back with a scope and a price in LKR.'),
                        'button' => $this->loc('Request a Quote'),
                        'url'    => 'quote',
                    ]],
                ]],
            ],
        ];
    }

    // -----------------------------------------------------------------
    // Our Story
    // -----------------------------------------------------------------

    private function ourStory(): array
    {
        return [
            'title'     => $this->loc('Our Story'),
            'metaTitle' => $this->loc('Our Story — CreativeFX, Colombo'),
            'meta'      => $this->loc('How CreativeFX grew from a two-person photography team into a Colombo creative production and digital marketing studio.'),
            'sections'  => [
                $this->hero(
                    $this->loc('About CreativeFX'),
                    $this->loc('Our Story'),
                    $this->loc('A creative media team from Colombo — cameras, a podcast studio, a streaming control room and a marketing desk under one roof.'),
                    'hero-our-story.svg'
                ),

                ['key' => 'who-we-are', 'type' => 'content', 'blocks' => [
                    ['two_column', [
                        'eyebrow' => $this->loc('Who we are'),
                        'title'   => $this->loc('Who we are'),
                        'body'    => $this->loc('<p>CreativeFX is a creative media and digital services company based in Colombo. We photograph, film, record, stream, rent out equipment and run digital campaigns — with our own crew, our own studio and our own kit.</p><p>Most clients come to us tired of coordinating a photographer, a video crew, an editor and an agency who never speak to each other. One brief, one production team, one set of files that arrive when we said they would.</p>'),
                        'items'   => [
                            $this->loc('Colombo studio · crews working island-wide'),
                            $this->loc('Six services under one production team'),
                            $this->loc('Every project scoped, quoted and delivered in LKR'),
                        ],
                    ]],
                ]],

                ['key' => 'how-we-started', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('How we started'),
                        'title'   => $this->loc('One camera, rented studio hours and a waiting list'),
                        'text'    => $this->loc('<p>CreativeFX started in 2018 with one camera body, two lenses and studio hours we rented by the afternoon. The first jobs were product shoots for online sellers who needed photographs the same week, and the work came back because the files did too.</p><p>Video followed, then editing, then the equipment other crews kept asking to borrow. By the time we signed the lease on our own space, we were already turning down work we had no room for.</p>'),
                    ]],
                ]],

                ['key' => 'what-we-believe', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('What we believe'),
                        'title'   => $this->loc('Creative work is a business decision'),
                        'text'    => $this->loc('<p>A film is not finished when it looks good. It is finished when it does the job it was made for, which is why every project here starts with a goal and ends with a report rather than a download link.</p><p>We would rather tell a client that a smaller shoot will serve them better than sell a production they do not need. It costs us a line on the invoice and it keeps the relationship for years.</p>'),
                    ]],
                ]],

                ['key' => 'journey', 'type' => 'timeline', 'blocks' => [
                    ['timeline', [
                        'title' => $this->loc('How we got here'),
                        'items' => [
                            ['year' => $this->loc('2018'), 'title' => $this->loc('Founded'),
                             'text' => $this->loc('CreativeFX opens in Colombo as a two-person photography and video team, working out of rented studio time.')],
                            ['year' => $this->loc('2019'), 'title' => $this->loc('First major project'),
                             'text' => $this->loc('A national retail campaign — twelve shoot days, a print set and a broadcast cut — puts the studio on larger clients’ lists.')],
                            ['year' => $this->loc('2021'), 'title' => $this->loc('Studio expansion'),
                             'text' => $this->loc('We take our own space in Colombo and build the podcast set, the lighting grid and two edit suites.')],
                            ['year' => $this->loc('2022'), 'title' => $this->loc('Production growth'),
                             'text' => $this->loc('The crew doubles, a second camera package and a live production kit arrive, and multi-camera events become regular work.')],
                            ['year' => $this->loc('2024'), 'title' => $this->loc('Digital marketing expansion'),
                             'text' => $this->loc('A paid media and SEO desk joins the studio, so campaigns can be produced and run by the same team.')],
                            ['year' => $this->loc('2026'), 'title' => $this->loc('Where we are now'),
                             'text' => $this->loc('Six services, a full-time crew, a rental catalogue that goes out island-wide, and clients from Jaffna to Galle.')],
                        ],
                    ]],
                ]],

                ['key' => 'mission-vision', 'type' => 'statement', 'blocks' => [
                    ['statement', [
                        'items' => [
                            ['title' => $this->loc('Our Mission'),
                             'text' => $this->loc('To create meaningful visual experiences that help businesses communicate, connect, and grow.')],
                            ['title' => $this->loc('Our Vision'),
                             'text' => $this->loc('To become a leading creative production and digital marketing partner for ambitious brands.')],
                        ],
                    ]],
                ]],

                ['key' => 'values', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('What we value'),
                        'items' => [
                            ['title' => $this->loc('Creativity'), 'icon' => 'bulb', 'image' => self::ART . 'project-07.svg',
                             'text' => $this->loc('Every brief gets an idea before it gets a schedule. We would rather spend an hour arguing about the concept than a day fixing a shoot that had none.')],
                            ['title' => $this->loc('Quality'), 'icon' => 'shield', 'image' => self::ART . 'project-08.svg',
                             'text' => $this->loc('Colour, sound and finishing are checked against a reference on a calibrated screen before anything leaves the studio.')],
                            ['title' => $this->loc('Innovation'), 'icon' => 'leaf', 'image' => self::ART . 'project-09.svg',
                             'text' => $this->loc('New formats, platforms and kit are tested on our own channels first, so clients get them working rather than experimental.')],
                            ['title' => $this->loc('Reliability'), 'icon' => 'handshake', 'image' => self::ART . 'project-10.svg',
                             'text' => $this->loc('A date in a quote is a commitment. If a delivery is at risk we say so early, not on the deadline.')],
                            ['title' => $this->loc('Collaboration'), 'icon' => 'users', 'image' => self::ART . 'project-11.svg',
                             'text' => $this->loc('Your team knows the business better than we ever will. The best work here has always come from planning it together.')],
                            ['title' => $this->loc('Customer Focus'), 'icon' => 'heart', 'image' => self::ART . 'project-12.svg',
                             'text' => $this->loc('We measure a project by what it did for the client — bookings, enquiries, applications — not by how it looked in our reel.')],
                        ],
                    ]],
                ]],

                ['key' => 'team', 'type' => 'team', 'blocks' => [
                    ['team_grid', [
                        'eyebrow' => $this->loc('The team'),
                        'title'   => $this->loc('The people behind the work'),
                        'intro'   => $this->loc('A core crew of directors, shooters, editors and marketers, with specialists brought in project by project.'),
                        'items'   => [
                            ['name' => $this->loc('Chathura Dissanayake'), 'role' => $this->loc('Founder & Creative Director'),
                             'photo' => self::ART . 'team-01.svg',
                             'bio'  => $this->loc('Started CreativeFX in 2018 and still directs the studio’s largest campaigns. Sets the creative brief on every project that comes through the door.')],
                            ['name' => $this->loc('Nadeesha Silva'), 'role' => $this->loc('Head of Production'),
                             'photo' => self::ART . 'team-02.svg',
                             'bio'  => $this->loc('Runs the schedule, the crew and the budget on every shoot. If a project lands on time, it is usually because she planned it twice.')],
                            ['name' => $this->loc('Ruwan Wickramasinghe'), 'role' => $this->loc('Director of Photography'),
                             'photo' => self::ART . 'team-03.svg',
                             'bio'  => $this->loc('Lights and shoots the studio’s films, from single-camera interviews to multi-camera events, and owns the look of everything we deliver.')],
                            ['name' => $this->loc('Ishara Gunasekara'), 'role' => $this->loc('Lead Photographer'),
                             'photo' => self::ART . 'team-04.svg',
                             'bio'  => $this->loc('Corporate, product and event photography, and the studio’s standard for colour and retouching.')],
                            ['name' => $this->loc('Tharindu Bandara'), 'role' => $this->loc('Senior Editor & Colourist'),
                             'photo' => self::ART . 'team-05.svg',
                             'bio'  => $this->loc('Cuts, grades and finishes the long-form work, and built the clip pipeline that turns one episode into nine.')],
                            ['name' => $this->loc('Menaka Rathnayake'), 'role' => $this->loc('Head of Digital Marketing'),
                             'photo' => self::ART . 'team-06.svg',
                             'bio'  => $this->loc('Plans and runs the paid campaigns, the search work and the reporting that tells clients what the creative actually did.')],
                        ],
                    ]],
                ]],

                ['key' => 'cta', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'  => $this->loc('Let’s work on something together'),
                        'text'   => $this->loc('Tell us what you are planning — a shoot, a season, a launch or a campaign — and we will come back with a scope, a timeline and a price.'),
                        'button' => $this->loc('Request a Quote'),
                        'url'    => 'quote',
                    ]],
                ]],
            ],
        ];
    }

    // -----------------------------------------------------------------
    // Services overview
    // -----------------------------------------------------------------

    private function servicesOverview(): array
    {
        return [
            'title'     => $this->loc('Services'),
            'metaTitle' => $this->loc('Services — Creative Production & Digital Marketing | CreativeFX'),
            'meta'      => $this->loc('Photography and videography, podcast production, live streaming, equipment rental, social media advertising and digital marketing from one Colombo team.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Services'),
                    $this->loc('Creative Production. Digital Growth. One Partner.'),
                    $this->loc('Six services that cover everything from the first frame to the final campaign report.'),
                    'hero-services.svg'
                ),

                ['key' => 'services', 'type' => 'services', 'blocks' => [
                    ['services_grid', [
                        'eyebrow' => $this->loc('What we do'),
                        'title'   => $this->loc('Everything we make, in one place'),
                        'intro'   => $this->loc('Each service stands on its own and every one of them is stronger next to the others — the shoot supplies the campaign, and the campaign tells the next shoot what to make.'),
                        'items'   => [
                            [
                                'title' => $this->loc('Photography & Videography'),
                                'text'  => $this->loc('01 — Corporate, product, event, fashion and campaign work, shot and finished in the studio: stills, films and every crop your channels need.'),
                                'image' => self::ART . 'service-photography-videography.svg',
                                'icon'  => 'camera',
                                'url'   => 'services/photography-videography',
                                'cta'   => $this->loc('View the service'),
                            ],
                            [
                                'title' => $this->loc('Podcast Studio'),
                                'text'  => $this->loc('02 — A Colombo studio booked by the hour for recording and filming your show, with the edit, the branding and the short-form clips included.'),
                                'image' => self::ART . 'service-podcast-studio.svg',
                                'icon'  => 'mic',
                                'url'   => 'services/podcast-studio',
                                'cta'   => $this->loc('View the service'),
                            ],
                            [
                                'title' => $this->loc('Live Streaming'),
                                'text'  => $this->loc('03 — Switched multi-camera broadcasts of conferences, launches, weddings and concerts, with live graphics and a connection we monitor throughout.'),
                                'image' => self::ART . 'service-live-streaming.svg',
                                'icon'  => 'broadcast',
                                'url'   => 'services/live-streaming',
                                'cta'   => $this->loc('View the service'),
                            ],
                            [
                                'title' => $this->loc('Gear Renting'),
                                'text'  => $this->loc('04 — Cameras, lenses, lighting, audio, tripods, gimbals, drones and studio kit on daily and weekly LKR rates, with an operator if you need one.'),
                                'image' => self::ART . 'service-gear-renting.svg',
                                'icon'  => 'box',
                                'url'   => 'services/gear-renting',
                                'cta'   => $this->loc('View the service'),
                            ],
                            [
                                'title' => $this->loc('Social Media Advertising'),
                                'text'  => $this->loc('05 — Paid campaigns on Meta, TikTok and YouTube, built on creative we produce and optimised every week against the cost per result.'),
                                'image' => self::ART . 'service-social-media-advertising.svg',
                                'icon'  => 'megaphone',
                                'url'   => 'services/social-media-advertising',
                                'cta'   => $this->loc('View the service'),
                            ],
                            [
                                'title' => $this->loc('Digital Marketing'),
                                'text'  => $this->loc('06 — SEO, content, Google Ads, email and the analytics underneath them, working to one plan and one monthly report.'),
                                'image' => self::ART . 'service-digital-marketing.svg',
                                'icon'  => 'chart',
                                'url'   => 'services/digital-marketing',
                                'cta'   => $this->loc('View the service'),
                            ],
                        ],
                    ]],
                ]],

                $this->processSteps($this->loc('How we work with you'), [
                    [$this->loc('Discover'), $this->loc('A call or a studio visit to understand the business, the audience and what the work has to move.')],
                    [$this->loc('Plan'), $this->loc('A written scope: deliverables, crew, locations, dates and a price in LKR, with nothing hidden in a footnote.')],
                    [$this->loc('Create'), $this->loc('Concept, script, shot list and art direction, signed off before the first shoot day is booked.')],
                    [$this->loc('Produce'), $this->loc('The shoot, the recording or the broadcast, run by a producer whose job is the schedule.')],
                    [$this->loc('Deliver'), $this->loc('Edit, grade, sound and versioning, delivered to a shared drive in every format your channels take.')],
                    [$this->loc('Grow'), $this->loc('Distribution, paid support and a monthly report, for clients who want the work to keep working.')],
                ]),

                ['key' => 'cta', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'  => $this->loc('Not sure which service you need?'),
                        'text'   => $this->loc('Describe the project in the quote form and we will tell you what it takes — or tell you it takes less than you thought.'),
                        'button' => $this->loc('Request a Quote'),
                        'url'    => 'quote',
                    ]],
                ]],
            ],
        ];
    }

    // -----------------------------------------------------------------
    // Service pages
    // -----------------------------------------------------------------

    private function photographyVideography(): array
    {
        return [
            'title'     => $this->loc('Photography & Videography'),
            'metaTitle' => $this->loc('Photography & Videography in Colombo — CreativeFX'),
            'meta'      => $this->loc('Corporate, product, event, fashion and campaign photography and video, shot and finished by one Colombo studio.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Services'),
                    $this->loc('Photography & Videography'),
                    $this->loc('Corporate, product, event, fashion and campaign work — shot, edited, graded and delivered in every format you publish in.'),
                    'service-hero-photography-videography.svg'
                ),

                ['key' => 'overview', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Overview'),
                        'title'   => $this->loc('One shoot, every format you publish in'),
                        'text'    => $this->loc('<p>We cover corporate portraits and workplace imagery, product and e-commerce photography, events, fashion and bridal, commercial films and full brand campaigns. The same team plans the shoot, lights it, shoots it and finishes it, so the stills and the video come back looking like they belong to the same brand.</p><p>Everything is delivered graded, retouched and versioned: full-resolution files for print, web-optimised sets for your site, and vertical crops for the feed. One shoot day, three channels, and no re-shoot to fix a crop.</p>'),
                    ]],
                ]],

                $this->whatWeOffer(
                    $this->loc('What we offer'),
                    $this->loc('The six ways the studio is booked most often.'),
                    [
                        [$this->loc('Corporate & Brand'), $this->loc('Leadership portraits, team and workplace imagery, and company films for websites, annual reports and recruitment.')],
                        [$this->loc('Product & E-commerce'), $this->loc('Studio product photography on white and in styled sets, cut out, colour-matched and sized to your platform’s specification.')],
                        [$this->loc('Events & Conferences'), $this->loc('Coverage from registration to the closing address, with a same-week gallery and a highlight film for the sponsors.')],
                        [$this->loc('Fashion & Bridal'), $this->loc('Lookbooks, campaign imagery and wedding films, shot in the studio or on location with a full lighting package.')],
                        [$this->loc('Commercial Films'), $this->loc('Concept, script, casting, shoot and post for television, YouTube and paid social, cut to every length the media plan needs.')],
                        [$this->loc('Aerial & Drone'), $this->loc('Drone coverage for property, resorts, construction and events, flown with the approvals each site needs and graded to match the ground footage.')],
                    ]
                ),

                $this->processSteps($this->loc('How a shoot runs'), [
                    [$this->loc('Consultation'), $this->loc('We talk through the goal, the deliverables and where the images will run, then quote against that rather than a day rate.')],
                    [$this->loc('Planning'), $this->loc('Shot list, locations, permits, casting, styling and a call sheet everyone has before the shoot day.')],
                    [$this->loc('Production'), $this->loc('The shoot itself, with a lighting package, a producer on the day and every card backed up twice before we leave.')],
                    [$this->loc('Editing'), $this->loc('Selects, retouching, edit, colour grade and sound, with a first cut for review inside the agreed window.')],
                    [$this->loc('Review'), $this->loc('One round of feedback is included on every project, and a second round on campaign work.')],
                    [$this->loc('Delivery'), $this->loc('Final files on a shared drive: print resolution, web-optimised sets and the vertical crops your channels need.')],
                ]),

                $this->packages(
                    $this->loc('Photography & video packages'),
                    $this->loc('Starting prices for the most common bookings in Colombo. Travel outside the Western Province, casting, styling and location fees are quoted separately.'),
                    [
                        [
                            'name'     => $this->loc('Starter'),
                            'price'    => '45,000',
                            'period'   => $this->loc('half day'),
                            'summary'  => $this->loc('A half-day shoot for a single deliverable.'),
                            'features' => [
                                $this->loc('Up to 4 hours of shooting'),
                                $this->loc('One photographer or one camera operator'),
                                $this->loc('40 edited images or one 60-second film'),
                                $this->loc('Standard retouching and colour grade'),
                                $this->loc('Delivery within 5 working days'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Professional'),
                            'price'    => '120,000',
                            'period'   => $this->loc('full day'),
                            'featured' => true,
                            'summary'  => $this->loc('A full day covering stills and video together.'),
                            'features' => [
                                $this->loc('Up to 9 hours of shooting'),
                                $this->loc('Two-person crew with a lighting package'),
                                $this->loc('100 edited images plus one 2-minute film'),
                                $this->loc('Three vertical social cuts'),
                                $this->loc('Two rounds of revisions'),
                                $this->loc('Delivery within 7 working days'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Premium'),
                            'price'    => '285,000',
                            'period'   => $this->loc('two days'),
                            'summary'  => $this->loc('A campaign shot across two days with a full crew.'),
                            'features' => [
                                $this->loc('Two shoot days with a crew of four'),
                                $this->loc('Concept, art direction and styling'),
                                $this->loc('Up to 250 edited images'),
                                $this->loc('One 3-minute film plus six cut-downs'),
                                $this->loc('Drone and gimbal coverage included'),
                                $this->loc('Priority delivery within 10 working days'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Custom'),
                            'price'    => 'Custom',
                            'currency' => '',
                            'cta'      => $this->loc('Talk to us'),
                            'summary'  => $this->loc('Multi-location, multi-day or retainer work.'),
                            'features' => [
                                $this->loc('Scoped against your brief and schedule'),
                                $this->loc('Island-wide crews and overseas travel'),
                                $this->loc('Monthly content retainers'),
                                $this->loc('Usage and licensing written into the quote'),
                            ],
                        ],
                    ]
                ),

                $this->faqs([
                    [$this->loc('How far in advance should we book a shoot?'),
                     $this->loc('Two to three weeks is comfortable for a studio or corporate shoot, and four to six weeks for a campaign that needs casting, styling or permits. We do take short-notice work whenever the crew and the kit are free, so it is always worth asking.')],
                    [$this->loc('Do you travel outside Colombo?'),
                     $this->loc('Yes — we shoot island-wide. Travel inside the Western Province is included in the quoted price, and anywhere further is quoted as a fixed travel and accommodation line, so nothing appears on the invoice you have not already seen.')],
                    [$this->loc('Who owns the images and footage?'),
                     $this->loc('You do. Full commercial usage transfers to you on final payment, and we keep the raw files backed up for twelve months in case you need a re-edit. Work only appears in our own portfolio with your written agreement.')],
                    [$this->loc('How long does editing take?'),
                     $this->loc('Five working days for a half-day shoot, seven for a full day and ten for campaign work. Event galleries can be delivered within 48 hours when that is agreed at the quote stage.')],
                    [$this->loc('Can we get photos and video from the same shoot day?'),
                     $this->loc('Yes, and it is usually the cheapest way to work. The Professional package is built around it: the crew lights each setup once and captures stills and motion before moving on.')],
                ]),

                $this->serviceCta($this->loc('Tell us what you need photographed or filmed, when it has to be ready, and the budget you are working with.')),
            ],
        ];
    }

    private function podcastStudio(): array
    {
        return [
            'title'     => $this->loc('Podcast Studio'),
            'metaTitle' => $this->loc('Podcast Studio in Colombo — Recording, Video & Editing | CreativeFX'),
            'meta'      => $this->loc('A Colombo podcast studio for recording, filming and editing your show — booked by the hour, with short-form clips included.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Services'),
                    $this->loc('Podcast Studio'),
                    $this->loc('A Colombo studio booked by the hour — multi-camera recording, editing, branding and a set of short-form clips from every session.'),
                    'service-hero-podcast-studio.svg'
                ),

                ['key' => 'overview', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Overview'),
                        'title'   => $this->loc('Record it, film it, and leave with the clips'),
                        'text'    => $this->loc('<p>Our studio is built for talk: four broadcast microphones, treated acoustics, three cameras on the set and a control room next door. You arrive, we run the session, and you leave knowing the episode is already in the edit.</p><p>The room is the small part of it. What most shows need is what happens afterwards — the cut, the mix, the artwork, the captions and the short clips that carry the episode into everyone’s feed. That is part of every session package here, not sold back to you later.</p>'),
                    ]],
                ]],

                $this->whatWeOffer(
                    $this->loc('What we offer'),
                    $this->loc('Everything a show needs between the idea and the upload.'),
                    [
                        [$this->loc('Studio Recording'), $this->loc('Four broadcast microphones, treated acoustics and a monitored mix, recorded as separate tracks so every voice can be corrected on its own.')],
                        [$this->loc('Multi-Camera Video'), $this->loc('Three cameras on the set — wide, host and guest — cut in post, with matched lighting for every seat.')],
                        [$this->loc('Editing & Mixing'), $this->loc('Full episode edit, noise and level correction, music beds and a master that meets each platform’s loudness standard.')],
                        [$this->loc('Short-Form Clips'), $this->loc('Vertical clips pulled from every episode, captioned and sized for Instagram, TikTok and YouTube Shorts.')],
                        [$this->loc('Show Branding'), $this->loc('Cover art, an animated intro and outro, lower thirds and a template your episodes keep using.')],
                        [$this->loc('Remote Guests'), $this->loc('Remote guests recorded locally at their end and synced to the studio tracks, so a weak connection never lands in the final cut.')],
                    ]
                ),

                $this->processSteps($this->loc('How a session works'), [
                    [$this->loc('Consultation'), $this->loc('We talk through the format, the episode length and how often you plan to publish before booking a slot.')],
                    [$this->loc('Planning'), $this->loc('Run sheet, guest brief, seat plan and branding assets agreed ahead of the session.')],
                    [$this->loc('Production'), $this->loc('The recording itself: sound check, three-camera film and an engineer in the control room throughout.')],
                    [$this->loc('Editing'), $this->loc('Cut, mix, master, artwork and the short-form clips pulled from the episode.')],
                    [$this->loc('Review'), $this->loc('You approve the episode and the clip selection, with one round of changes included.')],
                    [$this->loc('Delivery'), $this->loc('Master audio, the full video edit and every clip, ready to upload to your host and your channels.')],
                ]),

                $this->packages(
                    $this->loc('Studio packages'),
                    $this->loc('Studio time, production and delivery in one price. Extra guests, live audiences and additional clips are quoted on top.'),
                    [
                        [
                            'name'     => $this->loc('Starter'),
                            'price'    => '15,000',
                            'period'   => $this->loc('per session'),
                            'summary'  => $this->loc('A two-hour audio-only recording for a single episode.'),
                            'features' => [
                                $this->loc('2 hours of studio time'),
                                $this->loc('Up to two microphones'),
                                $this->loc('Audio edit, mix and master'),
                                $this->loc('One episode delivered in 3 working days'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Professional'),
                            'price'    => '38,000',
                            'period'   => $this->loc('per episode'),
                            'featured' => true,
                            'summary'  => $this->loc('A filmed episode with the clips that carry it.'),
                            'features' => [
                                $this->loc('3 hours of studio time'),
                                $this->loc('Up to four microphones'),
                                $this->loc('Three-camera video edit'),
                                $this->loc('Audio master plus the full video episode'),
                                $this->loc('Six vertical clips, captioned'),
                                $this->loc('Delivery in 5 working days'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Premium'),
                            'price'    => '145,000',
                            'period'   => $this->loc('per month'),
                            'summary'  => $this->loc('A four-episode month, produced end to end.'),
                            'features' => [
                                $this->loc('Four filmed episodes a month'),
                                $this->loc('Nine vertical clips per episode'),
                                $this->loc('Cover art and an animated intro'),
                                $this->loc('Thumbnails and episode descriptions'),
                                $this->loc('Priority studio slots'),
                                $this->loc('Monthly performance summary'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Custom'),
                            'price'    => 'Custom',
                            'currency' => '',
                            'cta'      => $this->loc('Talk to us'),
                            'summary'  => $this->loc('Seasons, live audiences and branded series.'),
                            'features' => [
                                $this->loc('Full-season production deals'),
                                $this->loc('Live audience and multi-guest setups'),
                                $this->loc('Branded and sponsored series'),
                                $this->loc('Distribution and paid promotion'),
                            ],
                        ],
                    ]
                ),

                $this->faqs([
                    [$this->loc('How long does a recording session take?'),
                     $this->loc('Most hour-long episodes are booked as a three-hour session: thirty minutes to set up and sound check, the recording itself, and time to pick up anything you want to record again before you leave.')],
                    [$this->loc('Do we need to bring anything?'),
                     $this->loc('Nothing technical. Bring your questions, your guests and any branding you already have. If the show has no artwork yet, the Premium package includes it.')],
                    [$this->loc('Can you record a guest who is not in Colombo?'),
                     $this->loc('Yes. Remote guests are recorded locally at their end and the file is synced to the studio tracks, so the audio in the final cut is studio quality even if the call itself drops.')],
                    [$this->loc('Do you publish the episodes for us?'),
                     $this->loc('We deliver everything ready to upload, and we can publish to your host and channels as part of a monthly package. The show, the feed and the audience always stay yours.')],
                    [$this->loc('How many clips do we get?'),
                     $this->loc('Six on the Professional package and nine per episode on Premium, each captioned and cut vertically. If a particular moment matters to you, say so during the session and we will build a clip around it.')],
                ]),

                $this->serviceCta($this->loc('Tell us about the show — the format, the length and how often you want to publish — and we will put a session plan together.')),
            ],
        ];
    }

    private function liveStreaming(): array
    {
        return [
            'title'     => $this->loc('Live Streaming'),
            'metaTitle' => $this->loc('Live Streaming & Event Broadcasts in Sri Lanka — CreativeFX'),
            'meta'      => $this->loc('Multi-camera live streaming for conferences, launches, weddings and concerts, broadcast to YouTube, Facebook, Instagram and LinkedIn.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Services'),
                    $this->loc('Live Streaming'),
                    $this->loc('Switched multi-camera broadcasts to YouTube, Facebook, Instagram and LinkedIn, with live graphics and a connection we monitor from the first minute to the last.'),
                    'service-hero-live-streaming.svg'
                ),

                ['key' => 'overview', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Overview'),
                        'title'   => $this->loc('A broadcast, not a phone on a tripod'),
                        'text'    => $this->loc('<p>We stream conferences, webinars, product launches, weddings, concerts and ceremonies with a switched multi-camera feed, live graphics, audio taken from the room’s sound desk and an operator watching the encoder throughout.</p><p>Hybrid events run to the room and the stream at the same time, with a separate mix for each, so the online audience hears the speaker rather than the hall. The full recording is available the same day, ready to be cut into sessions and highlights.</p>'),
                    ]],
                ]],

                $this->whatWeOffer(
                    $this->loc('What we offer'),
                    $this->loc('The events we broadcast most, and how each one is covered.'),
                    [
                        [$this->loc('Conferences & Summits'), $this->loc('Multi-stage coverage with a switched feed per stage, presentation capture and speaker lower thirds.')],
                        [$this->loc('Product Launches'), $this->loc('A rehearsed run of show, branded graphics and a stream that starts on time because it was tested the day before.')],
                        [$this->loc('Webinars & Town Halls'), $this->loc('Remote presenters, screen sharing and moderated question sessions, streamed publicly or to a private link.')],
                        [$this->loc('Weddings & Ceremonies'), $this->loc('A discreet two or three-camera stream for family who cannot travel, with the recording kept for the couple.')],
                        [$this->loc('Concerts & Festivals'), $this->loc('Multi-camera coverage with a multitrack audio feed from the front-of-house desk rather than the camera microphones.')],
                        [$this->loc('Multi-Platform Delivery'), $this->loc('One production streamed simultaneously to YouTube, Facebook, Instagram, LinkedIn and your own site or event app.')],
                    ]
                ),

                $this->processSteps($this->loc('How a broadcast runs'), [
                    [$this->loc('Consultation'), $this->loc('We go through the schedule, the platforms, the venue and everyone who has to appear on screen.')],
                    [$this->loc('Planning'), $this->loc('A site visit, a connection test, a run of show and a graphics pack, all agreed before the day.')],
                    [$this->loc('Production'), $this->loc('Rig, rehearse, then broadcast — with an operator on the vision mixer and another on the encoder.')],
                    [$this->loc('Editing'), $this->loc('Session recordings, speaker cuts and highlights prepared from the multi-track capture.')],
                    [$this->loc('Review'), $this->loc('Every recording is checked against the schedule and trimmed to its real start and end.')],
                    [$this->loc('Delivery'), $this->loc('The full programme and every edited session delivered the same week, ready to publish or gate.')],
                ]),

                $this->packages(
                    $this->loc('Live streaming packages'),
                    $this->loc('Priced per event for the Western Province. Venues outside it, overnight rigs and additional stages are quoted with the booking.'),
                    [
                        [
                            'name'     => $this->loc('Starter'),
                            'price'    => '65,000',
                            'period'   => $this->loc('per event'),
                            'summary'  => $this->loc('A single-camera stream to one platform.'),
                            'features' => [
                                $this->loc('Up to 3 hours of live coverage'),
                                $this->loc('One camera and one operator'),
                                $this->loc('One streaming platform'),
                                $this->loc('Audio taken from the venue desk'),
                                $this->loc('Full recording delivered after the event'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Professional'),
                            'price'    => '165,000',
                            'period'   => $this->loc('per event'),
                            'featured' => true,
                            'summary'  => $this->loc('A switched three-camera broadcast for a full day.'),
                            'features' => [
                                $this->loc('Up to 8 hours of live coverage'),
                                $this->loc('Three cameras, switched live'),
                                $this->loc('Lower thirds and branded graphics'),
                                $this->loc('Two platforms simultaneously'),
                                $this->loc('Bonded internet with a backup line'),
                                $this->loc('Recording and session cuts delivered'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Premium'),
                            'price'    => '385,000',
                            'period'   => $this->loc('per event'),
                            'summary'  => $this->loc('A multi-stage or multi-day production.'),
                            'features' => [
                                $this->loc('Up to six cameras across two stages'),
                                $this->loc('Full graphics package and video playback'),
                                $this->loc('Four platforms plus a private feed'),
                                $this->loc('Redundant connection and power'),
                                $this->loc('On-site technical director'),
                                $this->loc('Highlight film within 48 hours'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Custom'),
                            'price'    => 'Custom',
                            'currency' => '',
                            'cta'      => $this->loc('Talk to us'),
                            'summary'  => $this->loc('Festivals, tours and recurring broadcasts.'),
                            'features' => [
                                $this->loc('Multi-day and touring productions'),
                                $this->loc('Studio-based recurring shows'),
                                $this->loc('Ticketed and pay-per-view streams'),
                                $this->loc('Venue and connectivity surveys'),
                            ],
                        ],
                    ]
                ),

                $this->faqs([
                    [$this->loc('What internet connection do you need?'),
                     $this->loc('We bring our own. A bonded connection combines several mobile networks with the venue line, so one failure does not take the stream down. Where a venue has fibre we use it as one of the inputs rather than the only one.')],
                    [$this->loc('Can you stream to more than one platform at once?'),
                     $this->loc('Yes. The Professional package covers two platforms and Premium covers four, plus a private feed for a website or an event app. Each destination gets the correct aspect ratio instead of one feed stretched across all of them.')],
                    [$this->loc('Do we get the recording?'),
                     $this->loc('Always. The full programme recording is delivered after the event, and session-by-session cuts trimmed to their real start and end times are included from the Professional package upwards.')],
                    [$this->loc('What happens if something fails during the event?'),
                     $this->loc('Cameras, encoders, power and the connection all carry a backup on the Premium package, and the operator switches to it without stopping the stream. We rehearse the run of show as well, so a failure is the only surprise left on the day.')],
                    [$this->loc('Can you handle a hybrid event with a live audience?'),
                     $this->loc('That is most of what we do. The room and the stream get separate audio mixes and separate graphics, so the online audience is not watching a wide shot of a hall and straining to hear.')],
                ]),

                $this->serviceCta($this->loc('Send us the date, the venue and the run of show, and we will come back with a rig, a platform plan and a price.')),
            ],
        ];
    }

    private function gearRenting(): array
    {
        return [
            'title'     => $this->loc('Gear Renting'),
            'metaTitle' => $this->loc('Camera, Lighting & Audio Rental in Colombo — CreativeFX'),
            'meta'      => $this->loc('Rent cameras, lenses, lighting, audio, tripods, gimbals, drones and studio equipment in Colombo, on daily and weekly LKR rates.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Services'),
                    $this->loc('Gear Renting'),
                    $this->loc('Cameras, lenses, lighting, audio, tripods, gimbals, drones and studio kit — on daily and weekly rates, tested before every collection.'),
                    'service-hero-gear-renting.svg'
                ),

                ['key' => 'overview', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Overview'),
                        'title'   => $this->loc('Professional kit, ready when you collect it'),
                        'text'    => $this->loc('<p>Everything in the rental catalogue is kit we use on our own shoots. It is cleaned, charged, formatted and function-tested before every collection, and it goes out with the cables, batteries, media and case it actually needs — not a body in a bag.</p><p>Rates are quoted in LKR by the day or the week, with a weekend rate that runs from Friday afternoon to Monday morning. If you would rather not operate it yourself, most of the catalogue can go out with one of our crew.</p>'),
                    ]],
                ]],

                // Live from the Gear module: rates and availability edited in
                // Admin are what the page shows.
                ['key' => 'catalogue', 'type' => 'gear', 'blocks' => [
                    ['gear_grid', [
                        'eyebrow' => $this->loc('Catalogue'),
                        'title'   => $this->loc('What is available'),
                        'intro'   => $this->loc('Day and week rates in LKR, with each item’s current availability. Ask us about anything you cannot see here — the catalogue is larger than one page.'),
                        'limit'   => 9,
                    ]],
                ]],

                $this->whatWeOffer(
                    $this->loc('What we rent'),
                    $this->loc('Six categories, all of it maintained as working production kit rather than shelf stock.'),
                    [
                        [$this->loc('Cameras'), $this->loc('Cinema and mirrorless bodies from Sony, Canon and Blackmagic, with batteries, chargers and formatted media in every case.')],
                        [$this->loc('Lenses'), $this->loc('Zoom and prime glass covering wide, standard and telephoto, checked for focus and cleaned between rentals.')],
                        [$this->loc('Lighting'), $this->loc('LED panels, COB fixtures, softboxes and stands, with the modifiers, gels and sandbags that make them useful.')],
                        [$this->loc('Audio'), $this->loc('Wireless lavalier kits, shotgun microphones and field recorders, supplied with mounts, windshields and spare batteries.')],
                        [$this->loc('Support & Movement'), $this->loc('Fluid-head tripods, sliders and gimbals, balanced and tested before they leave the store.')],
                        [$this->loc('Drones & Studio'), $this->loc('Drones for aerial work, and studio kit — backdrops, C-stands and streaming hardware — for productions that need a set as well as a camera.')],
                    ]
                ),

                // A rental has no edit or review stage, so the six steps follow
                // the booking rather than a production.
                $this->processSteps($this->loc('How renting works'), [
                    [$this->loc('Consultation'), $this->loc('Tell us about the shoot and we will tell you what it needs, including the pieces people forget until they are on location.')],
                    [$this->loc('Booking'), $this->loc('Dates confirmed against live availability, with a quote in LKR covering the full period and any operator time.')],
                    [$this->loc('Preparation'), $this->loc('Every item is cleaned, charged, formatted and function-tested, then packed with its cables, media and case.')],
                    [$this->loc('Collection'), $this->loc('You check the kit with us at handover, sign the condition sheet and leave with everything on the list.')],
                    [$this->loc('Support'), $this->loc('Our team stays reachable for the whole rental, and a replacement goes out the same day if anything fails.')],
                    [$this->loc('Return'), $this->loc('Kit is checked back in with you present, and the deposit is released once the condition sheet is cleared.')],
                ]),

                $this->packages(
                    $this->loc('Rental terms'),
                    $this->loc('Rates start from the figures below and depend on the kit; every item in the catalogue carries its own day and week rate.'),
                    [
                        [
                            'name'     => $this->loc('Day Rate'),
                            'price'    => 'From 7,500',
                            'period'   => $this->loc('per day'),
                            'cta'      => $this->loc('Check availability'),
                            'summary'  => $this->loc('A single production day, collected and returned the next working day.'),
                            'features' => [
                                $this->loc('24-hour rental period'),
                                $this->loc('Collection from 9am, return by 6pm the next day'),
                                $this->loc('Batteries, cables, media and case included'),
                                $this->loc('Refundable deposit and NIC or passport required'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Weekend Rate'),
                            'price'    => 'From 13,500',
                            'period'   => $this->loc('Friday to Monday'),
                            'cta'      => $this->loc('Check availability'),
                            'summary'  => $this->loc('Friday afternoon to Monday morning, charged as two days.'),
                            'features' => [
                                $this->loc('Collect Friday from 3pm'),
                                $this->loc('Return Monday by 11am'),
                                $this->loc('Charged at two day rates'),
                                $this->loc('Built for weddings and weekend shoots'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Weekly Rate'),
                            'price'    => 'From 32,000',
                            'period'   => $this->loc('per week'),
                            'featured' => true,
                            'ribbon'   => $this->loc('Best value'),
                            'cta'      => $this->loc('Check availability'),
                            'summary'  => $this->loc('Seven days for the price of four and a half.'),
                            'features' => [
                                $this->loc('7-day rental period'),
                                $this->loc('Charged at 4.5 day rates'),
                                $this->loc('Free swap if a unit fails mid-shoot'),
                                $this->loc('Priority booking on repeat rentals'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Kit & Operator'),
                            'price'    => 'From 18,500',
                            'period'   => $this->loc('per day'),
                            'cta'      => $this->loc('Talk to us'),
                            'summary'  => $this->loc('The equipment and one of our crew to run it.'),
                            'features' => [
                                $this->loc('Camera operator, gaffer or sound recordist'),
                                $this->loc('Up to 10 hours on location'),
                                $this->loc('Kit, transport and setup included'),
                                $this->loc('Overtime quoted in advance'),
                            ],
                        ],
                    ]
                ),

                $this->faqs([
                    [$this->loc('What do I need to rent equipment?'),
                     $this->loc('A valid NIC or passport, a refundable deposit, and for higher-value kit a company letter or a card on file. Everything is set out in the rental agreement you sign at collection, with no charge on it you have not already seen.')],
                    [$this->loc('Is the equipment insured?'),
                     $this->loc('Our kit is insured for our own use, but the rental agreement makes you responsible for loss or damage while it is with you. An optional damage waiver caps that liability and is quoted with the booking.')],
                    [$this->loc('Can you deliver the equipment?'),
                     $this->loc('Yes. Delivery and collection inside Colombo are quoted per booking, and island-wide delivery is available on weekly rentals. Most clients collect from the studio, which is also when we hand over and check the kit together.')],
                    [$this->loc('What happens if something stops working?'),
                     $this->loc('Call us. Every item is tested before it goes out, but if something fails during your rental we send a replacement the same day where we have one, and the hours you lost are credited against the rental.')],
                    [$this->loc('Do you rent to individuals or only to companies?'),
                     $this->loc('Both. Students and independent filmmakers rent from us regularly on the same rates. What changes with the value of the kit is the deposit, not who is renting it.')],
                ]),

                $this->serviceCta($this->loc('Send us the dates and the shoot, and we will hold the equipment while the quote is confirmed.')),
            ],
        ];
    }

    private function socialMediaAdvertising(): array
    {
        return [
            'title'     => $this->loc('Social Media Advertising'),
            'metaTitle' => $this->loc('Social Media Advertising — Meta, TikTok & YouTube | CreativeFX'),
            'meta'      => $this->loc('Paid social campaigns on Facebook, Instagram, TikTok and YouTube, built on creative produced in our Colombo studio.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Services'),
                    $this->loc('Social Media Advertising'),
                    $this->loc('Paid campaigns on Meta, TikTok and YouTube — built on creative we produce and optimised every week against the cost per result.'),
                    'service-hero-social-media-advertising.svg'
                ),

                ['key' => 'overview', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Overview'),
                        'title'   => $this->loc('Creative that earns its place in the feed'),
                        'text'    => $this->loc('<p>Most campaigns fail on the creative, not the targeting. We set the strategy, produce the assets in our own studio and run the campaigns across Facebook, Instagram, TikTok and YouTube — so a piece that is not working is replaced the same week instead of the next quarter.</p><p>Audience testing, retargeting and weekly optimisation are part of every retainer, and the monthly report shows spend, results and cost per result rather than impressions dressed up as performance.</p>'),
                    ]],
                ]],

                $this->whatWeOffer(
                    $this->loc('What we offer'),
                    $this->loc('Strategy, creative and media buying handled by the same team.'),
                    [
                        [$this->loc('Campaign Strategy'), $this->loc('Objectives, audiences, budget split and a testing plan agreed before a rupee of media is spent.')],
                        [$this->loc('Creative Production'), $this->loc('Statics, vertical video and carousels produced in our studio, built for the placement rather than resized into it.')],
                        [$this->loc('Meta Advertising'), $this->loc('Facebook and Instagram campaigns with structured audience testing, retargeting and catalogue ads for retail.')],
                        [$this->loc('TikTok & YouTube'), $this->loc('Short-form and in-stream campaigns cut natively for each platform, with creator-style variants where they perform.')],
                        [$this->loc('Testing & Optimisation'), $this->loc('Weekly reviews of creative, audience and placement, with losing variants cut and winners scaled.')],
                        [$this->loc('Reporting'), $this->loc('A monthly report in plain language: spend, results, cost per result, and what changes next month.')],
                    ]
                ),

                $this->processSteps($this->loc('How a campaign runs'), [
                    [$this->loc('Consultation'), $this->loc('We look at what you are selling, what you have spent so far and what a result is actually worth to you.')],
                    [$this->loc('Planning'), $this->loc('Campaign structure, audiences, budgets and a creative brief, written down and approved.')],
                    [$this->loc('Production'), $this->loc('The assets are shot, designed and cut in the studio, in every ratio the placements need.')],
                    [$this->loc('Editing'), $this->loc('Copy, hooks and variants built for testing, with tracking and pixels verified before launch.')],
                    [$this->loc('Review'), $this->loc('Weekly optimisation: budget moves to what is working and the rest is rewritten or replaced.')],
                    [$this->loc('Delivery'), $this->loc('A monthly report, the raw creative files, and the plan for the month ahead.')],
                ]),

                $this->packages(
                    $this->loc('Monthly retainers'),
                    $this->loc('These are management fees. Advertising spend is paid directly to the platforms from your own account and is not included in the figures below.'),
                    [
                        [
                            'name'     => $this->loc('Starter'),
                            'price'    => '45,000',
                            'period'   => $this->loc('per month'),
                            'summary'  => $this->loc('One platform, managed properly.'),
                            'features' => [
                                $this->loc('One platform — Meta or TikTok'),
                                $this->loc('Up to LKR 150,000 monthly ad spend managed'),
                                $this->loc('Four creative assets a month'),
                                $this->loc('Audience and retargeting setup'),
                                $this->loc('Monthly performance report'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Professional'),
                            'price'    => '95,000',
                            'period'   => $this->loc('per month'),
                            'featured' => true,
                            'summary'  => $this->loc('Two platforms, with creative produced every month.'),
                            'features' => [
                                $this->loc('Two platforms'),
                                $this->loc('Up to LKR 500,000 monthly ad spend managed'),
                                $this->loc('Ten creative assets a month, including video'),
                                $this->loc('Weekly optimisation and creative testing'),
                                $this->loc('Retargeting and lookalike audiences'),
                                $this->loc('Monthly report and strategy call'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Premium'),
                            'price'    => '185,000',
                            'period'   => $this->loc('per month'),
                            'summary'  => $this->loc('Full-funnel campaigns across three platforms.'),
                            'features' => [
                                $this->loc('Three platforms'),
                                $this->loc('Up to LKR 1.5M monthly ad spend managed'),
                                $this->loc('Twenty creative assets a month'),
                                $this->loc('A monthly studio content day'),
                                $this->loc('Landing page and conversion tracking'),
                                $this->loc('Fortnightly reporting and review calls'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Custom'),
                            'price'    => 'Custom',
                            'currency' => '',
                            'cta'      => $this->loc('Talk to us'),
                            'summary'  => $this->loc('Higher spend, several markets or a seasonal push.'),
                            'features' => [
                                $this->loc('Ad spend above LKR 1.5M a month'),
                                $this->loc('Multi-market and multi-language campaigns'),
                                $this->loc('Launch and seasonal campaign sprints'),
                                $this->loc('In-house team training and handover'),
                            ],
                        ],
                    ]
                ),

                $this->faqs([
                    [$this->loc('Is the advertising budget included in your fee?'),
                     $this->loc('No. The package price is our management fee. Advertising spend goes directly to Meta, TikTok or Google from your own account, so you see every rupee and the account stays yours if we ever stop working together.')],
                    [$this->loc('How much should we spend on ads?'),
                     $this->loc('For most Colombo businesses a meaningful test starts around LKR 100,000 to 150,000 a month per platform. Below that, results arrive too slowly to learn from. We will tell you when a budget is better spent on one platform than split across three.')],
                    [$this->loc('How soon will we see results?'),
                     $this->loc('The first two weeks are learning and testing. By week three the winning audiences and creative are usually clear, and month two is normally where cost per result starts improving. Anyone promising results in week one is guessing.')],
                    [$this->loc('Do you produce the creative, or do we?'),
                     $this->loc('We produce it. Every package includes a monthly set of assets shot and cut in our studio, which is the point of working with a production team: when a campaign stalls, the fastest fix is new creative, and we do not have to wait for a third party to make it.')],
                    [$this->loc('Who owns the ad accounts and the data?'),
                     $this->loc('You do. We work inside your Business Manager and your ad accounts with agency access. If you leave, the accounts, the audiences, the pixel data and the creative files stay with you.')],
                ]),

                $this->serviceCta($this->loc('Tell us what you sell, what a customer is worth and what you are spending now, and we will show you what the campaign should look like.')),
            ],
        ];
    }

    private function digitalMarketing(): array
    {
        return [
            'title'     => $this->loc('Digital Marketing'),
            'metaTitle' => $this->loc('Digital Marketing — SEO, Google Ads & Analytics | CreativeFX'),
            'meta'      => $this->loc('SEO, content, Google Ads, email and analytics run to one plan by a Colombo team, and measured in enquiries rather than traffic.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Services'),
                    $this->loc('Digital Marketing'),
                    $this->loc('SEO, content, Google Ads, email and analytics working to a single plan — so the traffic you pay for turns into enquiries you can count.'),
                    'service-hero-digital-marketing.svg'
                ),

                ['key' => 'overview', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Overview'),
                        'title'   => $this->loc('One plan behind every channel'),
                        'text'    => $this->loc('<p>Search, content, ads and email fail separately when nobody joins them up. We run them to one plan: the keywords inform the content, the content feeds the ads, and the analytics tell all three what to do next month.</p><p>Everything is measured against enquiries, bookings and sales rather than traffic. If a channel is not paying for itself after a fair test, we say so and move the budget.</p>'),
                    ]],
                ]],

                $this->whatWeOffer(
                    $this->loc('What we offer'),
                    $this->loc('The channels we run, and the measurement that keeps them honest.'),
                    [
                        [$this->loc('Search Optimisation'), $this->loc('Technical fixes, on-page work and local SEO for the searches your customers in Sri Lanka actually type.')],
                        [$this->loc('Content Marketing'), $this->loc('Articles, landing pages and video built around search demand and the questions your sales team keeps answering.')],
                        [$this->loc('Google Ads'), $this->loc('Search, Performance Max and YouTube campaigns managed against cost per enquiry rather than clicks.')],
                        [$this->loc('Email & CRM'), $this->loc('Newsletters, automated sequences and list segmentation that keep past customers buying again.')],
                        [$this->loc('Conversion Optimisation'), $this->loc('Landing page structure, forms, page speed and calls to action tested against real enquiry numbers.')],
                        [$this->loc('Analytics & Reporting'), $this->loc('GA4, Search Console and conversion tracking set up properly, and a monthly report that says what changed and why.')],
                    ]
                ),

                $this->processSteps($this->loc('How the work runs'), [
                    [$this->loc('Consultation'), $this->loc('An audit of the site, the search terms, the tracking you have and where enquiries come from today.')],
                    [$this->loc('Planning'), $this->loc('A quarterly roadmap with monthly priorities and the number each one is meant to move.')],
                    [$this->loc('Production'), $this->loc('Pages, articles, ad creative and email written and built to that roadmap.')],
                    [$this->loc('Editing'), $this->loc('Technical fixes, on-page changes and conversion work shipped to the live site.')],
                    [$this->loc('Review'), $this->loc('A monthly look at rankings, traffic, enquiries and cost per enquiry against the plan.')],
                    [$this->loc('Delivery'), $this->loc('A report you can read in five minutes, and next month’s priorities agreed on a call.')],
                ]),

                $this->packages(
                    $this->loc('Monthly retainers'),
                    $this->loc('These are management fees. Google Ads spend is paid directly to Google from your own account and is not included in the figures below.'),
                    [
                        [
                            'name'     => $this->loc('Starter'),
                            'price'    => '55,000',
                            'period'   => $this->loc('per month'),
                            'summary'  => $this->loc('Search foundations for a small site.'),
                            'features' => [
                                $this->loc('Technical SEO fixes and on-page work'),
                                $this->loc('Up to 15 target keywords'),
                                $this->loc('Google Business Profile management'),
                                $this->loc('Two content pieces a month'),
                                $this->loc('Monthly ranking and traffic report'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Professional'),
                            'price'    => '120,000',
                            'period'   => $this->loc('per month'),
                            'featured' => true,
                            'summary'  => $this->loc('Search and paid, working together.'),
                            'features' => [
                                $this->loc('Everything in Starter'),
                                $this->loc('Up to 40 target keywords'),
                                $this->loc('Google Ads management'),
                                $this->loc('Four content pieces a month'),
                                $this->loc('GA4 and conversion tracking'),
                                $this->loc('Monthly strategy call'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Premium'),
                            'price'    => '245,000',
                            'period'   => $this->loc('per month'),
                            'summary'  => $this->loc('A full channel mix with conversion work.'),
                            'features' => [
                                $this->loc('Everything in Professional'),
                                $this->loc('Content, email and landing pages'),
                                $this->loc('Conversion rate optimisation programme'),
                                $this->loc('Competitor and market tracking'),
                                $this->loc('Fortnightly reporting'),
                                $this->loc('Quarterly strategy workshop'),
                            ],
                        ],
                        [
                            'name'     => $this->loc('Custom'),
                            'price'    => 'Custom',
                            'currency' => '',
                            'cta'      => $this->loc('Talk to us'),
                            'summary'  => $this->loc('Several sites, several languages, or support for an in-house team.'),
                            'features' => [
                                $this->loc('Multi-site and multi-language programmes'),
                                $this->loc('E-commerce and marketplace strategy'),
                                $this->loc('In-house team training'),
                                $this->loc('One-off audits and consulting days'),
                            ],
                        ],
                    ]
                ),

                $this->faqs([
                    [$this->loc('How long does SEO take to work?'),
                     $this->loc('Technical and on-page fixes can move rankings within a month. Competitive terms take three to six months of consistent work, and anyone offering page one in thirty days is either bidding on your own brand name or selling something else.')],
                    [$this->loc('Is Google Ads spend included?'),
                     $this->loc('No. The management fee is what you pay us; the ad spend goes directly to Google from your own account. We recommend a starting budget once we have seen the search volumes and the competition for your terms.')],
                    [$this->loc('Do you work with our existing website?'),
                     $this->loc('Usually, yes — most sites can be fixed rather than replaced. If the platform is holding results back, with no page speed, no tracking and no way to add landing pages, we will say so before you spend a month paying for work the site will waste.')],
                    [$this->loc('What do you report on?'),
                     $this->loc('Enquiries, calls, bookings or sales first, then the traffic and rankings behind them. Every report says what we changed, what it did and what happens next month, in language you can forward to your board.')],
                    [$this->loc('Can you handle Sinhala and Tamil content?'),
                     $this->loc('Yes. Search behaviour in Sinhala and Tamil differs from English, and treating them as translations of the same page wastes the opportunity. We plan keywords and write content separately for each language.')],
                ]),

                $this->serviceCta($this->loc('Send us your website and what you want more of — enquiries, bookings or orders — and we will come back with a plan and a price.')),
            ],
        ];
    }

    // -----------------------------------------------------------------
    // Contact
    // -----------------------------------------------------------------

    private function contact(): array
    {
        return [
            'title'     => $this->loc('Contact'),
            'metaTitle' => $this->loc('Contact CreativeFX — Colombo Creative Studio'),
            'meta'      => $this->loc('Talk to the CreativeFX studio in Colombo about photography, video, podcasting, live streaming, equipment rental or digital marketing.'),
            'sections'  => [
                $this->hero(
                    $this->loc('Contact'),
                    $this->loc('Let’s Create Something Great Together.'),
                    $this->loc('Tell us about the project — a shoot, a season, an event or a campaign — and we will come back within one working day.'),
                    'hero-contact.svg'
                ),

                ['key' => 'contact', 'type' => 'contact', 'blocks' => [
                    ['contact_block', [
                        'title' => $this->loc('Get in touch'),
                        'intro' => $this->loc('Messages sent from here land with the studio, not a shared inbox. We answer every enquiry within one working day, and if a project is not one we should take, we will tell you who is better placed to do it.'),
                    ]],
                ]],

                ['key' => 'quote', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'  => $this->loc('Need a price?'),
                        'text'   => $this->loc('The quote request walks through the service, the scope, your date and your budget in a few short steps — everything we need to come back with a real number rather than a range.'),
                        'button' => $this->loc('Request a Quote'),
                        'url'    => 'quote',
                    ]],
                ]],
            ],
        ];
    }
}
