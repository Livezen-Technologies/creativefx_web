<?php

namespace Modules\News\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Newsroom starter content: the blueprint's eight editorial categories plus
 * articles built from the facts already published on the site (marketing deck
 * + ESG Strategy). Upserts by slug — never deletes — so editorial changes made
 * in Admin → News survive redeploys.
 */
class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->seedCategories($now);
        $this->seedPosts($now);
    }

    private function seedCategories(string $now): void
    {
        $j = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);

        $categories = [
            ['company-news',   ['en' => 'Company News', 'es' => 'Noticias corporativas', 'ja' => '会社ニュース', 'zh' => '公司新闻']],
            ['press-releases', ['en' => 'Press Releases', 'es' => 'Comunicados de prensa', 'ja' => 'プレスリリース', 'zh' => '新闻稿']],
            ['sustainability', ['en' => 'Sustainability', 'es' => 'Sostenibilidad', 'ja' => 'サステナビリティ', 'zh' => '可持续发展']],
            ['esg',            ['en' => 'ESG', 'es' => 'ESG', 'ja' => 'ESG', 'zh' => 'ESG']],
            ['csr',            ['en' => 'CSR', 'es' => 'RSC', 'ja' => 'CSR', 'zh' => '企业社会责任']],
            ['events',         ['en' => 'Events', 'es' => 'Eventos', 'ja' => 'イベント', 'zh' => '活动']],
            ['awards',         ['en' => 'Awards', 'es' => 'Premios', 'ja' => '受賞', 'zh' => '奖项']],
            ['innovation',     ['en' => 'Innovation', 'es' => 'Innovación', 'ja' => 'イノベーション', 'zh' => '创新']],
        ];

        $table = $this->db->table('news_categories');
        foreach ($categories as $i => [$slug, $name]) {
            $exists = $table->select('id')->where('slug', $slug)->get()->getRowArray();
            $table->resetQuery();
            if ($exists === null) {
                $this->db->table('news_categories')->insert([
                    'slug'       => $slug,
                    'name'       => $j($name),
                    'sort_order' => $i + 1,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function seedPosts(string $now): void
    {
        $j = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);

        // slug => id map for category references.
        $catId = [];
        foreach ($this->db->table('news_categories')->select('id, slug')->get()->getResultArray() as $row) {
            $catId[$row['slug']] = (int) $row['id'];
        }

        $posts = [
            [
                'slug'        => 'better-tomorrow-2028-roadmap',
                'category_id' => $catId['esg'] ?? null,
                'title'       => $j([
                    'en' => 'Better Tomorrow 2028: the next chapter of our ESG roadmap',
                    'es' => 'Better Tomorrow 2028: el próximo capítulo de nuestra hoja de ruta ESG',
                    'ja' => 'Better Tomorrow 2028 — ESGロードマップの新章',
                    'zh' => 'Better Tomorrow 2028：ESG 路线图的新篇章',
                ]),
                'excerpt' => $j([
                    'en' => 'Our refreshed sustainability strategy sets measurable 2028 targets against a 2024 baseline across three pillars — Protect the Environment, Together with People, and Trust in Everything We Do.',
                    'es' => 'Nuestra estrategia renovada fija objetivos medibles a 2028 sobre una base de 2024, en tres pilares: proteger el medio ambiente, junto a las personas y confianza en todo lo que hacemos.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Norlanka has launched Better Tomorrow 2028, the next phase of our environmental, social and governance roadmap. The strategy commits us to measurable 2028 targets against a 2024 baseline, organised under three pillars: Protect the Environment, Together with People, and Trust in Everything We Do.

## What the roadmap commits to

- Reduced emissions, water and waste intensity across our own facilities and partner factories
- 100% Tier-1 Higg/Worldly verification
- Deeper community programmes in education, health and biodiversity
- Transparent, verified reporting — building on our ISO 14064-1 greenhouse-gas certification

The roadmap builds on momentum from the last strategy cycle: a 630 kWp rooftop solar system at Trincomalee, USGBC LEED BD+C Gold certification for the plant, a Higg FEM score of 93, and community initiatives that have reached 2,882 beneficiaries to date.

Progress against every target will be shared on our Impact page as the programme rolls out.
TXT]),
                'image'        => '/media/impact/tree-planting.jpg',
                'tags'         => 'ESG,Roadmap 2028,Strategy',
                'author'       => 'Norlanka Sustainability Team',
                'status'       => 'published',
                'published_at' => '2026-02-05 09:00:00',
            ],
            [
                'slug'        => 'trincomalee-leed-gold',
                'category_id' => $catId['awards'] ?? null,
                'title'       => $j([
                    'en' => 'Our Trincomalee plant is certified USGBC LEED BD+C Gold',
                    'es' => 'Nuestra planta de Trincomalee obtiene la certificación USGBC LEED BD+C Gold',
                    'ja' => 'トリンコマリー工場がUSGBC LEED BD+C ゴールド認証を取得',
                    'zh' => '亭可马里工厂荣获 USGBC LEED BD+C 金级认证',
                ]),
                'excerpt' => $j([
                    'en' => 'The U.S. Green Building Council has certified our Trincomalee manufacturing facility LEED BD+C Gold — recognising the plant\'s energy, water and materials performance.',
                    'es' => 'El U.S. Green Building Council certificó nuestra planta de Trincomalee como LEED BD+C Gold, en reconocimiento a su desempeño en energía, agua y materiales.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Our Trincomalee manufacturing facility has been certified Gold under the U.S. Green Building Council's LEED Building Design and Construction (BD+C) programme.

LEED Gold recognises the plant's performance across energy efficiency, water use, materials and indoor environmental quality. The certification caps a multi-year investment in the site, including the 630 kWp rooftop solar system commissioned in 2022 that now generates around 760 MWh of renewable energy every year.

## Part of a bigger picture

The certification is one milestone on our Better Tomorrow roadmap, alongside ISO 14064-1 greenhouse-gas certification and a Higg FEM score of 93 for the same site. It reflects the simple idea behind our impact programme: the factories where our clothes are made should be places the planet and our people can be proud of.
TXT]),
                'image'        => '/media/impact/leed-plant.jpg',
                'tags'         => 'LEED Gold,Green building,Trincomalee',
                'author'       => 'Norlanka Communications',
                'status'       => 'published',
                'published_at' => '2024-09-12 09:00:00',
            ],
            [
                'slug'        => 'rooftop-solar-760-mwh',
                'category_id' => $catId['sustainability'] ?? null,
                'title'       => $j([
                    'en' => '630 kWp of rooftop solar: ~760 MWh of clean energy a year',
                    'es' => '630 kWp de energía solar en cubierta: ~760 MWh de energía limpia al año',
                    'ja' => '屋上太陽光630kWp — 年間約760MWhのクリーン電力',
                    'zh' => '630 kWp 屋顶光伏：每年约 760 MWh 清洁能源',
                ]),
                'excerpt' => $j([
                    'en' => 'The rooftop solar array at our Trincomalee plant generates around 760 MWh of renewable electricity annually — avoiding roughly 1,300 tCO2e of emissions.',
                    'es' => 'La instalación solar de nuestra planta de Trincomalee genera unos 760 MWh de electricidad renovable al año, evitando cerca de 1.300 tCO2e de emisiones.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Commissioned in 2022, the 630 kWp rooftop photovoltaic system at our Trincomalee plant now generates approximately 760 MWh of renewable electricity every year — avoiding around 1,300 tCO2e of emissions.

Solar is the backbone of the site's decarbonisation plan and one reason the facility earned USGBC LEED BD+C Gold certification. Under the Better Tomorrow 2028 roadmap we are extending renewable energy and energy-efficiency programmes across our operations and partner factories.

## Beyond the roof

Clean energy is only part of the site's environmental story: the plant scored 93 in Higg vFEM 2024 — including 100% on wastewater and chemicals management — and diverts production waste from landfill through recycling, composting and reuse.
TXT]),
                'image'        => '/media/impact/solar-roof.jpg',
                'tags'         => 'Solar,Renewable energy,Climate',
                'author'       => 'Norlanka Sustainability Team',
                'status'       => 'published',
                'published_at' => '2025-06-18 09:00:00',
            ],
            [
                'slug'        => 'higg-fem-93-trincomalee',
                'category_id' => $catId['esg'] ?? null,
                'title'       => $j([
                    'en' => 'Higg FEM 2024: Trincomalee scores 93, up from 78 in 2022',
                    'es' => 'Higg FEM 2024: Trincomalee alcanza 93 puntos, frente a 78 en 2022',
                    'ja' => 'Higg FEM 2024 — トリンコマリーが93点を獲得（2022年は78点）',
                    'zh' => 'Higg FEM 2024：亭可马里得分 93，高于 2022 年的 78',
                ]),
                'excerpt' => $j([
                    'en' => 'Our Trincomalee facility scored 93 in verified Higg FEM 2024 — with full marks on wastewater and chemicals management.',
                    'es' => 'Nuestra planta de Trincomalee obtuvo 93 puntos en el Higg FEM verificado de 2024, con puntuación máxima en aguas residuales y gestión química.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Our Trincomalee plant scored 93 in the verified Higg Facility Environmental Module (vFEM) for 2024 — a significant climb from 78 in 2022 — including 100% scores on wastewater and chemicals management.

The Higg FEM, delivered on the Worldly platform, is the apparel industry's standard measure of facility-level environmental performance, covering energy, water, waste, chemicals and emissions.

## What's next

Under Better Tomorrow 2028 we are targeting 100% Higg/Worldly verification across our Tier-1 supply chain, extending the discipline we apply to our own site to every partner factory that makes Norlanka product.
TXT]),
                'image'        => '/media/company/factory-aerial.jpg',
                'tags'         => 'Higg FEM,Worldly,Verification',
                'author'       => 'Norlanka Sustainability Team',
                'status'       => 'published',
                'published_at' => '2025-03-10 09:00:00',
            ],
            [
                'slug'        => 'mangrove-restoration-chilaw',
                'category_id' => $catId['sustainability'] ?? null,
                'title'       => $j([
                    'en' => '700 mangroves restored at Ambakandawila, Chilaw',
                    'es' => '700 manglares restaurados en Ambakandawila, Chilaw',
                    'ja' => 'チラウのアンバカンダウィラでマングローブ700本を再生',
                    'zh' => '在奇劳安巴坎达维拉修复 700 株红树林',
                ]),
                'excerpt' => $j([
                    'en' => 'Our biodiversity programme has restored 700 mangroves on Sri Lanka\'s west coast — a thriving habitat that shelters the shoreline and stores carbon.',
                    'es' => 'Nuestro programa de biodiversidad restauró 700 manglares en la costa oeste de Sri Lanka: un hábitat próspero que protege el litoral y almacena carbono.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Seven hundred mangroves now stand at Ambakandawila, Chilaw, restored through Norlanka's biodiversity programme with local communities on Sri Lanka's west coast.

Mangrove forests are among the most effective carbon sinks on the planet, and they shelter coastlines, fisheries and wildlife. The Chilaw restoration is part of a wider nature programme that has planted more than 8,000 saplings island-wide.

## Growing with communities

Alongside planting, the programme runs waste-segregation education in schools and coastal and mountain clean-ups — including the Sripada clean-up with the Central Environmental Authority — so the habitats we restore stay healthy for the long term.
TXT]),
                'image'        => '/media/impact/mangroves.jpg',
                'tags'         => 'Biodiversity,Mangroves,Community',
                'author'       => 'Norlanka Sustainability Team',
                'status'       => 'published',
                'published_at' => '2025-08-22 09:00:00',
            ],
            [
                'slug'        => 'grade-5-scholarship-programme',
                'category_id' => $catId['csr'] ?? null,
                'title'       => $j([
                    'en' => 'Scholarship seminars and school supplies for Trincomalee students',
                    'es' => 'Seminarios de becas y útiles escolares para estudiantes de Trincomalee',
                    'ja' => 'トリンコマリーの生徒たちへ奨学セミナーと学用品を支援',
                    'zh' => '为亭可马里学生提供奖学金辅导与学习用品',
                ]),
                'excerpt' => $j([
                    'en' => 'Since 2023 our Grade 5 scholarship seminars have supported students across Trincomalee, and each year around 300 children of team members receive school supplies.',
                    'es' => 'Desde 2023, nuestros seminarios de becas de 5.º grado apoyan a estudiantes de Trincomalee, y cada año unos 300 hijos de empleados reciben útiles escolares.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Education is the heart of our community programme. Since 2023, Norlanka has run Grade 5 scholarship seminars for students across Trincomalee, helping children prepare for the national examination that opens doors to Sri Lanka's leading schools.

Each year the programme also puts school supplies in the hands of around 300 children of our own team members, easing the back-to-school burden on families.

## Together with people

These initiatives sit within the Together with People pillar of our Better Tomorrow roadmap, which has reached 2,882 community beneficiaries to date — from student seminars to patient meals for the National Cancer Institute.
TXT]),
                'image'        => '/media/impact/education.jpg',
                'tags'         => 'Education,Community,Trincomalee',
                'author'       => 'Norlanka Communications',
                'status'       => 'published',
                'published_at' => '2026-01-15 09:00:00',
            ],
            [
                'slug'        => 'great-place-to-work',
                'category_id' => $catId['company-news'] ?? null,
                'title'       => $j([
                    'en' => 'Norlanka certified a Great Place to Work',
                    'es' => 'Norlanka certificada como Great Place to Work',
                    'ja' => 'ノルランカ、Great Place to Work認定を取得',
                    'zh' => 'Norlanka 荣获卓越职场（Great Place to Work）认证',
                ]),
                'excerpt' => $j([
                    'en' => 'Norlanka has earned Great Place to Work certification — independent recognition of the trust, pride and camaraderie our people report.',
                    'es' => 'Norlanka obtuvo la certificación Great Place to Work: un reconocimiento independiente a la confianza, el orgullo y el compañerismo de nuestro equipo.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Norlanka has been certified a Great Place to Work, the global benchmark for workplace culture based directly on what employees say about their experience.

The certification arrived in the same year our Trincomalee plant switched on its rooftop solar system — a fitting pairing, because our belief is that responsible manufacturing starts with how we treat the people who make it happen.

## Why it matters

From merchandising in Colombo to the sewing floor in Trincomalee, more than 250 people design, develop and deliver over five million garments a year for international brands. Great Place to Work certification tells our customers — and future colleagues browsing our careers page — what our team already knows about working here.
TXT]),
                'image'        => '/media/company/head-office.jpg',
                'tags'         => 'Great Place to Work,Culture,People',
                'author'       => 'Norlanka Communications',
                'status'       => 'published',
                'published_at' => '2022-11-03 09:00:00',
            ],
            [
                'slug'        => 'blood-donation-cancer-institute',
                'category_id' => $catId['events'] ?? null,
                'title'       => $j([
                    'en' => 'Blood donation camps and 1,000+ patient meals for the National Cancer Institute',
                    'es' => 'Campañas de donación de sangre y más de 1.000 comidas para el Instituto Nacional del Cáncer',
                    'ja' => '献血キャンプと国立がん研究所への1,000食以上の患者食支援',
                    'zh' => '献血活动及为国家癌症研究所提供 1,000 多份病患餐食',
                ]),
                'excerpt' => $j([
                    'en' => 'Our community health programme has run blood donation camps since 2022 and provided food trolleys and more than 1,000 patient meals at the National Cancer Institute.',
                    'es' => 'Nuestro programa de salud comunitaria organiza campañas de donación de sangre desde 2022 y ha entregado más de 1.000 comidas a pacientes del Instituto Nacional del Cáncer.',
                ]),
                'body' => $j(['en' => <<<'TXT'
Norlanka team members have rolled up their sleeves — literally — at company blood donation camps every year since 2022, supporting Sri Lanka's national blood bank.

At the National Cancer Institute in Maharagama, our community health programme funds food trolleys and has served more than 1,000 patient meals, bringing a little comfort to families during treatment.

## An open invitation

These events are organised by our own volunteers, and each camp welcomes participants from neighbouring businesses and communities. Follow our newsroom for dates of the next donation drive.
TXT]),
                'image'        => '/media/impact/blood-donation.jpg',
                'tags'         => 'Health,Volunteering,Community',
                'author'       => 'Norlanka Communications',
                'status'       => 'published',
                'published_at' => '2025-10-08 09:00:00',
            ],
        ];

        $table = $this->db->table('news_posts');
        foreach ($posts as $post) {
            $exists = $table->select('id')->where('slug', $post['slug'])->get()->getRowArray();
            $table->resetQuery();
            if ($exists === null) {
                $this->db->table('news_posts')->insert($post + ['created_at' => $now, 'updated_at' => $now]);
            }
            // Existing rows are left untouched — editors own them after first seed.
        }
    }
}
