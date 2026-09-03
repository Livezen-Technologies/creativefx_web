<?php

namespace Modules\Tshda\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Starting data for the parts of the site that are lists rather than pages:
 * priority notices, FAQs, the document repository's categories, published
 * statistics, the Hantana training calendar, the society register, the current
 * discussion topic and the related-organisation links.
 *
 * Every table here is seeded only while it is empty. These are the Authority's
 * own records the moment TSHDA starts entering them, and a release that rewrote
 * them would undo the CMT's work — the same rule the menus follow.
 */
class TshdaDataSeeder extends Seeder
{
    public function run(): void
    {
        helper('norlanka');
        $this->seedNotices();
        $this->seedFaqs();
        $this->seedDocumentCategories();
        $this->seedStatistics();
        $this->seedProgrammes();
        $this->seedSocieties();
        $this->seedDiscussion();
        $this->seedOrgLinks();
    }

    private function empty(string $table): bool
    {
        return $this->db->table($table)->countAllResults() === 0;
    }

    private function loc(string $en): string
    {
        return json_encode(content_locales(['en' => $en]), JSON_UNESCAPED_UNICODE);
    }

    private function seedNotices(): void
    {
        if (! $this->empty('notices')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        $rows = [
            ['urgent', 'Fertilizer issue for the current season — district issue notices published',
                'Issue notices for each district are published in the Announcements section. Collect your allocation from the issuing point named on your district’s notice, within the window stated.', 'announcements'],
            ['notice', 'Replanting subsidy applications are open',
                'Applications for the replanting subsidy are being accepted at every Regional Office. Speak to your Tea Inspector before uprooting any block.', 'services/replanting-subsidy'],
            ['notice', 'Hantana National Training Centre — new programme calendar released',
                'The training calendar for the coming quarter is published, with residential and non-residential programmes for smallholders and society office bearers.', 'hantana'],
        ];

        $order = 0;
        foreach ($rows as [$severity, $title, $body, $url]) {
            $this->db->table('notices')->insert([
                'title'      => $this->loc($title),
                'body'       => $this->loc($body),
                'severity'   => $severity,
                'url'        => $url,
                'starts_at'  => $now,
                'sort_order' => $order++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedFaqs(): void
    {
        if (! $this->empty('faqs')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        $faqs = [
            ['registration', 'Who counts as a tea smallholder?',
                'A person lawfully in possession of land under tea not exceeding ten acres — about 4.05 hectares. The extent is measured across all the tea land held by the same person, not per block, so several small blocks are added together.'],
            ['registration', 'How do I register my holding with the Authority?',
                'Ask the Tea Inspector for your range for a registration form, or collect one from your Regional Office. The Inspector verifies the extent and the particulars of the holding, and the Regional Office issues your registration number. There is no fee.'],
            ['registration', 'I have lost my registration number. How do I recover it?',
                'Your Regional Office holds the register. Take your National Identity Card and, if you have it, any earlier correspondence or subsidy paperwork, and the office will confirm the number to you.'],
            ['subsidies', 'How much is the replanting subsidy and when is it paid?',
                'The rate is set by the Authority and revised from time to time; the current rate is published with the application form in the Downloads section. It is paid in instalments — on uprooting, on planting and on the establishment of the young tea — each released after a field inspection.'],
            ['subsidies', 'Can I replant only part of my holding?',
                'Yes. The block being replanted must meet the minimum extent stated in the guidelines, but it does not have to be the whole holding. Most smallholders replant in stages so that the holding continues to yield.'],
            ['subsidies', 'My application has been with the office for months. What can I do?',
                'Contact the Regional Manager of your district office, quoting your registration number and the date you applied. If the matter is still not resolved, use the feedback form on this site: every submission is given a reference number, routed to a responsible officer and tracked until it is answered.'],
            ['fertilizer', 'How is my fertilizer entitlement calculated?',
                'From the registered extent of your holding, at the rate published in the issue notice for the season. If the extent on the register is wrong, ask your Tea Inspector to have it corrected before the next issue.'],
            ['fertilizer', 'Where do I collect my allocation?',
                'From the issuing point named on your district’s issue notice — usually your society, or the Regional Office. Take your National Identity Card and your registration number.'],
            ['societies', 'What is a Tea Smallholder Development Society?',
                'A village-level body of registered smallholders within one Tea Inspector range. Societies organise input purchase, receive allocations on behalf of members, run welfare and savings programmes, and are the route through which many Authority programmes reach the field.'],
            ['societies', 'How do we form a new society?',
                'Convene a formation meeting with your Tea Inspector present, adopt the model constitution and elect office bearers, then submit the application with the minutes and the member list to your Regional Office.'],
            ['training', 'Who can attend a programme at Hantana?',
                'Registered smallholders, society office bearers and staff nominated by their division. Individual programmes may set further criteria, which are stated with the programme in the calendar.'],
            ['training', 'Is accommodation provided?',
                'Residential programmes are marked as such in the calendar and include accommodation at the Centre. Non-residential programmes do not.'],
            ['website', 'The site is not in my language. How do I change it?',
                'Use the language switcher in the header. The site is published in English, Sinhala and Tamil, and switching keeps you on the page you were reading rather than returning you to the home page. Your choice is remembered.'],
            ['website', 'How do I get alerts when something is published?',
                'Subscribe from the Announcements section and choose the categories you care about — tenders, vacancies, fertilizer and subsidy notices, training programmes or general news. You will be sent a confirmation link, and every message carries a one-click unsubscribe.'],
            ['website', 'How do I make a Right to Information request?',
                'Submit the RTI request form to the Information Officer at Head Office. You will receive an acknowledgement with a reference number, and a response within the statutory period.'],
        ];

        $order = 0;
        foreach ($faqs as [$category, $question, $answer]) {
            $this->db->table('faqs')->insert([
                'category'   => $category,
                'question'   => $this->loc($question),
                'answer'     => $this->loc($answer),
                'sort_order' => $order++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedDocumentCategories(): void
    {
        if (! $this->empty('document_categories')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        // The eight categories Clause 3.9 H names, in the order it names them.
        $categories = [
            ['tenders', 'Tender documents'],
            ['publications', 'Publications'],
            ['acts', 'Acts'],
            ['regulations', 'Regulations'],
            ['annual-reports', 'Annual reports'],
            ['application-forms', 'Application forms'],
            ['standards-guides', 'Standards and guides'],
            ['recruitment', 'Recruitment notices'],
        ];

        $order = 0;
        foreach ($categories as [$slug, $name]) {
            $this->db->table('document_categories')->insert([
                'slug'       => $slug,
                'name'       => $this->loc($name),
                'sort_order' => $order++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedStatistics(): void
    {
        if (! $this->empty('statistics_datasets')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        // Placeholder shapes, not published figures. The Authority's own data
        // is loaded during content gathering; what these rows do is give the
        // Statistics screens a real structure to render and the CMT something
        // to replace rather than something to invent.
        $datasets = [
            [
                'slug' => 'smallholdings-by-district',
                'title' => 'Registered tea smallholdings by district',
                'description' => 'Number of registered tea smallholdings in each district covered by the Authority. Figures are to be confirmed against the Authority’s register before publication.',
                'unit' => 'holdings', 'chart' => 'bar', 'period' => 'To be confirmed',
                'columns' => ['District', 'Registered holdings'],
                'rows' => [
                    ['Galle', null], ['Matara', null], ['Ratnapura', null],
                    ['Kegalle', null], ['Kalutara', null], ['Kandy', null],
                    ['Nuwara Eliya', null], ['Badulla', null],
                ],
            ],
            [
                'slug' => 'extension-structure',
                'title' => 'Field extension structure',
                'description' => 'The Authority’s service-delivery structure at the grassroots: Tea Inspector and Extension Officer ranges, the sub offices that supervise them, and the regions above.',
                'unit' => 'units', 'chart' => 'table', 'period' => 'Current',
                'columns' => ['Level', 'Number'],
                'rows' => [
                    ['Tea Inspector / Extension Officer ranges', 147],
                    ['Sub offices', 26],
                    ['Regions', 8],
                    ['Regional offices', 9],
                    ['National training centres', 1],
                ],
            ],
            [
                'slug' => 'replanting-progress',
                'title' => 'Replanting and new planting progress',
                'description' => 'Extent replanted and newly planted under Authority subsidy, by year. To be loaded from the Authority’s progress returns.',
                'unit' => 'hectares', 'chart' => 'line', 'period' => 'To be confirmed',
                'columns' => ['Year', 'Replanted (ha)', 'New planting (ha)'],
                'rows' => [
                    ['2021', null, null], ['2022', null, null], ['2023', null, null],
                    ['2024', null, null], ['2025', null, null],
                ],
            ],
        ];

        $order = 0;
        foreach ($datasets as $d) {
            $this->db->table('statistics_datasets')->insert([
                'slug'        => $d['slug'],
                'title'       => $this->loc($d['title']),
                'description' => $this->loc($d['description']),
                'unit'        => $this->loc($d['unit']),
                'chart'       => $d['chart'],
                'columns'     => json_encode(array_map(fn ($c) => content_locales(['en' => $c]), $d['columns']), JSON_UNESCAPED_UNICODE),
                'rows'        => json_encode($d['rows'], JSON_UNESCAPED_UNICODE),
                'source'      => $this->loc('Tea Small Holdings Development Authority'),
                'period'      => $d['period'],
                'sort_order'  => $order++,
                'status'      => 'published',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    private function seedProgrammes(): void
    {
        if (! $this->empty('programmes')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        // Dates are relative to the seed run so the calendar is never shipped
        // already in the past — a training calendar whose every entry has
        // expired reads as a site nobody maintains.
        $programmes = [
            [
                'slug' => 'good-agricultural-practice',
                'title' => 'Good agricultural practice for tea smallholdings',
                'summary' => 'Three days on field practice: pruning cycles, plucking rounds, shade, weed and pest management, and the leaf standards buyers apply.',
                'audience' => 'Registered smallholders and society office bearers',
                'offset' => 21, 'days' => 3, 'closes' => 7, 'capacity' => 40, 'residential' => 1,
                'fee' => 'No course fee. Accommodation and meals provided.',
            ],
            [
                'slug' => 'nursery-management',
                'title' => 'Tea nursery management and cultivar selection',
                'summary' => 'Two days for nursery operators and prospective operators: site selection, mother bushes, cultivar purity, propagation and the records the Authority requires.',
                'audience' => 'Nursery operators, prospective operators and field staff',
                'offset' => 35, 'days' => 2, 'closes' => 21, 'capacity' => 30, 'residential' => 1,
                'fee' => 'As published with the programme.',
            ],
            [
                'slug' => 'society-office-bearers',
                'title' => 'Society management for office bearers',
                'summary' => 'Two days on running a Tea Smallholder Development Society: the constitution, meetings and minutes, accounts, member records and the Authority’s reporting requirements.',
                'audience' => 'Chairpersons, secretaries and treasurers of registered societies',
                'offset' => 49, 'days' => 2, 'closes' => 35, 'capacity' => 35, 'residential' => 1,
                'fee' => 'No course fee.',
            ],
            [
                'slug' => 'soil-conservation-sloping-land',
                'title' => 'Soil conservation on sloping tea land',
                'summary' => 'One day, non-residential: reading a slope, laying out drains and terraces, cover crops, and the conservation conditions attached to replanting approval.',
                'audience' => 'Smallholders replanting on sloping land, and field staff',
                'offset' => 28, 'days' => 1, 'closes' => 14, 'capacity' => 50, 'residential' => 0,
                'fee' => 'No course fee. Lunch provided.',
            ],
            [
                'slug' => 'value-addition-and-marketing',
                'title' => 'Value addition and marketing for smallholder groups',
                'summary' => 'Three days for society groups pursuing value addition: quality, packaging, certification pathways, costing and routes to market.',
                'audience' => 'Society groups and Tea Shakthi enterprise participants',
                'offset' => 63, 'days' => 3, 'closes' => 49, 'capacity' => 25, 'residential' => 1,
                'fee' => 'As published with the programme.',
            ],
        ];

        $order = 0;
        foreach ($programmes as $p) {
            $starts = date('Y-m-d', strtotime("+{$p['offset']} days"));
            $ends   = date('Y-m-d', strtotime("+" . ($p['offset'] + $p['days'] - 1) . " days"));
            $closes = date('Y-m-d', strtotime("+{$p['closes']} days"));

            $this->db->table('programmes')->insert([
                'slug'        => $p['slug'],
                'title'       => $this->loc($p['title']),
                'summary'     => $this->loc($p['summary']),
                'description' => $this->loc($p['summary']),
                'audience'    => $this->loc($p['audience']),
                'starts_on'   => $starts,
                'ends_on'     => $ends,
                'closes_on'   => $closes,
                'capacity'    => $p['capacity'],
                'booked'      => 0,
                'residential' => $p['residential'],
                'fee'         => $this->loc($p['fee']),
                'venue'       => $this->loc('Hantana National Training Centre, Kandy'),
                'sort_order'  => $order++,
                'status'      => 'published',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    private function seedSocieties(): void
    {
        if (! $this->empty('societies')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        // A handful of rows so the register's search, filters and paging can be
        // exercised and reviewed. The Authority's register is imported during
        // content gathering; these carry a "SPECIMEN" registration prefix so a
        // specimen row can never be mistaken for a real society.
        $districts = ['Galle', 'Matara', 'Ratnapura', 'Kegalle', 'Kalutara', 'Kandy', 'Nuwara Eliya', 'Badulla'];

        $n = 1;
        foreach ($districts as $district) {
            foreach (['tsds', 'teashakthi'] as $kind) {
                $this->db->table('societies')->insert([
                    'registration'  => sprintf('SPECIMEN/%s/%03d', strtoupper(substr($district, 0, 3)), $n),
                    'name'          => sprintf('%s %s Society (specimen record)', $district, $kind === 'tsds' ? 'Tea Smallholder Development' : 'Tea Shakthi'),
                    'kind'          => $kind,
                    'district'      => $district,
                    'division'      => $district,
                    'members'       => 0,
                    'status'        => 'published',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $n++;
            }
        }
    }

    private function seedDiscussion(): void
    {
        if (! $this->empty('discussion_topics')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        $this->db->table('discussion_topics')->insert([
            'slug'       => 'leaf-quality-and-price',
            'title'      => $this->loc('Leaf quality and the price a smallholder is paid'),
            'body'       => $this->loc('The Authority is inviting comment from smallholders, society office bearers and buyers on how leaf quality is assessed at collection, and how that assessment reaches the price paid to the smallholder. Tell us what happens at your collecting point, and what would make the assessment fairer and clearer. Comments are read by the Societies & Marketing Division and are published after moderation.'),
            'opens_at'   => $now,
            'closes_at'  => date('Y-m-d H:i:s', strtotime('+90 days')),
            'is_current' => 1,
            'status'     => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedOrgLinks(): void
    {
        if (! $this->empty('org_links')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        $links = [
            ['related', 'Ministry of Plantation and Community Infrastructure', 'https://plantation.gov.lk/'],
            ['related', 'Sri Lanka Tea Board', 'https://srilankateaboard.lk/'],
            ['related', 'Tea Research Institute of Sri Lanka', 'https://www.tri.lk/'],
            ['related', 'Sri Lanka Tea Factory Owners’ Association', 'https://www.teafactoryowners.lk/'],
            ['government', 'Sri Lanka Government Web Portal', 'https://www.gov.lk/'],
            ['government', 'Local Languages Website', 'https://www.locallanguages.lk/'],
            ['government', 'Government Information Centre (1919)', 'https://gic.gov.lk/'],
            ['government', 'Information and Communication Technology Agency', 'https://www.icta.lk/'],
        ];

        $order = 0;
        foreach ($links as [$group, $name, $url]) {
            $this->db->table('org_links')->insert([
                'name'       => $this->loc($name),
                'url'        => $url,
                'group_key'  => $group,
                'sort_order' => $order++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
