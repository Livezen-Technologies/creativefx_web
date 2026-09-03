<?php

namespace Modules\News\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The newsroom's categories and its starting articles.
 *
 * Clause 3.9 B.II and E.c both feed off this table: the home page shows the
 * latest items, and the Announcements section is the same listing pinned to
 * the announcements category. The categories are the ones the Authority
 * actually publishes under, so an editor never has to file a fertilizer issue
 * notice under "Company News".
 *
 * Upserts by slug and never deletes, so editorial changes made in the console
 * survive a release. The articles are written from the Authority's published
 * record and are a starting draft for the Content Management Team; dates are
 * relative to the seed run so the newsroom is never shipped already stale.
 */
class NewsSeeder extends Seeder
{
    public function run(): void
    {
        helper('norlanka');
        $now = date('Y-m-d H:i:s');

        $this->seedCategories($now);
        $this->seedPosts($now);
    }

    private function loc(string $en): string
    {
        return json_encode(content_locales(['en' => $en]), JSON_UNESCAPED_UNICODE);
    }

    private function seedCategories(string $now): void
    {
        $categories = [
            ['announcements',  'Announcements & Notices'],
            ['press-releases', 'Press Releases'],
            ['events',         'Events'],
            ['tenders',        'Tenders'],
            ['circulars',      'Circulars'],
            ['programmes',     'Programmes & Projects'],
        ];

        foreach ($categories as $i => [$slug, $name]) {
            $exists = $this->db->table('news_categories')->where('slug', $slug)->countAllResults() > 0;
            if ($exists) {
                continue;
            }
            $this->db->table('news_categories')->insert([
                'slug'       => $slug,
                'name'       => $this->loc($name),
                'sort_order' => $i + 1,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedPosts(string $now): void
    {
        $catId = [];
        foreach ($this->db->table('news_categories')->get()->getResultArray() as $row) {
            $catId[$row['slug']] = (int) $row['id'];
        }

        foreach ($this->posts() as $post) {
            $exists = $this->db->table('news_posts')->where('slug', $post['slug'])->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $this->db->table('news_posts')->insert([
                'category_id'      => $catId[$post['category']] ?? null,
                'slug'             => $post['slug'],
                'title'            => $this->loc($post['title']),
                'excerpt'          => $this->loc($post['excerpt']),
                'body'             => $this->loc($post['body']),
                'author'           => 'Tea Small Holdings Development Authority',
                'meta_description' => $this->loc($post['excerpt']),
                'status'           => 'published',
                'published_at'     => date('Y-m-d H:i:s', strtotime('-' . $post['days'] . ' days')),
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    private function posts(): array
    {
        return [
            [
                'slug' => 'fertilizer-issue-notice-current-season',
                'category' => 'announcements',
                'days' => 2,
                'title' => 'Fertilizer issue notices published for every district',
                'excerpt' => 'Issue notices for the current season have been published for each district, naming the issuing point, the entitlement rate and the collection window.',
                'body' => '<p>Fertilizer issue notices for the current season have been published for every district covered by the Authority. Each notice names the issuing point, the rate at which entitlement is calculated, the subsidised price and the window within which the allocation must be collected.</p>
<p>Entitlement is calculated from the registered extent of the holding. Smallholders whose registered extent is out of date should ask their Tea Inspector to have the register corrected <em>before</em> the issue rather than after it — the allocation is read from the register, not from the application.</p>
<p>Collect your allocation from the issuing point named on your district notice, taking your National Identity Card and your smallholder registration number. Where a society is the issuing point, take your society membership number as well.</p>
<p>District notices are available under Downloads, and are sent to subscribers of the fertilizer and subsidy alert category.</p>',
            ],
            [
                'slug' => 'replanting-subsidy-applications-open',
                'category' => 'announcements',
                'days' => 6,
                'title' => 'Replanting subsidy applications are open at every Regional Office',
                'excerpt' => 'Applications for the replanting subsidy are being accepted at all regional offices. Speak to your Tea Inspector before uprooting any block.',
                'body' => '<p>Applications for the replanting subsidy are being accepted at every Regional Office of the Authority.</p>
<p>The subsidy is paid in instalments tied to the stages of the work — on uprooting, on planting, and on the establishment of the young tea — and each instalment is released only after the Tea Inspector has inspected the block and certified that the stage has been reached.</p>
<p><strong>Speak to your Tea Inspector before you uproot anything.</strong> A block uprooted before it has been inspected cannot be certified at the first stage, and the assistance for that stage is lost. This is the single most common reason a replanting application fails, and it is entirely avoidable.</p>
<p>The application form, the eligibility conditions and the current rate are published in the Services and Downloads sections of this site.</p>',
            ],
            [
                'slug' => 'hantana-training-calendar-released',
                'category' => 'programmes',
                'days' => 9,
                'title' => 'Hantana National Training Centre releases its new programme calendar',
                'excerpt' => 'Residential and non-residential programmes for smallholders, society office bearers and field staff are now open for online application.',
                'body' => '<p>The Hantana National Training Centre has released its programme calendar for the coming quarter. It includes residential and non-residential programmes for registered smallholders, society office bearers and the Authority’s own field staff.</p>
<p>Programmes cover good agricultural practice, nursery management and cultivar selection, society management for office bearers, soil conservation on sloping land, and value addition and marketing for smallholder groups.</p>
<p>Applications can now be made online, in English, Sinhala or Tamil. Each programme states its own capacity, and closes to applications automatically once it is full; where a programme is over-subscribed, applications are placed on a waiting list rather than refused. Applicants are notified of the outcome, and every application is given a reference number.</p>',
            ],
            [
                'slug' => 'society-registration-drive',
                'category' => 'programmes',
                'days' => 16,
                'title' => 'Society formation and strengthening programme under way',
                'excerpt' => 'The Authority is working with regional offices to form new Tea Smallholder Development Societies and to strengthen the standing of existing ones.',
                'body' => '<p>The Authority has begun a programme of society formation and strengthening across the tea-growing districts, working through the regional offices and the Tea Inspector ranges.</p>
<p>A Tea Smallholder Development Society is a village-level body of registered smallholders within one Tea Inspector range. It receives input allocations on behalf of its members, buys in bulk, runs savings and welfare schemes and represents its members to collectors and factories. Registration is what allows a group to do any of that.</p>
<p>Groups interested in forming a society should speak to the Tea Inspector for their range. The formation process, the documents required and the current processing time are set out under Services.</p>
<p>The programme also includes training for existing office bearers at the Hantana National Training Centre, on the constitution, meetings and minutes, accounts and member records — the things a society’s standing is assessed against.</p>',
            ],
            [
                'slug' => 'trilingual-website-launched',
                'category' => 'press-releases',
                'days' => 1,
                'title' => 'The Authority launches its trilingual website',
                'excerpt' => 'The Authority’s new website is published in English, Sinhala and Tamil, with online applications, a searchable staff directory and tracked public feedback.',
                'body' => '<p>The Tea Small Holdings Development Authority has launched its new website, published in all three languages — English, Sinhala and Tamil — with the language switcher on every page keeping the reader on the page they were reading.</p>
<p>The site brings together, for the first time in one place: a complete service catalogue stating who can apply for each service, what the process is and which documents are needed; a searchable directory of every office and officer, filterable by division, district and subject area; the Authority’s published statistics, with downloadable datasets; a central document repository whose search reaches inside the documents themselves; and online application to programmes at the Hantana National Training Centre.</p>
<p>Members of the public can send feedback, questions and petitions through the site. Every submission is given a reference number, routed to a responsible officer and tracked until it is answered — and the person who submitted it can check its progress at any time with that reference.</p>
<p>The site is built to the Guidelines for Developing Sri Lanka Government Websites published by the Information and Communication Technology Agency, and targets WCAG 2.1 Level AA for accessibility.</p>',
            ],
            [
                'slug' => 'leaf-quality-consultation',
                'category' => 'announcements',
                'days' => 12,
                'title' => 'Public consultation opens on leaf quality and price',
                'excerpt' => 'The Authority is inviting comment from smallholders, society office bearers and buyers on how leaf quality is assessed at collection.',
                'body' => '<p>The Authority has opened a public consultation on how green leaf quality is assessed at collection, and on how that assessment reaches the price paid to the smallholder.</p>
<p>The Societies & Marketing Division is inviting comment from smallholders, society office bearers, collectors and factories. Comments are published after moderation and are read by the Division.</p>
<p>The consultation is open for ninety days. Contribute through the Discussion section of this site, or write to the Division at Head Office.</p>',
            ],
        ];
    }
}
