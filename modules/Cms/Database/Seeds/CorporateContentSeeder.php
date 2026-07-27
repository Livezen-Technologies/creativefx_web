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

    /**
     * Page hero. Pass $video/$poster to run a background film behind the copy
     * (the block falls back to the animated aurora when they're absent).
     */
    private function hero(array $eyebrow, array $title, array $subtitle, ?string $video = null, ?string $poster = null): array
    {
        $content = ['eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle];
        if ($video !== null) {
            $content['video']  = $video;
            $content['poster'] = $poster;
        }

        return ['key' => 'hero', 'type' => 'hero', 'blocks' => [['pagehero', $content]]];
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
                    $this->loc('The platform where global fashion brands and manufacturing excellence come together',
                        'La plataforma donde las marcas de moda globales y la excelencia manufacturera se encuentran',
                        '世界のファッションブランドと製造の卓越性が出会うプラットフォーム',
                        '全球时尚品牌与卓越制造交汇的平台'),
                    $this->loc('Responsible Sourcing · Design · Innovation'),
                    // Manufacturing-floor background clip (content sheet, Our Story C2).
                    '/media/video/our-story-hero.mp4',
                    '/media/video/our-story-hero-poster.jpg'
                ),
                ['key' => 'profile', 'type' => 'content', 'blocks' => [
                    ['two_column', [
                        'eyebrow' => $this->loc('Who we are', 'Quiénes somos', '私たちについて', '关于我们'),
                        'title'   => $this->loc('Who we are', 'Quiénes somos', '私たちについて', '关于我们'),
                        'body'    => $this->loc(
                            "Norlanka is a leading end-to-end apparel solutions provider, partnering with over 20 global fashion brands to deliver innovative products from design to delivery. With a network of 25+ manufacturing partners across Sri Lanka and India, we produce over 60 million garments annually across babywear, childrenswear, activewear, casualwear, essentials, and accessories.\n\nSupported by advanced manufacturing capabilities, including a centralized cutting facility, in-house printing and embroidery, and operations in India, we deliver agility, quality, and scale. As part of PDS Limited, a global design-led sourcing and manufacturing platform operating in 25+ countries, we combine local expertise with global reach.",
                            "Norlanka es un proveedor líder de soluciones integrales de confección, que colabora con más de 20 marcas de moda globales para entregar productos innovadores del diseño a la entrega. Con una red de más de 25 socios de fabricación en Sri Lanka e India, producimos más de 60 millones de prendas al año.\n\nComo parte de PDS Limited, una plataforma global de diseño y fabricación presente en más de 25 países, combinamos experiencia local con alcance global."
                        ),
                        'items'   => [
                            $this->loc('25+ manufacturing partners — Sri Lanka & India'),
                            $this->loc('60M+ garments annually · 6 product segments'),
                            $this->loc('Part of PDS Limited — operating in 25+ countries'),
                        ],
                    ]],
                ]],
                ['key' => 'corporate-video', 'type' => 'video', 'blocks' => [
                    ['video', [
                        'title'   => $this->loc('Norlanka in motion', 'Norlanka en movimiento', '映像で見るノルランカ', '影像中的诺兰卡'),
                        'src'     => '/media/video/home-hero.mp4',
                        'poster'  => '/media/video/home-hero-poster.jpg',
                        'caption' => $this->loc('Our corporate film — design, development, sampling and quality assurance in action.'),
                    ]],
                ]],
                ['key' => 'hq-photo', 'type' => 'image', 'blocks' => [
                    ['image', [
                        'src' => '/media/company/hq-tower.jpg',
                        'alt' => $this->loc('Norlanka head office tower, Colombo'),
                        'caption' => $this->loc('The Norlanka head office in Colombo, Sri Lanka — home to 250+ head-office employees.'),
                    ]],
                ]],
                ['key' => 'vision', 'type' => 'statement', 'blocks' => [
                    ['statement', [
                        'items' => [
                            ['title' => $this->loc('Our Vision', 'Nuestra Visión', '私たちのビジョン', '我们的愿景'),
                             'text' => $this->loc('To be the most globally sought after, trusted fashion solutions provider.')],
                            ['title' => $this->loc('We believe in', 'Creemos en', '私たちの信条', '我们的信念'),
                             'text' => $this->loc('Providing stakeholders with growth opportunities through innovative design, and the highest value through excellent customer service.')],
                        ],
                    ]],
                ]],
                ['key' => 'journey', 'type' => 'timeline', 'blocks' => [
                    ['timeline', [
                        'title' => $this->loc('Our journey', 'Nuestra trayectoria', '私たちの歩み', '我们的历程'),
                        'items' => [
                            ['year' => $this->loc('2010'), 'title' => $this->loc('Founded', 'Fundación', '創業', '创立'), 'text' => $this->loc('Founded as G C Lanka Trading (Pvt) Ltd.')],
                            ['year' => $this->loc('2012'), 'title' => $this->loc('Rebranded as Norlanka', 'Renombrada Norlanka', 'ノルランカに社名変更', '更名为诺兰卡'), 'text' => $this->loc('Rebranded as Norlanka Manufacturing Colombo Ltd.')],
                            ['year' => $this->loc('2014'), 'title' => $this->loc('Trincomalee facility', 'Planta de Trincomalee', 'トリンコマリー工場', '亭可马里工厂'), 'text' => $this->loc('Expanded with the acquisition of the Trincomalee manufacturing facility.')],
                            ['year' => $this->loc('2015'), 'title' => $this->loc('Product Development Centre', 'Centro de Desarrollo', '製品開発センター', '产品开发中心'), 'text' => $this->loc('Established a dedicated Product Development Centre (PDC).')],
                            ['year' => $this->loc('2021'), 'title' => $this->loc('Norlanka India', 'Norlanka India', 'ノルランカ・インド', '诺兰卡印度'), 'text' => $this->loc('Expanded operations with Norlanka India.')],
                            ['year' => $this->loc('2022'), 'title' => $this->loc('Centralized cutting facility', 'Planta de corte centralizado', '集中裁断施設', '中央裁剪工厂'), 'text' => $this->loc('Commissioned a state-of-the-art centralized cutting facility.')],
                            ['year' => $this->loc('2023'), 'title' => $this->loc('UK showroom & design studio', 'Showroom y estudio en Reino Unido', '英国ショールーム＆デザインスタジオ', '英国展厅与设计工作室'), 'text' => $this->loc('Opened our UK showroom & design studio.')],
                        ],
                    ]],
                ]],
                ['key' => 'values', 'type' => 'values', 'blocks' => [
                    ['values_grid', [
                        'title' => $this->loc('Our Values', 'Nuestros valores', '私たちの価値観', '我们的价值观'),
                        'items' => [
                            ['title' => $this->loc('Trust, Integrity & Ethics', 'Confianza, integridad y ética', '信頼・誠実・倫理', '信任、诚信与道德'),
                             'text' => $this->loc('We will always conduct ourselves, internally and externally with the highest degree of trust, integrity, and ethics.')],
                            ['title' => $this->loc('People First', 'Las personas primero', '人を第一に', '员工优先'),
                             'text' => $this->loc('We will foster an engaging environment where our people are valued, treated with respect, empathy and compassion, and where diversity is a priority.')],
                            ['title' => $this->loc('Entrepreneurial Spirit', 'Espíritu emprendedor', '起業家精神', '企业家精神'),
                             'text' => $this->loc('We will promote an environment where our people are always encouraged to be: innovative, creative, self-driven, and agents of change.')],
                            ['title' => $this->loc('Transparency, Collaboration & Teamwork', 'Transparencia, colaboración y trabajo en equipo', '透明性・協働・チームワーク', '透明、协作与团队精神'),
                             'text' => $this->loc('We will always work in a collaborative manner fostering a ‘win/win’ environment, internally and externally.')],
                            ['title' => $this->loc('Social Responsibility', 'Responsabilidad social', '社会的責任', '社会责任'),
                             'text' => $this->loc('We will always conduct our business in a socially responsible manner working to protect the environment that we live in.')],
                            ['title' => $this->loc('Customer Centricity', 'Centrados en el cliente', '顧客中心', '以客户为中心'),
                             'text' => $this->loc('We will continuously engage with our customers to ensure highest quality service is delivered. To deliver right value to our customer we understand our customer needs.')],
                        ],
                    ]],
                ]],
                ['key' => 'leadership', 'type' => 'leadership', 'blocks' => [
                    ['leadership', [
                        'title' => $this->loc('Leadership', 'Liderazgo', 'リーダーシップ', '领导团队'),
                        'items' => [
                            ['name' => $this->loc('Dr. Deepak Kumar Seth'), 'role' => $this->loc('Group Chairman', 'Presidente del Grupo', 'グループ会長', '集团主席'), 'photo' => '/media/leadership/deepak-kumar-seth.jpg'],
                            ['name' => $this->loc('Pallak Seth'), 'role' => $this->loc('Group Executive Vice Chairman', 'Vicepresidente Ejecutivo del Grupo', 'グループ副会長', '集团执行副主席'), 'photo' => '/media/leadership/pallak-seth.jpg'],
                            ['name' => $this->loc('Sanjay Jain'), 'role' => $this->loc('Group CEO', 'CEO del Grupo', 'グループCEO', '集团首席执行官'), 'photo' => '/media/leadership/sanjay-jain.jpg'],
                            ['name' => $this->loc('Chandana Ranatunga'), 'role' => $this->loc('CEO', 'CEO', 'CEO', '首席执行官'), 'photo' => '/media/leadership/chandana-ranatunga.jpg'],
                        ],
                    ]],
                ]],
                ['key' => 'presence', 'type' => 'map', 'blocks' => [
                    ['map', [
                        'title' => $this->loc('Global presence', 'Presencia global', 'グローバルな拠点', '全球布局'),
                        'intro' => $this->loc('Head office, factories, manufacturing and design across Sri Lanka, India and the UK.'),
                        'items' => [
                            ['region' => $this->loc('Sri Lanka — Colombo'), 'detail' => $this->loc('Norlanka Head Office')],
                            ['region' => $this->loc('Sri Lanka — Trincomalee'), 'detail' => $this->loc('Norlanka Factory')],
                            ['region' => $this->loc('India — Bangalore'), 'detail' => $this->loc('Norlanka Manufacturing India')],
                            ['region' => $this->loc('UK — Leicester'), 'detail' => $this->loc('Norlanka Design Studio UK')],
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
                    $this->loc('Full-service design, development and manufacturing across 13 product categories.'),
                    // Showroom background clip (content sheet, Our Expertise C2).
                    '/media/video/our-expertise-hero.mp4',
                    '/media/video/our-expertise-hero-poster.jpg'
                ),
                ['key' => 'categories', 'type' => 'categories', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Product categories', 'Categorías de producto', '製品カテゴリ', '产品类别'),
                        'title'   => $this->loc('A portfolio built for global brands', 'Un portafolio para marcas globales', 'グローバルブランドのためのポートフォリオ', '面向全球品牌的产品组合'),
                        'text'    => $this->loc('Our diverse product portfolio spans across multiple apparel and lifestyle segments, crafted to meet the evolving needs of global brands and retailers. Our expertise includes Babywear, Childrenswear, Kids’ Nightwear, Schoolwear, Accessories, True Knits, Hosiery & Toys, Adult Wovens, Adult Jerseywear, Activewear, Maternity Wear, and Adults’ Essentials & Nightwear. With deep category knowledge and a strong manufacturing network, we deliver products that combine quality, innovation, comfort, and value.'),
                    ]],
                    ['category_grid', []],
                ]],
                ['key' => 'design', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Design & development', 'Diseño y desarrollo', 'デザインと開発', '设计与开发'),
                        'title'   => $this->loc('From ideas to market-ready products', 'De la idea al producto', 'アイデアを市場性ある製品へ', '从创意到量产'),
                        'text'    => $this->loc('We transform ideas into market-ready products through a collaborative design and development process. Our experienced teams work closely with customers on trend forecasting, fabric innovation, product engineering, sampling, and fit development. By combining creativity with technical expertise, we help brands bring compelling collections to life while ensuring speed, quality, and commercial viability.'),
                    ]],
                ]],
                ['key' => 'workflow', 'type' => 'process', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('End-to-end solutions', 'Soluciones integrales', 'エンドツーエンドのソリューション', '端到端解决方案'),
                        'title'   => $this->loc('From concept to consumer', 'Del concepto al consumidor', 'コンセプトから消費者まで', '从概念到消费者'),
                        'text'    => $this->loc('From concept to consumer, we provide seamless end-to-end solutions across the apparel value chain. Our services encompass design, sourcing, product development, manufacturing, quality assurance, supply chain management, and logistics. Leveraging our global network and industry expertise, we ensure efficiency, transparency, and timely delivery at every stage of the process.'),
                    ]],
                    ['process_steps', [
                        'items' => [
                            ['title' => $this->loc('Design')], ['title' => $this->loc('Sourcing')],
                            ['title' => $this->loc('Development')], ['title' => $this->loc('Manufacturing')],
                            ['title' => $this->loc('Quality Assurance')], ['title' => $this->loc('Supply Chain')],
                            ['title' => $this->loc('Logistics')], ['title' => $this->loc('Delivery')],
                        ],
                    ]],
                ]],
                ['key' => 'sourcing', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Ethical sourcing', 'Abastecimiento ético', '倫理的な調達', '道德采购'),
                        'title'   => $this->loc('Responsibility, embedded in everything', 'Responsabilidad en todo', 'すべてに責任を', '责任贯穿一切'),
                        'text'    => $this->loc('Sustainability and responsible business practices are embedded in everything we do. We partner with suppliers and manufacturing facilities that uphold high standards of social compliance, worker welfare, environmental stewardship, and ethical business conduct. Through responsible sourcing practices and continuous monitoring, we help our customers build resilient and sustainable supply chains.'),
                    ]],
                ]],
                ['key' => 'presence', 'type' => 'content', 'blocks' => [
                    ['richtext', [
                        'eyebrow' => $this->loc('Global presence', 'Presencia global', 'グローバルな拠点', '全球布局'),
                        'title'   => $this->loc('Local expertise, global reach', 'Experiencia local, alcance global', 'ローカルの専門性、グローバルな展開', '本地专长，全球触达'),
                        'text'    => $this->loc('With our own offices in the UK & India and as part of the PDS Group, we benefit from a strong global footprint spanning 25+ countries across key sourcing, manufacturing, and consumer markets. Our international network enables us to connect global brands with trusted manufacturing partners and fabric mills, providing local expertise, market intelligence, and agile solutions to meet the demands of a dynamic global marketplace.'),
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
                            ['value' => '25', 'suffix' => '+', 'label' => $this->loc('Manufacturing partners — Sri Lanka & India', 'Socios de fabricación', '製造パートナー（スリランカ・インド）', '制造合作伙伴（斯里兰卡与印度）')],
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
                            ['title' => $this->loc('Protect Our Environment', 'Proteger el medio ambiente', '環境を守る', '保护环境'),
                             'text'  => $this->loc('Our commitment to environmental stewardship is deeply ingrained in everything we do — from the way we operate our business to how we design our products. Our goal is to reduce our environmental impact, conserve natural resources, and protect the ecosystems that sustain life on Earth for a better tomorrow.'),
                             'targets' => [
                                ['label' => $this->loc('Emissions — Scope 1 & 2 (kgCO2e/SM)', 'Emisiones (kgCO2e/SM)', '排出量（kgCO2e/SM）', '排放（kgCO2e/SM）'), 'baseline' => '43.95', 'current' => '42.74', 'target' => '40.57'],
                                ['label' => $this->loc('Domestic water (normalized)', 'Agua doméstica (normalizada)', '生活用水（正規化）', '生活用水（归一化）'), 'baseline' => '0.0141', 'current' => '0.0130', 'target' => '0.0127'],
                                ['label' => $this->loc('Waste to landfill', 'Residuos a vertedero', '埋立廃棄物', '填埋废弃物'), 'baseline' => '5.29', 'current' => '6.60', 'target' => '5.00', 'unit' => '%'],
                                ['label' => $this->loc('Biodiversity footprint (ha)', 'Huella de biodiversidad (ha)', '生物多様性面積（ha）', '生物多样性面积（公顷）'), 'baseline' => '0.39', 'current' => '0.39', 'target' => '4.0'],
                             ]],
                            ['title' => $this->loc('Together With People', 'Junto a las personas', '人とともに', '与员工同行'),
                             'text'  => $this->loc('We believe that people are the heart of everything we do. Our success is built on strong relationships with our employees, customers, partners, and communities. When we are innovating with our teams, partnering with local communities, or supporting global initiatives, our focus remains the same: to make a better tomorrow for everyone.'),
                             'targets' => [
                                ['label' => $this->loc('Women in leadership', 'Mujeres en liderazgo', '女性管理職比率', '女性领导比例'), 'baseline' => '24.68', 'current' => '28.1', 'target' => '30.00', 'unit' => '%'],
                                ['label' => $this->loc('Training hours per employee', 'Horas de formación por empleado', '従業員1人あたり研修時間', '人均培训小时'), 'baseline' => '10.94', 'current' => '11.71', 'target' => '20.00'],
                                ['label' => $this->loc('Community beneficiaries', 'Beneficiarios comunitarios', '地域の受益者数', '社区受益者'), 'baseline' => '582', 'current' => '2882', 'target' => '5,000'],
                             ]],
                            ['title' => $this->loc('Trust In Everything', 'Confianza en todo', 'すべてに信頼を', '处处可信'),
                             'text'  => $this->loc('Trust reflects our deep commitment to doing what is right, always. In an increasingly complex world, we understand that trust is a company’s most valuable asset. It shapes reputations, builds lasting relationships, and fuels sustainable growth. Good governance is the foundation for a better tomorrow.'),
                             'targets' => [
                                ['label' => $this->loc('Tier-1 Higg/Worldly verification', 'Verificación Higg Tier-1', 'Tier-1 Higg検証', 'Tier-1 Higg 验证'), 'baseline' => '75.8', 'current' => '96.8', 'target' => '100.0', 'unit' => '%'],
                                ['label' => $this->loc('Tier-2 facilities verified', 'Instalaciones Tier-2 verificadas', 'Tier-2施設の検証', 'Tier-2 工厂验证'), 'baseline' => '45.8', 'current' => '45.8', 'target' => '75.0', 'unit' => '%'],
                             ]],
                        ],
                    ]],
                ]],
                ['key' => 'esg-report', 'type' => 'cta', 'blocks' => [
                    ['cta', [
                        'title'  => $this->loc('Our ESG report', 'Nuestro informe ESG', 'ESGレポート', 'ESG 报告'),
                        'text'   => $this->loc('Read the full Better Tomorrow strategy — pillars, targets and the projects behind them.'),
                        'button' => $this->loc('Download the ESG report', 'Descargar el informe ESG', 'ESGレポートをダウンロード', '下载 ESG 报告'),
                        'url'    => '/media/downloads/norlanka-esg-strategy.pdf',
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
                    $this->loc('Where Entrepreneurial Minds Thrive', 'Donde prosperan las mentes emprendedoras', '起業家精神が育つ場所', '创业精神在此绽放'),
                    $this->loc('Join a team that empowers you to innovate, grow, and make a meaningful impact in the global apparel industry.',
                        'Únete a un equipo que te impulsa a innovar, crecer y generar un impacto real en la industria global de la confección.',
                        'イノベーションと成長、そしてグローバルなアパレル産業への貢献を後押しするチームに参加しませんか。',
                        '加入一个赋能你创新、成长并在全球服装行业创造深远影响的团队。'),
                    // People-engagement background clip (content sheet, Careers C2).
                    '/media/video/careers-hero.mp4',
                    '/media/video/careers-hero-poster.jpg'
                ),
                ['key' => 'why', 'type' => 'features', 'blocks' => [
                    ['feature_cards', [
                        'title' => $this->loc('Why Choose Norlanka?', '¿Por qué elegir Norlanka?', 'ノルランカを選ぶ理由', '为何选择诺兰卡？'),
                        'items' => [
                            ['title' => $this->loc('Global Backing', 'Respaldo global', 'グローバルな後ろ盾', '全球支持'),
                             'text' => $this->loc('Backed by PDS Limited, a global fashion infrastructure platform, we offer the stability, resources, and international reach that empower our people to build long-term careers.')],
                            ['title' => $this->loc('Entrepreneurial Culture', 'Cultura emprendedora', '起業家文化', '创业文化'),
                             'text' => $this->loc('We encourage ownership, initiative, and innovation, giving every employee the opportunity to make a meaningful impact.')],
                            ['title' => $this->loc('Continuous Growth', 'Crecimiento continuo', '継続的な成長', '持续成长'),
                             'text' => $this->loc('Through learning, development, and global exposure, we help our people reach their full potential.')],
                            ['title' => $this->loc('Purpose-Driven Business', 'Negocio con propósito', 'パーパス主導の事業', '使命驱动的企业'),
                             'text' => $this->loc("As a responsible manufacturer, we're committed to creating positive impact for our people, our partners, and the communities we serve.")],
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
                ['key' => 'life', 'type' => 'gallery', 'blocks' => [
                    ['gallery', [
                        'eyebrow' => $this->loc('Life @ Norlanka', 'Vida en Norlanka', 'ノルランカでの日々', '诺兰卡生活'),
                        'title'   => $this->loc('Life @ Norlanka', 'Vida en Norlanka', 'ノルランカでの日々', '诺兰卡生活'),
                        'intro'   => $this->loc('Employee engagement and wellbeing — volunteering, community programmes and moments from across our teams.'),
                        'items'   => [
                            ['src' => '/media/impact/blood-donation.jpg', 'caption' => $this->loc('Annual blood donation camps, run by our own volunteers')],
                            ['src' => '/media/impact/tree-planting.jpg', 'caption' => $this->loc('Roots for Tomorrow — team tree-planting across the island')],
                            ['src' => '/media/impact/education.jpg', 'caption' => $this->loc('Scholarship seminars and school-supply drives with our teams')],
                        ],
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
