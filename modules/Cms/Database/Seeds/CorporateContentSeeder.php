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
                            "Norlanka is a vertically integrated apparel manufacturer serving the world's leading brands. From trend research to delivery, we combine craftsmanship, technology and responsible sourcing across a global footprint.",
                            'Norlanka es un fabricante de prendas verticalmente integrado al servicio de las marcas líderes del mundo, combinando artesanía, tecnología y abastecimiento responsable.'
                        ),
                        'items'   => [
                            $this->loc('Vertically integrated, design-to-delivery'),
                            $this->loc('Trusted by leading global brands'),
                            $this->loc('Responsible, traceable supply chains'),
                        ],
                    ]],
                ]],
                ['key' => 'vision', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('Vision & Mission', 'Visión y Misión', 'ビジョンとミッション', '愿景与使命'),
                        'items' => [
                            ['title' => $this->loc('Vision', 'Visión', 'ビジョン', '愿景'), 'text' => $this->loc('To be the world’s most trusted and responsible apparel partner.')],
                            ['title' => $this->loc('Mission', 'Misión', 'ミッション', '使命'), 'text' => $this->loc('To deliver design-led, sustainable apparel through innovation and people.')],
                        ],
                    ]],
                ]],
                ['key' => 'journey', 'type' => 'timeline', 'blocks' => [
                    ['timeline', [
                        'title' => $this->loc('Our journey', 'Nuestra trayectoria', '私たちの歩み', '我们的历程'),
                        'items' => [
                            ['year' => $this->loc('1995'), 'title' => $this->loc('Founded', 'Fundación', '創業', '成立'), 'text' => $this->loc('Norlanka begins as a specialist apparel manufacturer.')],
                            ['year' => $this->loc('2005'), 'title' => $this->loc('Global expansion', 'Expansión global', 'グローバル展開', '全球扩张'), 'text' => $this->loc('Facilities and partnerships expand across regions.')],
                            ['year' => $this->loc('2015'), 'title' => $this->loc('Sustainability', 'Sostenibilidad', 'サステナビリティ', '可持续发展'), 'text' => $this->loc('Responsible sourcing and ESG programs formalised.')],
                            ['year' => $this->loc('2024'), 'title' => $this->loc('Innovation', 'Innovación', 'イノベーション', '创新'), 'text' => $this->loc('Automation and an innovation lab shape what’s next.')],
                        ],
                    ]],
                ]],
                ['key' => 'values', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('Core values', 'Valores', '私たちの価値観', '核心价值观'),
                        'items' => [
                            ['title' => $this->loc('Responsible', 'Responsable', '責任', '负责任'), 'text' => $this->loc('We source and operate ethically and transparently.')],
                            ['title' => $this->loc('Collaborative', 'Colaborativo', '協働', '协作'), 'text' => $this->loc('We win together with partners and people.')],
                            ['title' => $this->loc('Sustainable', 'Sostenible', '持続可能', '可持续'), 'text' => $this->loc('We protect the environment in everything we make.')],
                            ['title' => $this->loc('Innovative', 'Innovador', '革新的', '创新'), 'text' => $this->loc('We pursue better materials, processes and technology.')],
                            ['title' => $this->loc('Global', 'Global', 'グローバル', '全球'), 'text' => $this->loc('We serve brands and markets worldwide.')],
                            ['title' => $this->loc('Customer Centric', 'Centrado en el cliente', '顧客中心', '以客户为中心'), 'text' => $this->loc('We build around our customers’ success.')],
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
                        'intro' => $this->loc('Manufacturing, sourcing and partnerships spanning multiple regions.'),
                        'items' => [
                            ['region' => $this->loc('South Asia'), 'detail' => $this->loc('Manufacturing hubs')],
                            ['region' => $this->loc('East Asia'), 'detail' => $this->loc('Sourcing & partners')],
                            ['region' => $this->loc('Europe'), 'detail' => $this->loc('Brand partners')],
                            ['region' => $this->loc('North America'), 'detail' => $this->loc('Brand partners')],
                            ['region' => $this->loc('Middle East'), 'detail' => $this->loc('Emerging markets')],
                        ],
                    ]],
                ]],
                ['key' => 'partners', 'type' => 'partners', 'blocks' => [
                    ['partners', [
                        'title' => $this->loc('Strategic partnerships', 'Alianzas estratégicas', '戦略的パートナーシップ', '战略合作伙伴'),
                        'intro' => $this->loc('We collaborate with mills, innovators and global brands.'),
                        'items' => ['Brand A', 'Brand B', 'Mill Co', 'Fabric Lab', 'Logistics Inc'],
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
                ['key' => 'capacity', 'type' => 'metrics', 'blocks' => [
                    ['metrics', [
                        'title' => $this->loc('Production capacity', 'Capacidad de producción', '生産能力', '产能'),
                        'items' => [
                            ['value' => '30', 'suffix' => '+', 'label' => $this->loc('Factories', 'Fábricas', '工場', '工厂')],
                            ['value' => '25000', 'suffix' => '+', 'label' => $this->loc('Employees', 'Empleados', '従業員', '员工')],
                            ['value' => '500', 'suffix' => '+', 'label' => $this->loc('Production lines', 'Líneas', '生産ライン', '生产线')],
                            ['value' => '10', 'suffix' => 'M+', 'label' => $this->loc('Monthly capacity', 'Capacidad mensual', '月間生産', '月产能')],
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

    private function impact(): array
    {
        return [
            'title' => $this->loc('Our Impact', 'Impacto', '私たちの影響', '我们的影响'),
            'meta'  => $this->loc('Better Tomorrow — environment, people and trust.'),
            'sections' => [
                $this->hero(
                    $this->loc('Our Impact', 'Impacto', '私たちの影響', '我们的影响'),
                    $this->loc('Better Tomorrow', 'Un mañana mejor', 'より良い明日', '更美好的明天'),
                    $this->loc('Protecting the environment, investing in people, and earning trust in everything we do.')
                ),
                ['key' => 'pillars', 'type' => 'esg', 'blocks' => [
                    ['esg_pillars', [
                        'title' => $this->loc('Better Tomorrow', 'Un mañana mejor', 'より良い明日', '更美好的明天'),
                        'intro' => $this->loc('Our ESG framework across three commitments.'),
                        'pillars' => [
                            ['title' => $this->loc('Protect Our Environment', 'Proteger el medio ambiente', '環境を守る', '保护环境'), 'items' => [
                                $this->loc('Emission reduction'), $this->loc('Water reduction'),
                                $this->loc('Waste reduction'), $this->loc('Biodiversity improvement'),
                            ]],
                            ['title' => $this->loc('Together With People', 'Junto a las personas', '人とともに', '与员工同行'), 'items' => [
                                $this->loc('Women in leadership'), $this->loc('Employee training'),
                                $this->loc('Community engagement'),
                            ]],
                            ['title' => $this->loc('Trust In Everything', 'Confianza en todo', 'すべてに信頼を', '处处可信'), 'items' => [
                                $this->loc('Certifications'), $this->loc('Compliance'), $this->loc('ESG reports'),
                            ]],
                        ],
                    ]],
                ]],
                ['key' => 'metrics', 'type' => 'metrics', 'blocks' => [
                    ['metrics', [
                        'title' => $this->loc('Progress', 'Progreso', '進捗', '进展'),
                        'items' => [
                            ['value' => '32', 'suffix' => '%', 'label' => $this->loc('Emission reduction', 'Reducción emisiones', '排出削減', '减排')],
                            ['value' => '28', 'suffix' => '%', 'label' => $this->loc('Water reduction', 'Reducción de agua', '水使用削減', '节水')],
                            ['value' => '46', 'suffix' => '%', 'label' => $this->loc('Women in leadership', 'Mujeres en liderazgo', '女性管理職', '女性领导')],
                            ['value' => '120', 'suffix' => 'k', 'label' => $this->loc('Training hours', 'Horas formación', '研修時間', '培训小时')],
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
                ['key' => 'cta', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'  => $this->loc('Join our team', 'Únete a nuestro equipo', 'チームに参加', '加入我们'),
                        'text'   => $this->loc('Our full job portal is launching soon. Reach out and we’ll be in touch.'),
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
