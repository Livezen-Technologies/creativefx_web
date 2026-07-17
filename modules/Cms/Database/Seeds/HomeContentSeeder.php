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
                'meta_title'       => $j(['en' => 'Norlanka — Responsible apparel manufacturing']),
                'meta_description' => $j(['en' => 'Design, innovation and responsible sourcing at global scale.']),
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
                'en' => "We craft the world's apparel, responsibly.",
                'es' => 'Creamos la ropa del mundo, de forma responsable.',
                'ja' => '世界のアパレルを、責任を持って創る。',
                'zh' => '我们以负责任的方式，打造世界的服饰。',
            ],
            'subhead' => [
                'en' => 'Design. Innovation. Responsible sourcing — at global scale.',
                'es' => 'Diseño. Innovación. Abastecimiento responsable, a escala global.',
                'ja' => 'デザイン。革新。責任ある調達 — グローバルな規模で。',
                'zh' => '设计、创新、负责任的采购——全球规模。',
            ],
            // Kinetic-typography hero: pre + cycling word + post.
            'pre' => [
                'en' => "We craft the world's",
                'es' => 'Creamos',
                'ja' => '私たちは世界の',
                'zh' => '我们打造世界的',
            ],
            'post' => [
                'en' => 'responsibly.',
                'es' => 'del mundo, de forma responsable.',
                'ja' => 'を、責任を持って創る。',
                'zh' => '，以负责任的方式。',
            ],
            // Cycling words follow the real product portfolio (company profile).
            'rotators' => [
                ['en' => 'apparel', 'es' => 'moda', 'ja' => 'アパレル', 'zh' => '服饰'],
                ['en' => 'babywear', 'es' => 'ropa de bebé', 'ja' => 'ベビーウェア', 'zh' => '婴儿装'],
                ['en' => 'childrenswear', 'es' => 'ropa infantil', 'ja' => '子ども服', 'zh' => '童装'],
                ['en' => 'activewear', 'es' => 'ropa deportiva', 'ja' => 'アクティブウェア', 'zh' => '运动装'],
                ['en' => 'essentials', 'es' => 'básicos', 'ja' => '定番', 'zh' => '基础款'],
            ],
        ], 0);

        // ---- Pillars ----------------------------------------------------
        $pillars = $addSection('pillars', 'pillars', 1);
        $pillarData = [
            [
                'title' => ['en' => 'Responsible Sourcing', 'es' => 'Abastecimiento Responsable', 'ja' => '責任ある調達', 'zh' => '负责任的采购'],
                'text'  => [
                    'en' => 'Ethical, traceable supply chains and sustainable materials across every collection.',
                    'es' => 'Cadenas de suministro éticas y trazables, y materiales sostenibles en cada colección.',
                    'ja' => '倫理的でトレーサブルなサプライチェーンと、持続可能な素材をすべてのコレクションに。',
                    'zh' => '在每个系列中采用合乎道德、可追溯的供应链和可持续材料。',
                ],
            ],
            [
                'title' => ['en' => 'Design', 'es' => 'Diseño', 'ja' => 'デザイン', 'zh' => '设计'],
                'text'  => [
                    'en' => 'Trend research, CAD design and rapid sampling that bring ideas to life.',
                    'es' => 'Investigación de tendencias, diseño CAD y muestreo rápido que dan vida a las ideas.',
                    'ja' => 'トレンド研究、CADデザイン、迅速なサンプリングでアイデアを形に。',
                    'zh' => '趋势研究、CAD 设计与快速打样，让创意成真。',
                ],
            ],
            [
                'title' => ['en' => 'Innovation', 'es' => 'Innovación', 'ja' => '革新', 'zh' => '创新'],
                'text'  => [
                    'en' => "Automation, performance fabrics and an innovation lab shaping what's next.",
                    'es' => 'Automatización, tejidos de alto rendimiento y un laboratorio de innovación que define el futuro.',
                    'ja' => '自動化、高機能素材、そして次を形作るイノベーションラボ。',
                    'zh' => '自动化、高性能面料以及塑造未来的创新实验室。',
                ],
            ],
        ];
        foreach ($pillarData as $i => $p) {
            $addBlock($pillars, 'pillar', $p, $i);
        }

        // ---- Stats ------------------------------------------------------
        $stats = $addSection('stats', 'stats', 2);
        // Real figures from the Norlanka company profile (Feb 2026).
        $statData = [
            ['value' => '60', 'suffix' => 'M+', 'label' => ['en' => 'Garments shipped per year', 'es' => 'Prendas enviadas al año', 'ja' => '年間出荷着数', 'zh' => '每年出货服装（件）']],
            ['value' => '40', 'suffix' => '+', 'label' => ['en' => 'Factories in Sri Lanka & India', 'es' => 'Fábricas en Sri Lanka e India', 'ja' => 'スリランカ・インドの工場', 'zh' => '斯里兰卡与印度的工厂']],
            ['value' => '12000', 'suffix' => '+', 'label' => ['en' => 'Associates & workforce', 'es' => 'Colaboradores y personal', 'ja' => '従業員・ワークフォース', 'zh' => '员工与劳动力']],
            ['value' => '20', 'suffix' => '+', 'label' => ['en' => 'Global brands & retailers', 'es' => 'Marcas y minoristas globales', 'ja' => 'グローバルブランド・小売', 'zh' => '全球品牌与零售商']],
        ];
        foreach ($statData as $i => $s) {
            $addBlock($stats, 'stat', $s, $i);
        }

        // ---- CTA --------------------------------------------------------
        $cta = $addSection('cta', 'cta', 3);
        $addBlock($cta, 'cta', [
            'title'  => ['en' => 'Step into our Virtual Showroom', 'es' => 'Entra en nuestro Showroom Virtual', 'ja' => 'バーチャルショールームへ', 'zh' => '进入虚拟展厅'],
            'text'   => ['en' => 'Explore our collections in immersive 3D.', 'es' => 'Explora nuestras colecciones en 3D inmersivo.', 'ja' => '没入型3Dでコレクションを体験。', 'zh' => '在沉浸式 3D 中探索我们的系列。'],
            'button' => ['en' => 'Enter Showroom', 'es' => 'Entrar al Showroom', 'ja' => 'ショールームへ', 'zh' => '进入展厅'],
            'url'    => 'showroom',
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
                'title'         => $j(['en' => 'Norlanka Launch Film']),
                // Background launch film (swap for a CDN/S3 URL in production).
                'src_path'      => '/media/video/home-hero.mp4',
                'poster_path'   => '/media/video/home-hero-poster.jpg',
                'is_muted_loop' => 1,
                'status'        => 'published',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        } else {
            // Keep the background film + poster in sync on re-seed.
            $videos->where('key', 'home_launch')->update([
                'src_path'    => '/media/video/home-hero.mp4',
                'poster_path' => '/media/video/home-hero-poster.jpg',
                'updated_at'  => $now,
            ]);
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
