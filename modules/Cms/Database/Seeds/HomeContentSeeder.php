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
        $pages = $this->db->table('pages');
        if ($pages->where('slug', 'home')->get()->getRowArray() === null) {
            $pages->insert([
                'slug'             => 'home',
                'title'            => $j(['en' => 'Magic Corn']),
                'meta_title'       => $j(['en' => 'Magic Corn — Corn in a Cup']),
                'meta_description' => $j(['en' => 'Sri Lanka’s original corn in a cup — sweet corn grown, steamed and topped your way since 2007.']),
                'template'         => 'home',
                'is_home'          => 1,
                'status'           => 'published',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
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

        // ---- Stats ------------------------------------------------------
        $stats = $addSection('stats', 'stats', 1);
        $statData = [
            ['value' => '35', 'suffix' => 'm', 'label' => ['en' => 'Swimming pool, with a baby pool']],
            ['value' => '25', 'suffix' => 'km', 'label' => ['en' => 'To the Sinharaja rainforest']],
            ['value' => '46', 'suffix' => 'km', 'label' => ['en' => 'From the Dodangoda highway exit']],
            ['value' => '24', 'suffix' => 'hr', 'label' => ['en' => 'Room service, every day']],
        ];
        foreach ($statData as $i => $s) {
            $addBlock($stats, 'stat', $s, $i);
        }

        // ---- Facilities --------------------------------------------------
        // The six the hotel lists on its own site, in its own order.
        $facilities = $addSection('certificates', 'certificates', 2);
        $addBlock($facilities, 'certificates', [
            'eyebrow' => ['en' => 'Our facilities'],
            'title'   => ['en' => 'Comfort, at the highest level'],
            'intro'   => ['en' => 'We always think about comforting you at the highest level. You may avail yourself of the following.'],
            'items'   => [
                ['en' => '24hr Room Service'],
                ['en' => 'Laundry Service'],
                ['en' => 'Conference Hall'],
                ['en' => 'Parking Facilities'],
                ['en' => '35m Swimming Pool with Baby Pool'],
                ['en' => 'A/C and Non A/C Rooms'],
            ],
        ], 0);

        // ---- CTA --------------------------------------------------------
        $cta = $addSection('cta', 'cta', 3);
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
                'title'         => $j(['en' => 'Magic Corn Film']),
                // No Magic Corn film supplied yet. Left empty on purpose: the hero
                // falls back to its gradient treatment rather than showing the
                // previous brand's factory footage. Set these when footage arrives.
                'src_path'      => '',
                'poster_path'   => '/media/giantforests/Welcome-to-Giants-Forest-1-1.jpg',
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
            if (($current['src_path'] ?? '') === '') {
                $videos->where('key', 'home_launch')->update([
                    'src_path' => '', 'poster_path' => '/media/giantforests/Welcome-to-Giants-Forest-1-1.jpg', 'updated_at' => $now,
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
