<?php

namespace Modules\Tshda\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The Authority's editorial pages, on the block-based CMS.
 *
 * Two rules govern this file:
 *
 *  1. A page an administrator has edited is theirs. `is_custom` is set the
 *     moment the console writes to a page, and this seeder returns before the
 *     reset that would otherwise delete their sections and write ours back —
 *     silently, one deploy after the edit was made.
 *
 *  2. Clause 3.10 sets a minimum of 500 words on every page except the welcome
 *     page, news and event pages, the sitemap and the home page. The copy here
 *     meets it. It is written from the Authority's published record and is a
 *     starting draft for the Content Management Team to correct and sign off,
 *     not a substitute for that sign-off: anything the Authority has not
 *     published — current subsidy rates, officers' names, figures — is left as
 *     a stated placeholder rather than invented.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        helper('norlanka');
        $now = date('Y-m-d H:i:s');

        foreach ($this->pages() as $slug => $def) {
            $this->seedPage($slug, $def, $now);
        }
    }

    private function loc(string $en): array
    {
        return content_locales(['en' => $en]);
    }

    private function seedPage(string $slug, array $def, string $now): void
    {
        $pages = $this->db->table('pages');
        $data  = [
            'slug'             => $slug,
            'title'            => json_encode($this->loc($def['title']), JSON_UNESCAPED_UNICODE),
            'meta_description' => json_encode($this->loc($def['meta']), JSON_UNESCAPED_UNICODE),
            'meta_title'       => json_encode($this->loc($def['meta_title'] ?? $def['title']), JSON_UNESCAPED_UNICODE),
            'template'         => 'default',
            'is_home'          => (int) ($def['is_home'] ?? 0),
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

        if ((int) ($existing['is_custom'] ?? 0) === 1) {
            return;
        }

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

    /** A hero block, built the same way on every page. */
    private function hero(string $eyebrow, string $title, string $intro): array
    {
        return ['pagehero', [
            'eyebrow' => $this->loc($eyebrow),
            'title'    => $this->loc($title),
            'subtitle' => $this->loc($intro),
        ]];
    }

    /** A body block of prose. */
    private function prose(string $title, string $html, string $eyebrow = ''): array
    {
        $content = ['title' => $this->loc($title), 'text' => $this->loc($html)];
        if ($eyebrow !== '') {
            $content['eyebrow'] = $this->loc($eyebrow);
        }

        return ['richtext', $content];
    }

    private function pages(): array
    {
        return [
            'home'                     => $this->home(),
            'about-us'                 => $this->aboutUs(),
            'vision-mission'           => $this->visionMission(),
            'strategic-plan'           => $this->strategicPlan(),
            'divisions'                => $this->divisions(),
            'organisational-structure' => $this->orgStructure(),
            'land-development'         => $this->landDevelopment(),
            'societies'                => $this->societies(),
            'privacy'                  => $this->privacy(),
            'terms'                    => $this->terms(),
            'accessibility'            => $this->accessibility(),
        ];
    }

    /**
     * The home page row exists so the page carries its own SEO fields, its
     * last-updated stamp and its place in the sitemap. Its layout is a
     * dedicated view rather than blocks: Clause 3.9 B names seven modules that
     * each read from their own table, and a block editor is the wrong tool for
     * "show whichever notices are current".
     */
    private function home(): array
    {
        return [
            'is_home' => 1,
            'title'   => 'Tea Small Holdings Development Authority',
            'meta_title' => 'Tea Small Holdings Development Authority — Sri Lanka',
            'meta'    => 'The statutory authority for the development of Sri Lanka’s tea smallholdings: subsidies, extension services, societies, training and statistics, in English, Sinhala and Tamil.',
            'sections' => [],
        ];
    }

    private function aboutUs(): array
    {
        return [
            'title' => 'About the Authority',
            'meta'  => 'Overview and history of the Tea Small Holdings Development Authority, established in 1977 under the Tea Small Holdings Development Law No. 35 of 1975.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero(
                        'About Us',
                        'The Authority and the sector it serves',
                        'Established in 1977, the Tea Small Holdings Development Authority is the statutory body responsible for the development of Sri Lanka’s tea smallholdings.'
                    ),
                ]],
                ['key' => 'overview', 'type' => 'generic', 'blocks' => [
                    $this->prose('Overview', <<<'HTML'
<p>The Tea Small Holdings Development Authority was established on 1 February 1977 under the Tea Small Holdings Development Law No. 35 of 1975. It is the sole statutory body mandated for the development of the tea smallholding sector in Sri Lanka, and it reports to the Ministry responsible for the plantation industries.</p>
<p>A tea smallholding is, in law, land under tea not exceeding ten acres — about 4.05 hectares — in the possession of one person. That definition covers a very large part of the national crop. Smallholders are not a marginal category in Sri Lankan tea; they are the majority of it, and the productivity of their land, the quality of the leaf they pluck and the price they are paid together determine a great deal of what the country earns from tea and what rural households in the tea-growing districts live on.</p>
<p>The Authority exists because that sector cannot be developed by market forces alone. A smallholder replanting a block takes land out of production for several years before the new tea yields; without assistance, the rational decision for an individual household is to keep plucking a declining bush. The subsidy schemes, the extension service and the society structure the Authority operates all address that gap between what is sensible for one household this season and what the sector needs over a generation.</p>
HTML),
                    $this->prose('What the Authority does', <<<'HTML'
<p>The Authority's work falls into four broad areas.</p>
<p><strong>Land development.</strong> Replanting, new planting, infilling and soil conservation are supported by subsidy schemes administered through the regional office network. Assistance is paid in instalments against field inspection at each stage, so that public money follows work actually done on the ground rather than an application on paper.</p>
<p><strong>Extension and technical advice.</strong> The Authority maintains a field extension service reaching every tea-growing district. At the grassroots the unit of service is the Tea Inspector or Extension Officer range; 147 such ranges operate across the tea-growing area, supervised by 26 sub offices grouped into eight regions. An officer in a range is the smallholder's first and usually only point of contact with the Authority, and much of what this website does is make that officer easier to find and easier to reach.</p>
<p><strong>Societies and marketing.</strong> Tea Smallholder Development Societies organise smallholders at village level. They receive input allocations on behalf of members, run welfare and savings programmes, and give a group of households a standing that no individual smallholder has when dealing with collectors and factories. The Authority registers, supports and monitors them, and runs enterprise and welfare programmes with them under the Tea Shakthi banner.</p>
<p><strong>Training.</strong> The Hantana National Training Centre near Kandy is the Authority's residential training facility, running programmes for smallholders, society office bearers and the Authority's own field staff.</p>
HTML),
                    $this->prose('History', <<<'HTML'
<p>The legislation that created the Authority was passed in 1975, at a point when the difference in yield and quality between the estate sector and the smallholding sector had become a national policy question rather than an agricultural one. The Authority began work in February 1977 with a mandate that has not fundamentally changed since: increase production, improve productivity, develop marketing, and work for the welfare of the tea smallholder.</p>
<p>What has changed is scale and method. The extension network has been built out district by district; the subsidy schemes have been revised as costs and cultivars have changed; the society structure has grown from a handful of pilot bodies into a national network; and the Hantana centre has taken on training that was once done, if at all, in the field. The Authority now employs roughly 440 staff across a head office at Battaramulla, nine regional offices, the sub offices beneath them and the national training centre.</p>
<p>The Authority's regional offices are at Galle, Matara, Ratnapura, Kegalle, Kalutara, Kandy, Nuwara Eliya and Bandarawela, with the head office at Pelawatte, Battaramulla. A full list, with addresses, telephone numbers and map locations, is in the <a href="directory">Staff Directory</a>, and the divisions and their functional areas are set out under <a href="divisions">Divisions</a>.</p>
HTML),
                ]],
            ],
        ];
    }

    private function visionMission(): array
    {
        return [
            'title' => 'Vision, Mission and Objectives',
            'meta'  => 'The vision, mission, objectives and guiding policies of the Tea Small Holdings Development Authority.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('About Us', 'Vision, Mission and Objectives', 'What the Authority is for, stated plainly, and the objectives its divisions are measured against.'),
                ]],
                ['key' => 'vision', 'type' => 'generic', 'blocks' => [
                    ['statement', ['items' => [
                        [
                            'title' => $this->loc('Vision'),
                            'text'  => $this->loc('A restructured tea smallholding sector that is one of the most influential actors in the national economy, in which the productivity of tea land has been raised and the family unit of the tea smallholder, and the rural economy around it, has been empowered.'),
                        ],
                        [
                            'title' => $this->loc('Mission'),
                            'text'  => $this->loc('To provide support services that increase the productivity and the leaf quality of the tea lands of Sri Lanka’s tea smallholders.'),
                        ],
                    ]]],
                    $this->prose('Mission', <<<'HTML'
<p>To provide support services that increase the productivity and the leaf quality of the tea lands of Sri Lanka's tea smallholders.</p>
<p>The mission is deliberately narrow. The Authority is not a buyer, a factory or a bank. What it can change is the state of the land, the skill of the person working it and the strength of the organisation that person belongs to — and the mission names those, rather than the outcomes in the market that follow from them.</p>
HTML),
                    $this->prose('Objectives', <<<'HTML'
<p>The Tea Small Holdings Development Law sets the Authority's objects. In the working form used across its divisions, they are:</p>
<ul>
<li><strong>Increase production.</strong> Raise the volume of green leaf produced by the smallholding sector, principally by replacing old and low-yielding tea with recommended cultivars under the replanting programme, and by bringing suitable new land under tea where expansion is appropriate.</li>
<li><strong>Improve productivity.</strong> Raise yield per hectare and quality per kilogram through extension advice on pruning, plucking rounds, shade, nutrition, and pest and disease management, and through soil conservation on the sloping land most smallholdings sit on.</li>
<li><strong>Develop marketing.</strong> Strengthen the smallholder's position in the chain between the bush and the factory — through societies, through group input purchase and value addition, and through clearer, fairer assessment of leaf at collection.</li>
<li><strong>Advance smallholder welfare.</strong> Support the welfare, savings and enterprise programmes that make a smallholding household more resilient than the crop alone allows.</li>
<li><strong>Organise the sector.</strong> Register smallholdings and societies, keep those registers current, and use them as the basis on which every other service is delivered.</li>
</ul>
HTML),
                    $this->prose('How the objectives are pursued', <<<'HTML'
<p>Each objective is carried by a division with named functional areas, set out under <a href="divisions">Divisions and Functional Areas</a>, and delivered in the field through the regional office network and the Tea Inspector ranges beneath it.</p>
<p>The Authority's approach rests on three practical commitments. The first is that assistance follows verified work: subsidy instalments are released against field inspection, not against paperwork. The second is that the smallholder should not have to know the Authority's organisation chart to get help — the Tea Inspector for the range is the single point of contact, and this website's <a href="directory">Staff Directory</a> exists to make that officer findable by district and subject rather than by title. The third is that the registers are the foundation: an entitlement, an allocation and a society membership are all read from the register, so keeping it accurate is not administrative housekeeping but the precondition of everything else.</p>
<p>Progress against these objectives is published in the <a href="statistics">Statistics</a> section and in the Authority's annual reports, which are available under <a href="downloads">Downloads</a>.</p>
HTML),
                ]],
            ],
        ];
    }

    private function strategicPlan(): array
    {
        return [
            'title' => 'Strategic Plan, Policies and Action Plan',
            'meta'  => 'The Authority’s strategic direction, policy framework and current action plan for the tea smallholding sector.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('About Us', 'Strategic Plan and Policies', 'The Authority’s medium-term direction, the policies that govern how it works, and the action plan for the current year.'),
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('Strategic direction', <<<'HTML'
<p>The Authority's strategic plan addresses one central problem: a large share of the country's smallholder tea is old. Bushes past their productive life yield less each year, respond poorly to fertilizer and produce leaf that is harder to sell well. Replanting is the answer, and replanting is expensive in the one currency a smallholding household cannot easily spare — time without income.</p>
<p>Everything else in the plan follows from taking that seriously. Raising the replanting rate means making the subsidy sufficient and its payment predictable; it means having planting material of verified cultivar available when the block is ready; it means conservation works on sloping land so the investment is not washed away; and it means an extension service close enough to the smallholder that the young tea is managed properly through the years before it yields.</p>
HTML),
                    $this->prose('Strategic priorities', <<<'HTML'
<ul>
<li><strong>Accelerate replanting and infilling.</strong> Raise the annual extent replanted under subsidy, shorten the time from application to first instalment, and follow the replanted block through establishment rather than closing the file at planting.</li>
<li><strong>Secure quality planting material.</strong> Register and monitor commercial nurseries so that subsidised planting is done with cultivars of verified identity and quality.</li>
<li><strong>Strengthen the extension service.</strong> Keep every Tea Inspector range staffed and equipped, and give officers the information, mobility and subject support they need to advise credibly.</li>
<li><strong>Build the society network.</strong> Increase the share of smallholders who belong to an active registered society, and increase what a society is capable of doing for its members.</li>
<li><strong>Improve leaf quality and its assessment.</strong> Work with societies, collectors and factories on how leaf is assessed at collection and how that assessment reaches the price paid.</li>
<li><strong>Modernise the Authority's own information.</strong> Keep the registers of holdings, societies and officers accurate and available, and publish the Authority's statistics openly.</li>
</ul>
HTML),
                    $this->prose('Policy framework', <<<'HTML'
<p>The Authority operates under the Tea Small Holdings Development Law No. 35 of 1975 and the regulations made under it, within the policy direction of the Ministry responsible for plantation industries and the national policy framework for the tea industry. Its subsidy schemes, registration requirements and technical recommendations are issued as circulars and guidelines, which are published under <a href="downloads">Downloads</a> so that a smallholder, a society or an officer can read the rule that applies rather than rely on an account of it.</p>
<p>The Authority is subject to the Right to Information Act No. 12 of 2016. Requests are made to the Information Officer at Head Office; the procedure is set out under <a href="services">Services</a>. It is also subject to audit by the Auditor General, and its annual reports and audited accounts are tabled in Parliament and published under <a href="downloads">Downloads</a>.</p>
HTML),
                    $this->prose('Action plan', <<<'HTML'
<p>The action plan translates the strategic priorities into the year's work: targets for extent replanted and newly planted by district, fertilizer issue schedules, the society formation and strengthening programme, the training calendar at Hantana, and the Authority's own capital and administrative programme.</p>
<p>The current action plan and the annual progress report against it are published under <a href="downloads">Downloads</a> in the Publications and Annual Reports categories. Where a target has a public consequence — an application window opening, a fertilizer issue, a training intake — it also appears as an announcement in the <a href="announcements">Media Centre</a>, and subscribers to the relevant alert category are notified when it is published.</p>
<p><em>The strategic plan and action plan documents for the current period are being loaded into the Downloads section. Until they appear there, copies may be requested from the Authority's Head Office.</em></p>
HTML),
                ]],
            ],
        ];
    }

    private function divisions(): array
    {
        return [
            'title' => 'Divisions and Functional Areas',
            'meta'  => 'The divisions of the Tea Small Holdings Development Authority and the functional area each is responsible for.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('About Us', 'Divisions and Functional Areas', 'Who does what inside the Authority, and which division to approach for which matter.'),
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('How the Authority is organised', <<<'HTML'
<p>The Authority works through divisions at Head Office, a network of regional offices in the tea-growing districts, sub offices beneath them, and the Tea Inspector and Extension Officer ranges that meet the smallholder in the field. Roughly 440 staff are distributed across that structure, the great majority of them in the field rather than at Head Office.</p>
<p>The divisions below are the functional areas the Authority is organised into. Each is listed with what it is responsible for and what a member of the public would approach it about. Named officers, with telephone numbers and email addresses, are in the <a href="directory">Staff Directory</a>, which is filterable by division, district and subject area.</p>
HTML),
                    ['feature_cards', [
                        'title' => $this->loc('Head Office divisions'),
                        'items' => [
                            ['title' => $this->loc('Development'), 'text' => $this->loc('Replanting, new planting, infilling and soil conservation subsidies; the technical standards attached to them; and the field programme that delivers them through the regional offices.')],
                            ['title' => $this->loc('Extension & Training'), 'text' => $this->loc('The field extension service and the Tea Inspector ranges; technical recommendations to smallholders; and the Hantana National Training Centre and its programme calendar.')],
                            ['title' => $this->loc('Societies & Marketing'), 'text' => $this->loc('Registration and support of Tea Smallholder Development Societies; the Tea Shakthi welfare, savings and enterprise programmes; and work on leaf quality, collection and marketing.')],
                            ['title' => $this->loc('Administration'), 'text' => $this->loc('Establishments, human resources, recruitment and staff welfare; Right to Information; and the Authority’s general administration and legal matters.')],
                            ['title' => $this->loc('Finance'), 'text' => $this->loc('Budget, payments and the disbursement of subsidy instalments; procurement and tenders; and the Authority’s accounts and financial reporting.')],
                            ['title' => $this->loc('Internal Audit'), 'text' => $this->loc('Audit of the Authority’s systems, field inspections and subsidy disbursements, and compliance with the Law, regulations and circulars made under it.')],
                            ['title' => $this->loc('Information & Communication Technology'), 'text' => $this->loc('The Authority’s registers and systems, this website and its content, and the data on which entitlements and allocations are calculated.')],
                            ['title' => $this->loc('Planning & Monitoring'), 'text' => $this->loc('The strategic plan and action plan, progress monitoring against targets, and the statistics the Authority publishes.')],
                        ],
                    ]],
                    $this->prose('The field structure', <<<'HTML'
<p>Below Head Office, the Authority's presence in the tea-growing districts is organised in three tiers.</p>
<p><strong>Regional offices</strong> at Galle, Matara, Ratnapura, Kegalle, Kalutara, Kandy, Nuwara Eliya and Bandarawela administer the Authority's programmes in their districts. A Regional Manager heads each office. Applications for registration, subsidies and society registration are processed here, and this is the office to approach when a matter cannot be settled with the Tea Inspector.</p>
<p><strong>Sub offices</strong> — twenty-six of them, across eight regions — supervise the ranges beneath them and bring the Authority closer to the smallholder than a district office can.</p>
<p><strong>Tea Inspector and Extension Officer ranges</strong> are the lowest unit of service, and there are 147 of them across the tea-growing area. The officer for a range knows the holdings in it, inspects blocks for subsidy, advises on field practice and certifies the work at each stage. For most smallholders, the officer for their range <em>is</em> the Authority.</p>
<p>The <a href="hantana">Hantana National Training Centre</a> sits alongside this structure as a national facility rather than a district one, serving all regions.</p>
HTML),
                ]],
            ],
        ];
    }

    private function orgStructure(): array
    {
        return [
            'title' => 'Organisational Structure',
            'meta'  => 'The organisational structure of the Tea Small Holdings Development Authority, from the Board and Director General to the Tea Inspector ranges in the field.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('About Us', 'Organisational Structure', 'The Authority’s reporting structure, as a chart you can read rather than a scanned image.'),
                ]],
                ['key' => 'chart', 'type' => 'org_chart', 'blocks' => [
                    ['org_chart', ['title' => $this->loc('Reporting structure')]],
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('Reading the structure', <<<'HTML'
<p>The Authority is governed by a Board appointed under the Tea Small Holdings Development Law, chaired by the Chairman. The Director General is the chief executive and is accountable to the Board for the Authority's operations.</p>
<p>Two Deputy Directors General carry the executive load beneath the Director General. <strong>Development</strong> holds the land development programme — replanting, new planting, soil conservation and the subsidies attached to them — together with extension and training. <strong>Administration</strong> holds establishments, human resources, finance and the Authority's general administration. Internal Audit reports separately, as the Law and the Authority's audit arrangements require, so that the function auditing disbursements does not report to the function making them.</p>
<p>Beneath the divisions, the field structure runs regional office to sub office to range. A Regional Manager heads each regional office and is the senior officer of the Authority in that district. The Hantana National Training Centre is a national facility and is managed within Extension & Training rather than by a region.</p>
<p>This structure is published here as a chart rather than as a scanned organogram so that it can be read on a telephone, searched, translated into Sinhala and Tamil with the rest of the site, and read aloud by a screen reader. Named post-holders are listed in the <a href="directory">Staff Directory</a>, which is maintained through the content management system and is therefore current in a way a scanned image never is.</p>
<p>The functional responsibility of each division is set out under <a href="divisions">Divisions and Functional Areas</a>. If you are not sure which division to approach, the <a href="services">Services</a> section names the responsible division and the contact point against every service the Authority provides.</p>
HTML),
                ]],
            ],
        ];
    }

    private function landDevelopment(): array
    {
        return [
            'title' => 'Land Development and Extension Services',
            'meta'  => 'Replanting and new planting subsidies, soil conservation, fertilizer assistance and the technical advisory service for tea smallholders.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('Functional Areas', 'Land Development and Extension Services', 'Land preparation, replanting subsidies, soil conservation, fertilizer distribution and technical advisory programmes — with the forms and the current status of each application window.'),
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('What this area covers', <<<'HTML'
<p>Land development and extension is the largest of the Authority's functional areas and the one most smallholders deal with. It covers everything to do with the state of the land and what grows on it: taking out old tea and putting in new, bringing suitable new land under the crop, holding the soil on sloping ground, getting fertilizer to the holding, and the field advice that ties all of it together.</p>
<p>The work is delivered through the regional offices and, beneath them, the 147 Tea Inspector and Extension Officer ranges that cover the tea-growing area. The officer for your range is the person who inspects your block, certifies the work at each stage of a subsidy, and advises on practice. Find them in the <a href="directory">Staff Directory</a>, filtered by district.</p>
HTML),
                    ['service_list', ['area' => 'land', 'title' => $this->loc('Services in this area')]],
                    $this->prose('Replanting: why it matters and how it works', <<<'HTML'
<p>Tea is a long-lived crop, but not an indefinite one. A bush past its productive life yields less every year, responds poorly to fertilizer and gives leaf that is harder to sell well. Replacing it is the single most effective thing that can be done for the productivity of a smallholding — and the single hardest, because the block produces nothing for the years between uprooting and the first plucking of the young tea.</p>
<p>The replanting subsidy exists to carry the household across that gap. It is paid in instalments tied to the stages of the work: on uprooting, on planting, and on the establishment of the young tea. Each instalment is released after the Tea Inspector has inspected the block and certified that the stage has actually been reached. That is slower than paying a lump sum on approval, and it is deliberate: it is what keeps the scheme honest and what makes it possible to fund it at a rate worth having.</p>
<p>Talk to your Tea Inspector <em>before</em> you uproot anything. A block uprooted before inspection cannot be certified at the first stage, and the assistance for that stage is lost.</p>
HTML),
                    $this->prose('Fertilizer and soil conservation', <<<'HTML'
<p>Fertilizer allocations are issued against the registered extent of the holding, at the price and within the window published in the issue notice for each district and season. Notices appear in the <a href="announcements">Announcements</a> section as they are released, and subscribers to the fertilizer and subsidy alert category are notified. If the extent recorded on the register is wrong, have your Tea Inspector correct it before the next issue rather than after it — the allocation is calculated from the register, not from the application.</p>
<p>Most smallholder tea in Sri Lanka is on sloping land, which makes soil conservation part of land development rather than an addition to it. Terracing, drains, cover crops and the layout of a replanted block all determine whether the investment stays on the hillside. Conservation works are supported, and conservation conditions are attached to replanting approval where the slope requires them.</p>
HTML),
                    $this->prose('Application windows and forms', <<<'HTML'
<p>Application windows for the subsidy schemes are opened by circular and announced on this site. The <a href="services">Services</a> section shows the current status of each — whether it is open or closed — alongside the eligibility conditions, the process, the documents you will need, the responsible division and the contact point.</p>
<p>Application forms for every scheme are in the <a href="downloads">Downloads</a> section under Application Forms, and can also be obtained from any regional office or from your Tea Inspector. Completed applications are submitted to the regional office covering the district in which the land lies.</p>
<p>If an application has been with an office for longer than the stated processing time, contact the Regional Manager quoting your registration number and the date of application. If it is still not resolved, use the <a href="feedback">feedback form</a>: every submission is given a reference number, routed to a responsible officer and tracked until it is answered.</p>
HTML),
                ]],
            ],
        ];
    }

    private function societies(): array
    {
        return [
            'title' => 'Societies Management — Tea Shakthi and Cooperatives',
            'meta'  => 'Tea Smallholder Development Societies: registration, benefits, member welfare programmes and a searchable society directory.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('Functional Areas', 'Societies Management', 'Tea Smallholder Development Societies, the registration process, the benefits of membership and the Tea Shakthi welfare programmes — with a searchable directory of registered societies.'),
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('Why societies exist', <<<'HTML'
<p>A tea smallholder acting alone has very little standing. The holding is small, the volume of leaf is small, the household has no store of working capital, and every transaction — buying fertilizer, selling leaf, borrowing against a crop — is made on terms set by someone larger. A Tea Smallholder Development Society changes that arithmetic by aggregating households that are individually small into a body that is not.</p>
<p>A society is a village-level organisation of registered smallholders within one Tea Inspector range. It receives input allocations on behalf of its members, buys in bulk, runs savings and welfare schemes, represents its members to collectors and factories, and is the channel through which many of the Authority's programmes reach the field. It is also, in practice, how the Authority hears from smallholders: an officer can meet a society; they cannot meet every household.</p>
HTML),
                    ['service_list', ['area' => 'societies', 'title' => $this->loc('Services in this area')]],
                    $this->prose('Forming and registering a society', <<<'HTML'
<p>A new society is formed at a meeting of the smallholders who intend to belong to it, with the Tea Inspector for the range present. The meeting adopts the model constitution, elects office bearers — chairperson, secretary and treasurer at a minimum — and resolves to apply for registration.</p>
<p>The application goes to the regional office with the minutes of the formation meeting, the member list with each member's smallholder registration number, and the adopted constitution. The regional office verifies the particulars, confirms that the members are registered smallholders within the range, and forwards the application. The registration certificate and the society's registration number are then issued.</p>
<p>Registration matters for practical reasons, not formal ones. An unregistered group cannot receive allocations on behalf of its members, cannot enter the Tea Shakthi programmes, and has no standing in the Authority's dealings. The full process, the documents required and the current processing time are set out under <a href="services">Services</a>.</p>
HTML),
                    $this->prose('Tea Shakthi and member welfare', <<<'HTML'
<p>Tea Shakthi is the banner under which the Authority runs welfare, savings and enterprise programmes with registered societies. What a particular society does under it depends on what its members need and what it is capable of running: group purchase of inputs, savings and credit schemes, welfare funds for illness and bereavement, support for value addition and small-scale processing, and training for office bearers at the <a href="hantana">Hantana National Training Centre</a>.</p>
<p>Programmes are applied for through the regional office. A society's standing — whether its registration is current, whether it holds meetings and keeps accounts, whether its member list is up to date — is assessed as part of the application, which is one of the reasons the Authority runs training for office bearers on exactly those things.</p>
HTML),
                    ['society_search', ['title' => $this->loc('Society directory')]],
                ]],
            ],
        ];
    }

    private function privacy(): array
    {
        return [
            'title' => 'Privacy Notice',
            'meta'  => 'How the Tea Small Holdings Development Authority handles personal information collected through this website.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('Legal', 'Privacy Notice', 'What this website collects, why, how long it is kept and what you can ask the Authority to do about it.'),
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('What this notice covers', <<<'HTML'
<p>This notice describes how the Tea Small Holdings Development Authority handles personal information collected through this website. It does not cover information the Authority holds about you for other reasons — your smallholder registration, a subsidy application made on paper, or your employment — which is governed by the Authority's general records practice and by the law under which it operates.</p>
HTML),
                    $this->prose('What the site collects', <<<'HTML'
<p><strong>Information you give us.</strong> When you submit a feedback form, a petition, a training application, a comment on a discussion topic or a subscription request, the site stores what you typed, together with the date and time. Each submission is given a reference number so that it can be tracked to an answer. Contact details are used to reply to you and to notify you of the outcome, and are not used for anything else.</p>
<p><strong>Information collected automatically.</strong> The site records page views for statistical purposes, as Clause 3.17 of the Authority's requirements provides for. What is recorded is the page, the time, the language version, the type of device and browser, an approximate location derived from the network, and the site that referred you, reduced to its host name. Visitors are counted using a value derived by one-way hash from the network address and the browser's own description of itself; the network address itself is not stored, and the hash cannot be reversed to identify a person or a household.</p>
<p><strong>What is not collected.</strong> The analytics are self-hosted on the Authority's own server. No visitor data is sent to any third-party analytics service. The site does not use advertising trackers. Requests that carry a Do Not Track header are not recorded at all, and neither are requests from search engine crawlers or requests to administrative pages.</p>
HTML),
                    $this->prose('Cookies and local storage', <<<'HTML'
<p>The site uses a session cookie to keep you signed in if you are a member of staff using the administration console or the Field Officer Portal, and to protect forms against cross-site request forgery. It stores your language choice and your light or dark preference in your browser's local storage so that the site opens the way you left it. None of these are used to build a profile of you.</p>
HTML),
                    $this->prose('Retention, disclosure and your rights', <<<'HTML'
<p>Analytics records are retained for the period set in the Authority's settings — 400 days by default — and are then deleted automatically. Feedback, petitions, applications and correspondence are retained under the Authority's records schedule, because they are part of the record of a decision taken by a public body.</p>
<p>Personal information collected through this site is not sold, rented or shared for marketing. It is disclosed only within the Authority to the officer responsible for answering the matter, and outside it only where the Authority is required by law to do so.</p>
<p>You may ask the Authority what information it holds about you, ask for it to be corrected if it is wrong, and ask for a subscription to be ended — every alert message carries a one-click unsubscribe link, and the subscription is deleted rather than merely flagged. Requests for information held by the Authority may also be made under the Right to Information Act No. 12 of 2016; the procedure is under <a href="services">Services</a>.</p>
<p>Questions about this notice should be addressed to the Information Officer at the Authority's Head Office, whose details are in the <a href="directory">Staff Directory</a>, or submitted through the <a href="feedback">feedback form</a>.</p>
HTML),
                ]],
            ],
        ];
    }

    private function terms(): array
    {
        return [
            'title' => 'Terms of Use',
            'meta'  => 'Terms on which the Tea Small Holdings Development Authority publishes this website, including the status of the information on it.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('Legal', 'Terms of Use', 'The status of the information published here, and the terms on which it may be used.'),
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('Status of the information', <<<'HTML'
<p>This website is published by the Tea Small Holdings Development Authority as a public information service. The Authority takes care to keep it accurate and current, and every page carries the date on which its content was last updated, drawn from the content management system's own revision record rather than typed by hand.</p>
<p>Nevertheless, the site is a description of the Authority's schemes and procedures, not the instrument that creates them. Where this site and a circular, regulation or the Tea Small Holdings Development Law differ, the circular, regulation or Law prevails. Subsidy rates, application windows, eligibility conditions and fees change; before acting on anything material, confirm it with your Tea Inspector or your regional office, or read the governing document under <a href="downloads">Downloads</a>.</p>
HTML),
                    $this->prose('Use of the content', <<<'HTML'
<p>The content of this site — text, photographs, documents, datasets and the organisational information published here — is the property of the Tea Small Holdings Development Authority unless it is credited to another source.</p>
<p>You may read, print, download and quote from it for personal use, for study and research, and for reporting on the Authority's work, provided the Authority is acknowledged as the source and the material is not altered in a way that changes its meaning. You may link to any page on this site without asking permission. Reproduction for commercial purposes, or in a way that suggests the Authority endorses a product, a service or an opinion, requires written permission.</p>
<p>Documents published under <a href="downloads">Downloads</a> may include material whose copyright rests elsewhere — an Act of Parliament, a standard, or a report by another body. Where that is the case, the terms of the originating body apply.</p>
HTML),
                    $this->prose('Links, submissions and availability', <<<'HTML'
<p>This site links to other government institutions and to organisations in the tea sector. Those sites are not under the Authority's control, and a link is not an endorsement of their content. The Authority is not responsible for what they publish.</p>
<p>Comments, feedback and petitions submitted through this site are moderated before publication where they are published at all. The Authority may decline to publish, or may remove, material that is defamatory, abusive, discloses another person's private information, is unlawful, or is submitted repeatedly to disrupt the service. Submitting material does not oblige the Authority to publish it; every submission is nevertheless recorded, given a reference and routed to a responsible officer.</p>
<p>The Authority aims to keep the site available at all times but does not guarantee uninterrupted service. Planned maintenance is announced in advance where practicable.</p>
HTML),
                ]],
            ],
        ];
    }

    private function accessibility(): array
    {
        return [
            'title' => 'Accessibility Statement',
            'meta'  => 'How this website meets WCAG 2.1 Level AA and the ICTA Guidelines for Developing Sri Lanka Government Websites, and how to report a barrier.',
            'sections' => [
                ['key' => 'hero', 'type' => 'hero', 'blocks' => [
                    $this->hero('Legal', 'Accessibility Statement', 'What this site does to be usable by everyone, what is known not to be there yet, and how to tell us when something blocks you.'),
                ]],
                ['key' => 'body', 'type' => 'generic', 'blocks' => [
                    $this->prose('Our commitment', <<<'HTML'
<p>The Tea Small Holdings Development Authority intends this website to be usable by everyone who needs it, including people who use a screen reader, navigate by keyboard alone, need large text or high contrast, or read with difficulty. That is a requirement of the Guidelines for Developing Sri Lanka Government Websites published by the Information and Communication Technology Agency, and it is also simply what a public information service ought to be.</p>
<p>The site is built to conform to the Web Content Accessibility Guidelines (WCAG) 2.1 at Level AA.</p>
HTML),
                    $this->prose('What has been done', <<<'HTML'
<ul>
<li><strong>Contrast.</strong> Text and interface colours are chosen against measured contrast ratios, not by eye, and meet the Level AA thresholds in both the light and the dark presentation.</li>
<li><strong>Keyboard.</strong> Every menu, dialog, filter and form can be operated from the keyboard alone. Focus is visible, focus order follows the reading order, and dialogs return focus to the control that opened them.</li>
<li><strong>Structure.</strong> Pages use real headings in order, landmark regions, lists that are lists, and tables with header cells — so a screen reader can move through a page by structure rather than word by word.</li>
<li><strong>Text alternatives.</strong> Images that carry meaning have alternative text; images that are decoration are hidden from assistive technology rather than described pointlessly.</li>
<li><strong>Language.</strong> Every page declares its language, so a screen reader pronounces Sinhala, Tamil and English correctly. Switching language keeps you on the page you were reading.</li>
<li><strong>Motion.</strong> Animation is reduced or removed for visitors whose system asks for reduced motion. Nothing moves, flashes or auto-advances in a way that could provoke a seizure.</li>
<li><strong>Scaling.</strong> The site is usable at 200% text size and on a small screen without horizontal scrolling.</li>
</ul>
HTML),
                    $this->prose('Known limitations', <<<'HTML'
<p>Two areas are not fully under the Authority's control, and it is more useful to state them than to claim otherwise.</p>
<p><strong>Uploaded documents.</strong> Some documents published under Downloads originate as scans or as files produced outside the Authority, and their internal accessibility varies. Scanned documents are put through text recognition at upload so that their contents can at least be searched and read aloud, but a scan is not the equal of a properly tagged document. If a document you need is not usable, ask for it in an accessible form and the Authority will supply one.</p>
<p><strong>Third-party content.</strong> Embedded content served by other organisations — a social media feed, an external map — is presented in a way that does not block the rest of the page if it fails to load, but its own accessibility is determined by the organisation that publishes it.</p>
HTML),
                    $this->prose('Telling us about a barrier', <<<'HTML'
<p>If something on this site prevents you from doing what you came to do, please tell us. Use the <a href="feedback">feedback form</a>, or contact the Authority by any of the routes on the <a href="contact">Contact</a> page. Describe the page and what happened; if you use assistive technology, telling us which one helps a great deal.</p>
<p>Every report is given a reference number and routed to a responsible officer, and you will be told what has been done. Where a fix will take time, the Authority will supply the information you needed by another route in the meantime.</p>
HTML),
                ]],
            ],
        ];
    }
}
