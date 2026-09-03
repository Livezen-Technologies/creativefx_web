<?php

namespace Modules\Cms\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the CMS-driven Home page: the page row, its storytelling sections and
 * blocks (all locale-aware JSON), plus the launch video with one audio track
 * and one subtitle file per supported language. Replacing the placeholder media
 * later is a data change (update these rows / upload via admin) — no code edits.
 */
class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $j   = static fn (array $map): string => json_encode($map, JSON_UNESCAPED_UNICODE);

        // ---- Page -------------------------------------------------------
        // The page's own title and description are seed-managed, exactly like the
        // sections below that this seeder clears and rewrites on every run. They
        // were insert-only, so standing this codebase up as a second site left
        // the home page introducing itself as the first one: the browser tab and
        // every search result still read "Magic Corn — Corn in a Cup" while the
        // page beneath them was a hotel. Nothing on the page showed it, which is
        // why it survived a visual check.
        $pages    = $this->db->table('pages');
        $pageMeta = [
            'title'            => $j(['en' => 'Kukuleganga Giants Forest']),
            'meta_title'       => $j(['en' => 'Kukuleganga Giants Forest — Hotel in Kalawana, Sri Lanka']),
            'meta_description' => $j(['en' => 'A hotel above the Kukuleganga reservoir at the edge of the Sinharaja rainforest — rooms, a 35m pool and Sri Lankan cooking, 25km from Sinharaja.']),
            'template'         => 'home',
            'is_home'          => 1,
            'status'           => 'published',
            'updated_at'       => $now,
        ];
        if ($pages->where('slug', 'home')->get()->getRowArray() === null) {
            $pages->insert($pageMeta + ['slug' => 'home', 'created_at' => $now]);
        } else {
            $pages->where('slug', 'home')->update($pageMeta);
        }
        $pageId = (int) $pages->where('slug', 'home')->get()->getRowArray()['id'];

        // Idempotency: clear existing sections/blocks for this page, then reseed.
        $existingSections = array_column(
            $this->db->table('page_sections')->select('id')->where('page_id', $pageId)->get()->getResultArray(),
            'id'
        );
        if ($existingSections !== []) {
            $this->db->table('page_blocks')->whereIn('section_id', $existingSections)->delete();
            $this->db->table('page_sections')->where('page_id', $pageId)->delete();
        }

        $sections = $this->db->table('page_sections');
        $blocks   = $this->db->table('page_blocks');

        $addSection = function (string $key, string $type, int $order) use ($sections, $pageId, $now): int {
            $sections->insert([
                'page_id'    => $pageId,
                'key'        => $key,
                'type'       => $type,
                'sort_order' => $order,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return (int) $this->db->insertID();
        };
        $addBlock = static function (int $sectionId, string $type, array $content, int $order) use ($blocks, $j, $now): void {
            $blocks->insert([
                'section_id' => $sectionId,
                'type'       => $type,
                'content'    => $j($content),
                'sort_order' => $order,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        // ---- Hero -------------------------------------------------------
        $hero = $addSection('hero', 'hero', 0);
        $addBlock($hero, 'hero', [
            'headline' => [
                'en' => 'Stay in sapphire land with your closest.',
            ],
            'subhead' => [
                'en' => 'A hotel above the Kukuleganga reservoir, at the edge of the Sinharaja rainforest.',
            ],
            // Kinetic-typography hero: pre + cycling word + post.
            'pre' => [
                'en' => 'Wake up to',
            ],
            'post' => [
                'en' => 'in sapphire land.',
            ],
            // What a guest actually opens their eyes to, per the hotel's own copy.
            'rotators' => [
                ['en' => 'birdsong'],
                ['en' => 'sunrise'],
                ['en' => 'cool air'],
                ['en' => 'still water'],
                ['en' => 'rainforest'],
            ],
        ], 0);

        // ---- Welcome ----------------------------------------------------
        // The hotel's own introduction, kept in its own words.
        $welcome = $addSection('welcome', 'welcome', 1);
        $addBlock($welcome, 'welcome', [
            'eyebrow' => ['en' => 'Stay in sapphire land with your closest,'],
            'title'   => ['en' => 'Welcome to Giants Forest'],
            'lead'    => ['en' => 'It’s your time to get a Diamond Experience at Gem City, Sabaragamuwa Province in Sri Lanka.'],
            'body'    => [
                ['en' => 'Zephyr in the valley and singing birds will wake up you. Open your eyes at sunrise with lovely sceneries of Kukuleganga Reservoir next to you. Rising Sun, Sparkling Rain Drops and Drizzling makes your eyes Blue and Green. Take a breath while isolating from your busy life. Calm environment, Cool Climate over the year will take you for an unforgettable break.'],
                ['en' => 'It’s your Second Home, 46 Km away from Dodangoda Highway Exit. Arrival within 2 and half hours from Bandaranayke International Airport. All Holidaymakers are welcome to Wet Zone, Sinharaja Rain Forest just before you. It’s only 25km. Our hospitality will comfort you to be in your dreamy holidays. Spend your leisure time with us.'],
            ],
            'image_a'     => '/media/giantforests/Welcome-1.jpg',
            'image_a_alt' => ['en' => 'The pool at dusk, lit against the forest'],
            'image_b'     => '/media/giantforests/Welcome-to-Giants-Forest-1-1.jpg',
            'image_b_alt' => ['en' => 'The Kukuleganga reservoir from the air, forest on both banks'],
        ], 0);

        // ---- Our Facilities ----------------------------------------------
        // The six the hotel lists on its own site, in its own order. Each names
        // its icon, so reordering them in the CMS cannot hand one the wrong mark.
        $facilities = $addSection('facilities', 'facilities', 2);
        $addBlock($facilities, 'facilities', [
            'title'     => ['en' => 'Our Facilities'],
            'intro'     => ['en' => 'We always think, comfort you at highest level. You may avail yourself of followings.'],
            'image'     => '/media/giantforests/Our-Amenities-Giants-forest-Hotel1.jpg',
            'image_alt' => ['en' => 'The hotel and its pool from directly above, ringed by forest'],
            'watermark' => '/media/giantforests/Kukuleganga-Giants-Forest-Logo-white.png',
            'items'     => [
                ['icon' => 'clock',     'label' => ['en' => '24hr Room Service']],
                ['icon' => 'droplet',   'label' => ['en' => 'Laundry Service']],
                ['icon' => 'mic',       'label' => ['en' => 'Conference Hall']],
                ['icon' => 'car',       'label' => ['en' => 'Parking Facilities']],
                ['icon' => 'waves',     'label' => ['en' => '35m Swimming Pool with Baby Pool']],
                ['icon' => 'snowflake', 'label' => ['en' => 'A/C Non A/C Room with Large Space']],
            ],
        ], 0);

        // ---- Rooms & Suites ------------------------------------------------
        // Block 0 is the heading; every block after it is a room.
        $rooms = $addSection('rooms', 'rooms', 3);
        $addBlock($rooms, 'rooms_head', [
            'eyebrow' => ['en' => 'Engage with modern luxuries experience at your own place.'],
            'title'   => ['en' => 'Rooms & Suites'],
            'button'  => ['en' => 'View All'],
            'url'     => 'accommodation',
        ], 0);
        $roomData = [
            [
                'title'  => ['en' => 'Deluxe Room'],
                'text'   => ['en' => 'Superior double rooms are made with open living area comforts you and morning sight of Sabaragamuwa hills at balcony in the room feel you better.'],
                'image'  => '/media/giantforests/Deluxe-Room-Giants-forest.jpg',
                'button' => ['en' => 'Book Now'],
                'url'    => 'contact',
            ],
            [
                'title'  => ['en' => 'Standard Room'],
                'text'   => ['en' => 'The rooms consist of wooden ceiling, make cool atmosphere. All the facilities are compiled with these rooms.'],
                'image'  => '/media/giantforests/Single-Room-Giants-forest.jpg',
                'button' => ['en' => 'Book Now'],
                'url'    => 'contact',
            ],
        ];
        foreach ($roomData as $i => $room) {
            $addBlock($rooms, 'room', $room, $i + 1);
        }

        // ---- People Say ----------------------------------------------------
        // Guest reviews, reproduced as they were written. They are attributed to
        // named people, so the odd typo stays: correcting it would put words in
        // their mouths they did not write.
        $says = $addSection('testimonials', 'testimonials', 4);
        $addBlock($says, 'testimonials_head', [
            'title'     => ['en' => 'People Say'],
            'image'     => '/media/giantforests/People-Say-Giants-forest.jpg',
            'image_alt' => ['en' => 'Tea served in the hotel’s own branded cups'],
            'watermark' => '/media/giantforests/Kukuleganga-Giants-Forest-Logo-white.png',
        ], 0);
        $quotes = [
            ['Kate Palmer', 'Australia', 'We stayed here with our family and are fully satisfied with our vacation. The rooms are very modern, have all the needed amenities, the kitchen is very delicious and the service is just perfect. We will for sure come back.'],
            ['Prasanna Ranasinghe', 'Sri Lanka', "My wife and I had the pleasure of spending an amazing night at this unique hotel.\nOur experience from the initial booking to the final checkout was amazing, with the staff and management\nThank you very much Giants Forest….Good Luck…"],
            ['Sudath Savinda', 'Sri Lanka', 'Great location, really pleasant and clean rooms. All of the people are incredibly helpful and generous with their time and advice. This was one of the nicest places we stayed in Sri Lanka.'],
            ['Charith Ramachandra', 'Sri Lanka', 'Nice place with tasty and affordable food and beverages.'],
            ['Chandika Hemasinghe', 'Sri Lanka', "This is the ideal location near Sinharaja Forest as well as water front experience\nRecommended"],
            ['Madusha Wickramasinghe', 'Sri Lanka', 'We had two days stay there and staff and the food was very attractive. Friendly staff and It has a very large nice pool'],
            ['Sunsu Holdings', 'Sri Lanka', "Very Nice Place and surrounding beautiful tea lands and situated in front of Kuda Ganga\nWonderful experience, coll climate"],
            ['Sampath Hewawitharana', 'Sri Lanka', 'Highly recommend location. In.side the forest real relaxation'],
        ];
        foreach ($quotes as $i => [$name, $place, $quote]) {
            $addBlock($says, 'testimonial', [
                'name'  => $name,
                'place' => $place,
                'quote' => ['en' => $quote],
            ], $i + 1);
        }

        // ---- The film ------------------------------------------------------
        // The hotel publishes its film on YouTube rather than as a file, so it is
        // embedded, not copied. The id lives in content so it can be swapped
        // without a deploy; the view builds the loop parameters around it.
        $film = $addSection('film', 'film', 5);
        $addBlock($film, 'film', [
            'eyebrow'    => ['en' => 'See before you feel'],
            'title'      => ['en' => 'Our Captions at the Hotel Premises'],
            'youtube_id' => 'hOcQxQi61d0',
            'poster'     => '/media/giantforests/Gallery-Giants-Forests-Hotel.jpg',
            'button'     => ['en' => 'View Gallery'],
            'url'        => 'gallery',
        ], 0);

        // ---- CTA --------------------------------------------------------
        $cta = $addSection('cta', 'cta', 6);
        $addBlock($cta, 'cta', [
            'title'  => ['en' => 'Reserve your room today'],
            'text'   => ['en' => 'Send us your dates and we will confirm availability. Nothing is charged on this site.'],
            'button' => ['en' => 'Book Now'],
            'url'    => 'contact',
        ], 0);

        $this->seedVideo($now, $j);
        $this->seedEsgMetrics($now, $j);
    }

    private function seedVideo(string $now, callable $j): void
    {
        $videos = $this->db->table('videos');
        if ($videos->where('key', 'home_launch')->get()->getRowArray() === null) {
            $videos->insert([
                'key'           => 'home_launch',
                'title'         => $j(['en' => 'Kukuleganga Giants Forest']),
                // The hotel's own film, behind the hero. It is muted and loops,
                // so it carries no audio track: those are bytes every visitor
                // downloads and nobody ever hears. The still stays as the
                // poster, painting immediately while the film buffers, and
                // covering for it entirely where the film cannot play.
                'src_path'      => '/media/video/giants-forest-hero.mp4',
                'src_path_webm' => '/media/video/giants-forest-hero.webm',
                'duration_seconds' => 79,
                'poster_path'   => '/media/giantforests/Welcome-to-Giants-Forest-3.jpg',
                'is_muted_loop' => 1,
                'status'        => 'published',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        } else {
            // The old rule named one specific stale path, so it recognised the
            // brand it was written for and nothing else — standing this codebase
            // up as a second site left the first site's photograph in the hero.
            //
            // The honest rule is about ownership, not paths: an empty src_path
            // means nobody has uploaded a film here, so the poster is still
            // seed-managed and should follow the seed. Upload one in
            // Admin -> Videos and both are left alone from then on.
            $current = $videos->where('key', 'home_launch')->get()->getRowArray();
            if (($current['src_path'] ?? '') === '' || str_starts_with((string) $current['src_path'], '/media/video/giants-forest-hero')) {
                $videos->where('key', 'home_launch')->update([
                    'src_path'      => '/media/video/giants-forest-hero.mp4',
                    'src_path_webm' => '/media/video/giants-forest-hero.webm',
                    'poster_path'   => '/media/giantforests/Welcome-to-Giants-Forest-3.jpg',
                    'duration_seconds' => 79,
                    'updated_at'    => $now,
                ]);
            }
        }
        $videoId = (int) $videos->where('key', 'home_launch')->get()->getRowArray()['id'];

        $this->db->table('video_tracks')->where('video_id', $videoId)->delete();
        $this->db->table('video_subtitles')->where('video_id', $videoId)->delete();

        $langs = [
            ['en', 'English', 1],
            ['ja', '日本語', 0],
            ['es', 'Español', 0],
            ['zh', '中文', 0],
        ];
        $tracks = [];
        $subs   = [];
        foreach ($langs as $i => [$code, $label, $default]) {
            $tracks[] = [
                'video_id'   => $videoId,
                'locale'     => $code,
                'label'      => $label,
                'audio_path' => "/media/audio/launch-{$code}.wav",
                'kind'       => 'audio',
                'is_default' => $default,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $subs[] = [
                'video_id'   => $videoId,
                'locale'     => $code,
                'label'      => $label,
                'vtt_path'   => "/media/subtitles/launch-{$code}.vtt",
                'is_default' => $default,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->db->table('video_tracks')->insertBatch($tracks);
        $this->db->table('video_subtitles')->insertBatch($subs);
    }

    private function seedEsgMetrics(string $now, callable $j): void
    {
        $metrics = [
            ['emission_reduction', ['en' => 'Emission reduction'], '32', '%', 'sourcing'],
            ['water_reduction', ['en' => 'Water reduction'], '28', '%', 'sourcing'],
            ['women_leadership', ['en' => 'Women in leadership'], '46', '%', 'innovation'],
            ['employee_training', ['en' => 'Training hours / year'], '120k', '', 'innovation'],
        ];
        $rows = [];
        foreach ($metrics as $i => [$key, $label, $value, $unit, $pillar]) {
            $rows[] = [
                'key'        => $key,
                'label'      => $j($label),
                'value'      => $value,
                'unit'       => $unit,
                'pillar'     => $pillar,
                'year'       => (int) date('Y'),
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->db->table('esg_metrics')->ignore(true)->insertBatch($rows);
    }
}
