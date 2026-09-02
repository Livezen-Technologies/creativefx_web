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
                'title'            => $j(['en' => 'Norlanka', 'es' => 'Norlanka', 'ja' => 'ノーランカ', 'zh' => '诺兰卡']),
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
                'en' => 'Sweet corn, served hot in a cup.',
                'es' => 'Maíz dulce, servido caliente en vaso.',
                'ja' => 'スイートコーンを、あつあつのカップで。',
                'zh' => '甜玉米，热腾腾装进杯里。',
            ],
            'subhead' => [
                'en' => 'Grown, steamed and topped your way — Sri Lanka’s original corn in a cup.',
                'es' => 'Cultivado, cocido al vapor y con los toppings que elijas: el maíz en vaso original de Sri Lanka.',
                'ja' => '自社栽培、蒸したて、トッピングはお好みで — スリランカ発、カップコーンの原点。',
                'zh' => '自种、现蒸、配料自选——斯里兰卡最早的杯装甜玉米。',
            ],
            // Kinetic-typography hero: pre + cycling word + post.
            'pre' => [
                'en' => 'Sweet corn, served',
                'es' => 'Maíz dulce, servido',
                'ja' => 'スイートコーンを、',
                'zh' => '甜玉米，就要',
            ],
            'post' => [
                'en' => 'in a cup.',
                'es' => 'en vaso.',
                'ja' => 'カップで。',
                'zh' => '装进杯里。',
            ],
            // Cycling words are the toppings the outlets actually serve.
            'rotators' => [
                ['en' => 'buttered', 'es' => 'con mantequilla', 'ja' => 'バターで', 'zh' => '黄油'],
                ['en' => 'cheesy', 'es' => 'con queso', 'ja' => 'チーズで', 'zh' => '芝士'],
                ['en' => 'garlicky', 'es' => 'con ajo', 'ja' => 'ガーリックで', 'zh' => '蒜香'],
                ['en' => 'spiced', 'es' => 'con especias', 'ja' => 'スパイスで', 'zh' => '香料'],
                ['en' => 'hot', 'es' => 'caliente', 'ja' => 'あつあつで', 'zh' => '热腾腾'],
            ],
        ], 0);

        // ---- Stats ------------------------------------------------------
        $stats = $addSection('stats', 'stats', 1);
        $statData = [
            ['value' => '2007', 'suffix' => '', 'label' => ['en' => 'Serving corn in a cup since', 'es' => 'Sirviendo maíz en vaso desde', 'ja' => 'カップコーンの提供開始', 'zh' => '杯装玉米始于']],
            ['value' => '30', 'suffix' => '+', 'label' => ['en' => 'Outlets island-wide', 'es' => 'Locales en toda la isla', 'ja' => '島内の店舗数', 'zh' => '全岛门店']],
            ['value' => '40', 'suffix' => '+', 'label' => ['en' => 'Women employed at our factory', 'es' => 'Mujeres empleadas en nuestra fábrica', 'ja' => '工場で働く女性従業員', 'zh' => '工厂女性员工']],
            ['value' => '100', 'suffix' => '%', 'label' => ['en' => 'Homegrown Sri Lankan brand', 'es' => 'Marca 100% de Sri Lanka', 'ja' => '100%スリランカ国産ブランド', 'zh' => '100% 斯里兰卡本土品牌']],
        ];
        foreach ($statData as $i => $s) {
            $addBlock($stats, 'stat', $s, $i);
        }

        // ---- CTA --------------------------------------------------------
        $cta = $addSection('cta', 'cta', 2);
        $addBlock($cta, 'cta', [
            'title'  => ['en' => 'Take Magic Corn home', 'es' => 'Llévate Magic Corn a casa', 'ja' => 'Magic Cornをおうちで', 'zh' => '把 Magic Corn 带回家'],
            'text'   => ['en' => 'Our 1kg frozen sweet corn pack — the same corn we serve at the outlets.', 'es' => 'Nuestro paquete de 1 kg de maíz dulce congelado, el mismo que servimos en los locales.', 'ja' => '店舗と同じスイートコーンを1kg冷凍パックで。', 'zh' => '1 公斤冷冻甜玉米装——与门店同款。'],
            'button' => ['en' => 'Visit the shop', 'es' => 'Ir a la tienda', 'ja' => 'ショップを見る', 'zh' => '前往商店'],
            'url'    => 'products',
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
                'poster_path'   => '',
                'is_muted_loop' => 1,
                'status'        => 'published',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        } else {
            // Only clear a path still pointing at the previous brand's footage;
            // a film added later in Admin -> Videos is left alone.
            $current = $videos->where('key', 'home_launch')->get()->getRowArray();
            if (str_contains((string) ($current['src_path'] ?? ''), '/media/video/home-hero')) {
                $videos->where('key', 'home_launch')->update([
                    'src_path' => '', 'poster_path' => '', 'updated_at' => $now,
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
