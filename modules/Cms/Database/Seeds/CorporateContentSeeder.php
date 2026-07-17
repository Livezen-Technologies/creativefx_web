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

    /** Build a locale-map, dropping null locales. */
    private function loc(string $en, ?string $es = null, ?string $ja = null, ?string $zh = null): array
    {
        return array_filter(['en' => $en, 'es' => $es, 'ja' => $ja, 'zh' => $zh], static fn ($v) => $v !== null);
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
            'our-story'     => $this->ourStory(),
            'our-expertise' => $this->ourExpertise(),
            'manufacturing' => $this->manufacturing(),
            'impact'        => $this->impact(),
            'careers'       => $this->careers(),
            'contact'       => $this->contact(),
            // 'showroom' is now served by the Showroom module (3D experience).
        ];
    }

    private function hero(array $eyebrow, array $title, array $subtitle): array
    {
        return ['key' => 'hero', 'type' => 'hero', 'blocks' => [
            ['pagehero', ['eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle]],
        ]];
    }

    private function ctaContact(): array
    {
        return ['key' => 'cta', 'type' => 'cta', 'blocks' => [
            ['cta', [
                'title'  => $this->loc('Let’s build something responsibly', null, '責任を持って共に創りましょう', '让我们以负责任的方式共创'),
                'text'   => $this->loc('Partner with Norlanka for design, development and manufacturing at scale.'),
                'button' => $this->loc('Contact us', 'Contáctanos', 'お問い合わせ', '联系我们'),
                'url'    => 'contact',
            ]],
        ]];
    }

    private function ourStory(): array
    {
        return [
            'title' => $this->loc('Our Story', 'Nuestra Historia', '私たちの物語', '我们的故事'),
            'meta'  => $this->loc('Norlanka — company profile, vision, journey, leadership and global presence.'),
            'sections' => [
                $this->hero(
                    $this->loc('Our Story', 'Nuestra Historia', '私たちの物語', '我们的故事'),
                    $this->loc('Crafting apparel the world trusts', 'Creando ropa en la que el mundo confía', '世界が信頼するアパレルづくり', '打造世界信赖的服饰'),
                    $this->loc('Three decades of responsible sourcing, design and innovation.')
                ),
                ['key' => 'profile', 'type' => 'content', 'blocks' => [
                    ['two_column', [
                        'eyebrow' => $this->loc('Company profile', 'Perfil', '会社概要', '公司简介'),
                        'title'   => $this->loc('Who we are', 'Quiénes somos', '私たちについて', '关于我们'),
                        'body'    => $this->loc(
                            'Norlanka is a full-service apparel manufacturer, providing solutions from design to delivery for over 20 international fashion brands and retailers. We work with 40+ partner factories across Sri Lanka and India, ship over 60 million garments a year, and are part of PDS Limited — a global, design-led manufacturing platform.',
                            'Norlanka es un fabricante de servicio completo, con soluciones del diseño a la entrega para más de 20 marcas internacionales. Trabajamos con 40+ fábricas asociadas en Sri Lanka e India, enviamos más de 60 millones de prendas al año y formamos parte de PDS Limited.'
                        ),
                        'items'   => [
                            $this->loc('USD 100+ Mn revenue · 12,000+ associates'),
                            $this->loc('6,000+ machines · 5 Mn sewing units per month'),
                            $this->loc('SEDEX, GOTS, GRS, HIGG & ISO 14064-1 compliant'),
                        ],
                    ]],
                ]],
                ['key' => 'hq-photo', 'type' => 'image', 'blocks' => [
                    ['image', [
                        'src' => '/media/company/hq-tower.jpg',
                        'alt' => $this->loc('Norlanka head office tower, Colombo'),
                        'caption' => $this->loc('The Norlanka head office in Colombo, Sri Lanka — home to 250+ head-office employees.'),
                    ]],
                ]],
                ['key' => 'vision', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('Vision & Mission', 'Visión y Misión', 'ビジョンとミッション', '愿景与使命'),
                        'items' => [
                            ['title' => $this->loc('Vision', 'Visión', 'ビジョン', '愿景'), 'text' => $this->loc('To be the most globally sought after, trusted fashion solutions provider.')],
                            ['title' => $this->loc('Mission', 'Misión', 'ミッション', '使命'), 'text' => $this->loc('Providing stakeholders with growth opportunities through innovative design, and the highest value through excellent customer service.')],
                        ],
                    ]],
                ]],
                ['key' => 'journey', 'type' => 'timeline', 'blocks' => [
                    ['timeline', [
                        'title' => $this->loc('Our journey', 'Nuestra trayectoria', '私たちの歩み', '我们的历程'),
                        'items' => [
                            ['year' => $this->loc('2018'), 'title' => $this->loc('Pioneering 3D apparel', '3D pionero', '3Dアパレルの先駆け', '开创 3D 服装设计'), 'text' => $this->loc('Norlanka moves to the forefront of 3D apparel design in the industry.')],
                            ['year' => $this->loc('2022'), 'title' => $this->loc('Solar-powered manufacturing', 'Fabricación solar', '太陽光発電の導入', '太阳能制造'), 'text' => $this->loc('A 630 kWp solar roof is commissioned at Trincomalee; Great Place to Work certified.')],
                            ['year' => $this->loc('2024'), 'title' => $this->loc('Verified climate accounting', 'Contabilidad climática verificada', '検証済み気候会計', '经验证的气候核算'), 'text' => $this->loc('ISO 14064-1 greenhouse-gas certification and a Higg FEM score of 93.')],
                            ['year' => $this->loc('2026'), 'title' => $this->loc('Global scale', 'Escala global', 'グローバル規模へ', '全球规模'), 'text' => $this->loc('USD 100+ Mn revenue and 60+ million garments shipped a year across Sri Lanka and India.')],
                        ],
                    ]],
                ]],
                ['key' => 'values', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('We believe in', 'Creemos en', '私たちの信条', '我们的信念'),
                        'items' => [
                            ['title' => $this->loc('Trust, Integrity & Ethics', 'Confianza, integridad y ética', '信頼・誠実・倫理', '信任、诚信与道德'), 'text' => $this->loc('Doing what is right, always — in every relationship and decision.')],
                            ['title' => $this->loc('People First', 'Las personas primero', '人を第一に', '员工优先'), 'text' => $this->loc('Our people are the heart of everything we do.')],
                            ['title' => $this->loc('Social Responsibility', 'Responsabilidad social', '社会的責任', '社会责任'), 'text' => $this->loc('Building stronger, more sustainable communities.')],
                            ['title' => $this->loc('Customer Centricity', 'Centrados en el cliente', '顧客中心', '以客户为中心'), 'text' => $this->loc('We build around our customers’ success.')],
                            ['title' => $this->loc('Entrepreneurial Spirit', 'Espíritu emprendedor', '起業家精神', '企业家精神'), 'text' => $this->loc('Agility and initiative in everything we take on.')],
                            ['title' => $this->loc('Transparency & Teamwork', 'Transparencia y trabajo en equipo', '透明性とチームワーク', '透明与团队协作'), 'text' => $this->loc('Collaboration and openness with partners and people.')],
                        ],
                    ]],
                ]],
                ['key' => 'leadership', 'type' => 'leadership', 'blocks' => [
                    ['leadership', [
                        'title' => $this->loc('Leadership', 'Liderazgo', 'リーダーシップ', '领导团队'),
                        'items' => [
                            ['name' => $this->loc('Chief Executive Officer'), 'role' => $this->loc('CEO message', 'Mensaje del CEO', 'CEOメッセージ', 'CEO 致辞'), 'message' => $this->loc('Our people and our responsibility to the planet drive every decision we make.')],
                            ['name' => $this->loc('Pallak'), 'role' => $this->loc('Leadership message', 'Mensaje', 'メッセージ', '寄语'), 'message' => $this->loc('Design and innovation are how we create lasting value for our partners.')],
                            ['name' => $this->loc('Leadership Team'), 'role' => $this->loc('Across functions', 'Equipo', 'リーダーシップチーム', '管理团队'), 'message' => $this->loc('A global team uniting craftsmanship, technology and sustainability.')],
                        ],
                    ]],
                ]],
                ['key' => 'presence', 'type' => 'map', 'blocks' => [
                    ['map', [
                        'title' => $this->loc('Global presence', 'Presencia global', 'グローバルな拠点', '全球布局'),
                        'intro' => $this->loc('Manufacturing, design and customer teams across five countries.'),
                        'items' => [
                            ['region' => $this->loc('Sri Lanka'), 'detail' => $this->loc('Headquarters, factories & sample room')],
                            ['region' => $this->loc('India'), 'detail' => $this->loc('Manufacturing — Tirupur & Delhi')],
                            ['region' => $this->loc('United Kingdom'), 'detail' => $this->loc('Design studio — Leicester')],
                            ['region' => $this->loc('USA'), 'detail' => $this->loc('Sales & marketing')],
                            ['region' => $this->loc('Hong Kong'), 'detail' => $this->loc('Product development')],
                        ],
                    ]],
                ]],
                ['key' => 'partners', 'type' => 'partners', 'blocks' => [
                    ['partners', [
                        'title' => $this->loc('Compliance & certifications', 'Cumplimiento y certificaciones', 'コンプライアンスと認証', '合规与认证'),
                        'intro' => $this->loc('Held to the industry\'s highest standards across manufacturing, sampling and supply chain.'),
                        'items' => ['SEDEX', 'GOTS', 'GRS', 'HIGG', 'ISO 14064-1', 'LEED Gold'],
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }

    private function ourExpertise(): array
    {
        return [
            'title' => $this->loc('Our Expertise', 'Experiencia', '専門分野', '专业能力'),
            'meta'  => $this->loc('Product categories, design & development, end-to-end solutions and materials.'),
            'sections' => [
                $this->hero(
                    $this->loc('Our Expertise', 'Experiencia', '専門分野', '专业能力'),
                    $this->loc('From first sketch to final stitch', 'Del primer boceto a la última puntada', '最初のスケッチから最後の一針まで', '从初稿到成衣'),
                    $this->loc('Full-service design, development and manufacturing across 13 product categories.')
                ),
                ['key' => 'categories', 'type' => 'categories', 'blocks' => [
                    ['category_grid', [
                        'title' => $this->loc('Product categories', 'Categorías de producto', '製品カテゴリ', '产品类别'),
                        'intro' => $this->loc('Specialist capability across babywear, knits, woven, activewear and more.'),
                    ]],
                ]],
                ['key' => 'design', 'type' => 'features', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Design & development', 'Diseño y desarrollo', 'デザインと開発', '设计与开发'),
                        'items' => [
                            ['title' => $this->loc('Trend Research'), 'text' => $this->loc('Insight-led seasonal direction.')],
                            ['title' => $this->loc('CAD Design'), 'text' => $this->loc('Digital design and tech packs.')],
                            ['title' => $this->loc('Sampling'), 'text' => $this->loc('Fast, accurate prototype sampling.')],
                            ['title' => $this->loc('Product Development'), 'text' => $this->loc('From concept to production-ready.')],
                            ['title' => $this->loc('Fit Testing'), 'text' => $this->loc('Rigorous fit and wear validation.')],
                            ['title' => $this->loc('Innovation Lab'), 'text' => $this->loc('New materials and techniques.')],
                        ],
                    ]],
                ]],
                ['key' => 'workflow', 'type' => 'process', 'blocks' => [
                    ['process_steps', [
                        'title' => $this->loc('End-to-end solutions', 'Soluciones integrales', 'エンドツーエンドのソリューション', '端到端解决方案'),
                        'items' => [
                            ['title' => $this->loc('Research')], ['title' => $this->loc('Design')],
                            ['title' => $this->loc('Development')], ['title' => $this->loc('Sampling')],
                            ['title' => $this->loc('Manufacturing')], ['title' => $this->loc('Quality Assurance')],
                            ['title' => $this->loc('Logistics')], ['title' => $this->loc('Delivery')],
                        ],
                    ]],
                ]],
                ['key' => 'materials', 'type' => 'features', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Materials expertise', 'Materiales', '素材の専門知識', '材料专长'),
                        'items' => [
                            ['title' => $this->loc('Cotton'), 'text' => $this->loc('Soft, breathable, versatile staples.')],
                            ['title' => $this->loc('Organic Cotton'), 'text' => $this->loc('Certified organic fibres.')],
                            ['title' => $this->loc('Recycled Fibers'), 'text' => $this->loc('Lower-impact recycled inputs.')],
                            ['title' => $this->loc('Bamboo'), 'text' => $this->loc('Naturally soft, sustainable.')],
                            ['title' => $this->loc('Performance Fabrics'), 'text' => $this->loc('Engineered for movement.')],
                            ['title' => $this->loc('Sustainable Materials'), 'text' => $this->loc('Responsible material library.')],
                        ],
                    ]],
                ]],
                ['key' => 'qa', 'type' => 'process', 'blocks' => [
                    ['process_steps', [
                        'title' => $this->loc('Quality assurance', 'Control de calidad', '品質保証', '质量保证'),
                        'items' => [
                            ['title' => $this->loc('Incoming Inspection')], ['title' => $this->loc('Inline Inspection')],
                            ['title' => $this->loc('Production QC')], ['title' => $this->loc('Final Audit')],
                            ['title' => $this->loc('Shipment Release')],
                        ],
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }

    private function manufacturing(): array
    {
        return [
            'title' => $this->loc('Manufacturing', 'Fabricación', '製造', '制造'),
            'meta'  => $this->loc('Facilities, machinery, automation, production capacity and sustainability.'),
            'sections' => [
                $this->hero(
                    $this->loc('Manufacturing', 'Fabricación', '製造', '制造'),
                    $this->loc('Scale, precision and automation', 'Escala, precisión y automatización', '規模、精度、自動化', '规模、精度与自动化'),
                    $this->loc('Modern facilities and advanced machinery built for responsible production.')
                ),
                ['key' => 'capabilities', 'type' => 'features', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Capabilities', 'Capacidades', '生産能力', '生产能力'),
                        'items' => [
                            ['title' => $this->loc('Facilities'), 'text' => $this->loc('Modern, audited manufacturing sites.')],
                            ['title' => $this->loc('Machinery'), 'text' => $this->loc('Advanced, well-maintained equipment.')],
                            ['title' => $this->loc('Technology'), 'text' => $this->loc('Digital planning and traceability.')],
                            ['title' => $this->loc('Automation'), 'text' => $this->loc('Automated lines for consistency.')],
                            ['title' => $this->loc('Production Lines'), 'text' => $this->loc('Flexible, scalable capacity.')],
                            ['title' => $this->loc('Sustainability'), 'text' => $this->loc('Energy, water and waste programs.')],
                        ],
                    ]],
                ]],
                ['key' => 'floor-photo', 'type' => 'image', 'blocks' => [
                    ['image', [
                        'src' => '/media/company/sewing-floor.jpg',
                        'alt' => $this->loc('Sewing floor at a Norlanka partner factory'),
                        'caption' => $this->loc('World-class factories across Sri Lanka and India, backed by dedicated quality-assurance teams at every site.'),
                    ]],
                ]],
                ['key' => 'capacity', 'type' => 'metrics', 'blocks' => [
                    ['metrics', [
                        'title' => $this->loc('Production capacity', 'Capacidad de producción', '生産能力', '产能'),
                        'items' => [
                            ['value' => '40', 'suffix' => '+', 'label' => $this->loc('Factories — Sri Lanka & India', 'Fábricas', '工場（スリランカ・インド）', '工厂（斯里兰卡与印度）')],
                            ['value' => '12000', 'suffix' => '+', 'label' => $this->loc('Associates & workforce', 'Colaboradores', '従業員・ワークフォース', '员工与劳动力')],
                            ['value' => '6000', 'suffix' => '+', 'label' => $this->loc('Machines', 'Máquinas', 'ミシン・機械', '设备')],
                            ['value' => '5', 'suffix' => 'M', 'label' => $this->loc('Sewing units per month', 'Unidades al mes', '月間縫製数', '每月缝制件数')],
                        ],
                    ]],
                ]],
                ['key' => 'tours', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Factory tours', 'Visitas', '工場見学', '工厂参观'),
                        'title'   => $this->loc('Factory videos & 360° tours', 'Vídeos y recorridos 360°', '工場動画と360°ツアー', '工厂视频与360°参观'),
                        'text'    => $this->loc('Immersive factory videos and 360° virtual tours are coming soon — see our facilities, machinery and people in action.'),
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }

    /**
     * Our Impact — content from the Norlanka ESG Strategy deck ("Better Tomorrow"):
     * real pillars, 2028 KPI targets, initiatives and photography.
     */
    private function impact(): array
    {
        return [
            'title' => $this->loc('Our Impact', 'Impacto', '私たちの影響', '我们的影响'),
            'meta'  => $this->loc('Better Tomorrow — Norlanka\'s ESG strategy aligned to the UN SDGs.'),
            'sections' => [
                $this->hero(
                    $this->loc('Our Impact', 'Impacto', '私たちの影響', '我们的影响'),
                    $this->loc('Better Tomorrow', 'Un mañana mejor', 'より良い明日', '更美好的明天'),
                    $this->loc('Our ESG strategy is aligned to the United Nations Sustainable Development Goals — turning purpose into action and responsibility into measurable results.',
                        'Nuestra estrategia ESG está alineada con los ODS de la ONU: convertimos el propósito en acción y la responsabilidad en resultados medibles.',
                        '私たちのESG戦略は国連SDGsに整合し、目的を行動に、責任を測定可能な成果に変えていきます。',
                        '我们的 ESG 战略与联合国可持续发展目标保持一致——把使命化为行动，把责任化为可衡量的成果。')
                ),
                ['key' => 'statement', 'type' => 'richtext', 'blocks' => [
                    ['richtext', [
                        'title' => $this->loc('A better tomorrow begins today', 'Un mañana mejor empieza hoy', 'より良い明日は今日から', '更美好的明天，始于今天'),
                        'text'  => $this->loc('We believe that creating a better tomorrow begins with the actions we take today. Through this strategy we aim to drive sustainable growth, build trust with our stakeholders and create meaningful impact — ensuring our success contributes to a more equitable, resilient and environmentally conscious world. Together, we\'re not just building a better business; we\'re helping create a better tomorrow.'),
                    ]],
                ]],
                ['key' => 'pillars', 'type' => 'esg', 'blocks' => [
                    ['esg_pillars', [
                        'title' => $this->loc('Pillars of Better Tomorrow', 'Pilares de un mañana mejor', 'Better Tomorrowの柱', 'Better Tomorrow 三大支柱'),
                        'intro' => $this->loc('Three commitments with measurable 2028 targets against a 2024 baseline.',
                            'Tres compromisos con objetivos medibles a 2028 sobre una base de 2024.',
                            '2024年を基準とし、2028年に向けた測定可能な目標を掲げる3つのコミットメント。',
                            '三大承诺，以 2024 年为基线、以 2028 年为目标，皆可衡量。'),
                        'pillars' => [
                            ['title' => $this->loc('Protect Our Environment', 'Proteger el medio ambiente', '環境を守る', '保护环境'), 'items' => [
                                $this->loc('Emissions −10% (Scope 1 & 2, kgCO2e/SM)', 'Emisiones −10% (Alcance 1 y 2)', '排出量−10%（スコープ1・2）', '排放 −10%（范围1和2）'),
                                $this->loc('Domestic water use −10% per employee-day', 'Agua −10% por empleado/día', '水使用量−10%（従業員1人/日）', '人均日用水 −10%'),
                                $this->loc('Zero waste to landfill — 95% diverted', 'Cero residuos a vertedero: 95% desviado', '埋立ゼロ — 95%転換', '零填埋——95% 转化利用'),
                                $this->loc('Double our biodiversity footprint via reforestation', 'Duplicar la huella de biodiversidad', '再植林で生物多様性面積を倍増', '通过再造林使生物多样性面积翻倍'),
                            ]],
                            ['title' => $this->loc('Together With People', 'Junto a las personas', '人とともに', '与员工同行'), 'items' => [
                                $this->loc('30% of leadership positions held by women', '30% de liderazgo femenino', '管理職の30%を女性に', '30% 领导岗位由女性担任'),
                                $this->loc('20 training hours per employee', '20 horas de formación por empleado', '従業員1人あたり研修20時間', '人均 20 小时培训'),
                                $this->loc('5,000 community beneficiaries', '5.000 beneficiarios comunitarios', '地域の受益者5,000人', '5,000 名社区受益者'),
                            ]],
                            ['title' => $this->loc('Trust In Everything', 'Confianza en todo', 'すべてに信頼を', '处处可信'), 'items' => [
                                $this->loc('100% Tier-1 Higg/Worldly verification', 'Verificación Higg del 100% en Tier-1', 'Tier-1のHigg検証100%', 'Tier-1 Higg 验证 100%'),
                                $this->loc('75% Tier-2 facilities verified', '75% de instalaciones Tier-2 verificadas', 'Tier-2施設の75%を検証', 'Tier-2 工厂验证 75%'),
                                $this->loc('Science-based targets — net-zero by 2050 (PDS)', 'Objetivos científicos: cero neto 2050', '科学的根拠に基づく目標 — 2050年ネットゼロ', '科学碳目标——2050 年净零（PDS）'),
                            ]],
                        ],
                    ]],
                ]],
                ['key' => 'metrics', 'type' => 'metrics', 'blocks' => [
                    ['metrics', [
                        'title' => $this->loc('Progress', 'Progreso', '進捗', '进展'),
                        'items' => [
                            ['value' => '93', 'suffix' => '', 'label' => $this->loc('Higg FEM score 2024 — Trincomalee', 'Puntuación Higg FEM 2024', 'Higg FEMスコア2024', '2024 年 Higg FEM 得分')],
                            ['value' => '760', 'suffix' => ' MWh', 'label' => $this->loc('Solar energy generated per year', 'Energía solar anual', '年間太陽光発電量', '每年太阳能发电量')],
                            ['value' => '8000', 'suffix' => '+', 'label' => $this->loc('Saplings planted island-wide', 'Plantones sembrados', '島内に植樹した苗木', '全岛种植树苗')],
                            ['value' => '2882', 'suffix' => '', 'label' => $this->loc('Community beneficiaries to date', 'Beneficiarios comunitarios', '地域の受益者数', '社区受益者人数')],
                        ],
                    ]],
                ]],
                ['key' => 'journey', 'type' => 'timeline', 'blocks' => [
                    ['timeline', [
                        'title' => $this->loc('Our sustainability journey', 'Nuestro camino sostenible', 'サステナビリティの歩み', '我们的可持续之路'),
                        'items' => [
                            ['year' => $this->loc('2021'), 'title' => $this->loc('Sustainability Roadmap 2025', 'Hoja de ruta 2025', 'サステナビリティ・ロードマップ2025', '2025 可持续发展路线图'),
                             'text' => $this->loc('Our first structured roadmap sets measurable environmental and social goals.')],
                            ['year' => $this->loc('2022'), 'title' => $this->loc('Solar PV roof at Trincomalee', 'Techo solar en Trincomalee', 'トリンコマリーに太陽光発電', '亭可马里光伏屋顶'),
                             'text' => $this->loc('A 630 kWp rooftop system generating ~760 MWh of renewable energy a year.')],
                            ['year' => $this->loc('2024'), 'title' => $this->loc('LEED Gold certification', 'Certificación LEED Gold', 'LEEDゴールド認証', 'LEED 金级认证'),
                             'text' => $this->loc('Our Trincomalee plant is certified USGBC LEED BD+C Gold.')],
                            ['year' => $this->loc('2025'), 'title' => $this->loc('Sri Lanka\'s first zero-landfilling organization', 'Primera organización sin vertederos de Sri Lanka', 'スリランカ初のゼロ埋立企業', '斯里兰卡首家零填埋企业'),
                             'text' => $this->loc('Waste diverted from landfill through recycling, composting and reuse — and our Roadmap 2028 launches the next chapter.')],
                        ],
                    ]],
                ]],
                ['key' => 'protect-photo', 'type' => 'image', 'blocks' => [
                    ['image', [
                        'src' => '/media/impact/solar-roof.jpg',
                        'alt' => $this->loc('Rooftop solar array at Norlanka Manufacturing, Trincomalee'),
                        'caption' => $this->loc('630 kWp of rooftop solar at our Trincomalee plant — around 1,300 tCO2e of emissions avoided.'),
                    ]],
                ]],
                ['key' => 'protect', 'type' => 'cards', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Protect — our environment', 'Proteger el medio ambiente', '環境を守る取り組み', '保护环境行动'),
                        'intro' => $this->loc('Flagship environmental programmes across energy, water, waste and biodiversity.'),
                        'items' => [
                            ['title' => $this->loc('Solar PV system'), 'text' => $this->loc('630 kWp installed at Trincomalee, generating ~760 MWh of renewable energy annually.')],
                            ['title' => $this->loc('Rainwater harvesting'), 'text' => $this->loc('A 3,150 m² catchment collects ~2,625 m³ a year, with 60% recharged to aquifers.')],
                            ['title' => $this->loc('Mangrove restoration'), 'text' => $this->loc('700 mangroves restored at Ambakandawila, Chilaw — a thriving coastal habitat that protects the shoreline.')],
                            ['title' => $this->loc('Roots for Tomorrow'), 'text' => $this->loc('Tree planting across the island — 8,000+ saplings — plus One Plant for a Million Garments.')],
                            ['title' => $this->loc('Biodiversity partnership'), 'text' => $this->loc('An MOU with Kelani Valley Plantations to plant 1,000 native trees in the We Oya catchment.')],
                            ['title' => $this->loc('Green habits & clean-ups'), 'text' => $this->loc('Waste-segregation education in schools and the Sripada clean-up with the Central Environmental Authority.')],
                        ],
                    ]],
                ]],
                ['key' => 'together-photo', 'type' => 'image', 'blocks' => [
                    ['image', [
                        'src' => '/media/impact/education.jpg',
                        'alt' => $this->loc('Students supported by Norlanka education programmes'),
                        'caption' => $this->loc('Grade 5 scholarship seminars and school-supply donations support students across Trincomalee.'),
                    ]],
                ]],
                ['key' => 'together', 'type' => 'cards', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Together — with people', 'Junto a las personas', '人とともに', '与员工同行'),
                        'intro' => $this->loc('Community programmes that reached 2,882 beneficiaries this year, on the way to 5,000.'),
                        'items' => [
                            ['title' => $this->loc('Style for Change'), 'text' => $this->loc('Annual pre-loved clothing donations — from 700 beneficiaries in Ampara to 2,000+ across Nuwara Eliya.')],
                            ['title' => $this->loc('Children\'s education'), 'text' => $this->loc('Grade 5 scholarship seminars since 2023 and school supplies for ~300 children of team members each year.')],
                            ['title' => $this->loc('Community health'), 'text' => $this->loc('Food trolleys and 1,000+ patient meals for the National Cancer Institute; blood donation camps since 2022.')],
                            ['title' => $this->loc('Crisis response'), 'text' => $this->loc('USD 20,000 contributed with PDS Limited to the Rebuilding Sri Lanka Fund after Cyclone Ditwah.')],
                            ['title' => $this->loc('Future apparel talent'), 'text' => $this->loc('Fabric donations and fashion-show sponsorships with the University of Moratuwa.')],
                            ['title' => $this->loc('Women in leadership'), 'text' => $this->loc('28.1% of leadership roles held by women — on track to our 30% target.')],
                        ],
                    ]],
                ]],
                ['key' => 'trust-photo', 'type' => 'image', 'blocks' => [
                    ['image', [
                        'src' => '/media/impact/mangroves.jpg',
                        'alt' => $this->loc('Mangrove restoration at Ambakandawila, Chilaw'),
                        'caption' => $this->loc('Mangrove restoration at Ambakandawila, Chilaw — 700 saplings creating a sustainable coastal ecosystem.'),
                    ]],
                ]],
                ['key' => 'trust', 'type' => 'cards', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Trust — in everything', 'Confianza en todo', 'すべてに信頼を', '处处可信'),
                        'intro' => $this->loc('Governance, verification and recognition that hold us accountable.'),
                        'items' => [
                            ['title' => $this->loc('Higg FEM verification'), 'text' => $this->loc('Trincomalee scored 93 in vFEM 2024 — up from 78 in 2022 — with 100% on wastewater and chemicals.')],
                            ['title' => $this->loc('Science-based targets'), 'text' => $this->loc('With PDS Limited: −42% Scope 1 & 2 and −25% Scope 3 by FY2030; net-zero by FY2050.')],
                            ['title' => $this->loc('Presidential Environmental Awards'), 'text' => $this->loc('Merit Award, Apparel Industry Category, 2025 — presented at BMICH, Colombo.')],
                            ['title' => $this->loc('GHG verification'), 'text' => $this->loc('Independently verified greenhouse-gas inventory (ISO 14064-1).')],
                            ['title' => $this->loc('Responsible Care'), 'text' => $this->loc('Member of the Lanka Responsible Care Council for safe chemical management and ethical operations.')],
                            ['title' => $this->loc('LEED Gold plant'), 'text' => $this->loc('Our Trincomalee facility is USGBC LEED BD+C Gold certified.')],
                        ],
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }

    private function careers(): array
    {
        return [
            'title' => $this->loc('Careers', 'Empleo', '採用情報', '招贤纳士'),
            'meta'  => $this->loc('Life at Norlanka — culture, benefits, learning and programs.'),
            'sections' => [
                $this->hero(
                    $this->loc('Careers', 'Empleo', '採用情報', '招贤纳士'),
                    $this->loc('Life at Norlanka', 'La vida en Norlanka', 'ノーランカでの働き方', '诺兰卡的工作生活'),
                    $this->loc('Build a career with a responsible, global apparel leader.')
                ),
                ['key' => 'culture', 'type' => 'content', 'blocks' => [
                    ['two_column', [
                        'eyebrow' => $this->loc('Culture', 'Cultura', '文化', '文化'),
                        'title'   => $this->loc('Culture & values', 'Cultura y valores', '文化と価値観', '文化与价值观'),
                        'body'    => $this->loc('We grow our people through learning, wellbeing and a culture of collaboration and responsibility.'),
                        'items'   => [$this->loc('Collaborative teams'), $this->loc('Global opportunities'), $this->loc('Recognised great place to work (GPTW)')],
                    ]],
                ]],
                ['key' => 'benefits', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('Why join us', '¿Por qué unirte?', '私たちで働く理由', '为何加入我们'),
                        'items' => [
                            ['title' => $this->loc('Benefits & Wellbeing'), 'text' => $this->loc('Health, wellbeing and support.')],
                            ['title' => $this->loc('Learning & Development'), 'text' => $this->loc('Grow your skills and career.')],
                            ['title' => $this->loc('Internship Programs'), 'text' => $this->loc('Kickstart your career with us.')],
                            ['title' => $this->loc('Graduate Programs'), 'text' => $this->loc('Structured early-career paths.')],
                            ['title' => $this->loc('Workplace Events'), 'text' => $this->loc('A vibrant, connected community.')],
                            ['title' => $this->loc('Awards & Achievements'), 'text' => $this->loc('GPTW and industry recognition.')],
                        ],
                    ]],
                ]],
                ['key' => 'openings', 'type' => 'jobs', 'blocks' => [
                    ['jobs_list', [
                        'title' => $this->loc('Open positions', 'Vacantes abiertas', '募集中のポジション', '在招职位'),
                        'intro' => $this->loc('Apply online — upload your CV and our HR team will be in touch.',
                            'Postula en línea: sube tu CV y nuestro equipo de RR. HH. te contactará.',
                            'オンラインで応募できます。履歴書をアップロードいただければ、人事チームからご連絡します。',
                            '在线申请——上传简历，我们的人力资源团队将与您联系。'),
                    ]],
                ]],
                ['key' => 'cta', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'  => $this->loc('Don’t see the right role?', '¿No encuentras el puesto adecuado?', '希望のポジションが見つかりませんか？', '没有合适的职位？'),
                        'text'   => $this->loc('Send us your profile and we’ll keep you in mind for future openings.'),
                        'button' => $this->loc('Contact us', 'Contáctanos', 'お問い合わせ', '联系我们'),
                        'url'    => 'contact',
                    ]],
                ]],
            ],
        ];
    }

    private function contact(): array
    {
        return [
            'title' => $this->loc('Contact', 'Contacto', 'お問い合わせ', '联系我们'),
            'meta'  => $this->loc('Connect with Norlanka — inquiries, partnerships and more.'),
            'sections' => [
                $this->hero(
                    $this->loc('Contact', 'Contacto', 'お問い合わせ', '联系我们'),
                    $this->loc('Connect with us', 'Conecta con nosotros', 'お問い合わせ', '与我们联系'),
                    $this->loc('Tell us about your project — we’ll get back to you shortly.')
                ),
                ['key' => 'contact', 'type' => 'contact', 'blocks' => [
                    ['contact_block', [
                        'title' => $this->loc('Get in touch', 'Ponte en contacto', 'お問い合わせ', '取得联系'),
                        'intro' => $this->loc('For inquiries, partnerships or careers, send us a message.'),
                    ]],
                ]],
                ['key' => 'map', 'type' => 'map', 'blocks' => [
                    ['map', [
                        'title' => $this->loc('Find us', 'Encuéntranos', '所在地', '我们的位置'),
                        'embed' => 'https://www.openstreetmap.org/export/embed.html?bbox=79.82%2C6.90%2C79.90%2C6.95&layer=mapnik&marker=6.9271%2C79.8612',
                    ]],
                ]],
            ],
        ];
    }

    private function showroom(): array
    {
        return [
            'title' => $this->loc('Virtual Showroom', 'Showroom Virtual', 'バーチャルショールーム', '虚拟展厅'),
            'meta'  => $this->loc('An immersive 3D virtual showroom — coming soon.'),
            'sections' => [
                $this->hero(
                    $this->loc('Virtual Showroom', 'Showroom Virtual', 'バーチャルショールーム', '虚拟展厅'),
                    $this->loc('Coming soon', 'Próximamente', '近日公開', '即将上线'),
                    $this->loc('An immersive, interactive 3D showroom to explore our collections.')
                ),
                ['key' => 'intro', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'title' => $this->loc('An immersive experience', 'Una experiencia inmersiva', '没入型体験', '沉浸式体验'),
                        'text'  => $this->loc('Our Three.js-powered virtual showroom — with interactive hotspots, product galleries and inquiries — is in development. Check back soon.'),
                    ]],
                ]],
                $this->ctaContact(),
            ],
        ];
    }
}
