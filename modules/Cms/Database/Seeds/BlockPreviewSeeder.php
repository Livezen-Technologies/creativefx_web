<?php

namespace Modules\Cms\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * A throwaway page that renders every new CreativeFX block once, so the block
 * library can be eyeballed on one screen.
 *
 *   php spark db:seed "Modules\Cms\Database\Seeds\BlockPreviewSeeder"
 *   → /en/block-preview
 *
 * Not part of DatabaseSeeder and not linked from anywhere. Delete the page from
 * Admin -> Pages when you are done with it.
 */
class BlockPreviewSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $l   = static fn (string $s): array => ['en' => $s];

        $blocks = [
            ['pagehero', [
                'eyebrow'  => $l('Block library'),
                'title'    => $l('Every new CreativeFX block, once'),
                'subtitle' => $l('A scratch page for design review. Not linked from the site.'),
                'video'    => '',
                'poster'   => '/media/placeholders/hero-services.svg',
            ]],
            ['services_grid', [
                'eyebrow' => $l('What we do'),
                'title'   => $l('Services grid'),
                'intro'   => $l('Six cards, image or icon, whole card clickable.'),
                'items'   => [
                    ['title' => $l('Photography & Videography'), 'text' => $l('Corporate, product, event and campaign work.'), 'image' => '/media/placeholders/service-photography-videography.svg', 'icon' => 'camera', 'url' => 'services/photography-videography', 'cta' => $l('Explore Service')],
                    ['title' => $l('Podcast Studio'), 'text' => $l('Recording, multi-camera film, editing and clips.'), 'image' => '/media/placeholders/service-podcast-studio.svg', 'icon' => 'mic', 'url' => 'services/podcast-studio', 'cta' => $l('Explore Service')],
                    ['title' => $l('Live Streaming'), 'text' => $l('Switched multi-camera broadcasts with live graphics.'), 'image' => '', 'icon' => 'broadcast', 'url' => 'services/live-streaming', 'cta' => $l('Explore Service')],
                ],
            ]],
            ['service_features', [
                'title'   => $l('Service features — short form'),
                'intro'   => $l('Plain labels render as a checkmark list.'),
                'columns' => 3,
                'items'   => [$l('Corporate'), $l('Product'), $l('Events'), $l('Fashion'), $l('Commercial'), $l('Wedding'), $l('Brand campaigns'), $l('Social media')],
            ]],
            ['service_features', [
                'title' => $l('Service features — long form'),
                'items' => [
                    ['title' => $l('Recording studio'), 'text' => $l('Treated room, booked by the hour.')],
                    ['title' => $l('Multi-camera setup'), 'text' => $l('Three angles, switched live or in the edit.')],
                    ['title' => $l('Short-form clips'), 'text' => $l('Vertical cuts sized per platform.')],
                ],
            ]],
            ['packages', [
                'title' => $l('Packages'),
                'intro' => $l('Four tiers, the middle one featured.'),
                'items' => [
                    ['name' => $l('Starter'), 'price' => '75,000', 'currency' => 'LKR', 'period' => $l('per project'), 'summary' => $l('A half-day shoot and a single deliverable.'), 'features' => [$l('Half-day shoot'), $l('One edited video'), $l('10 retouched stills')], 'url' => 'quote', 'cta' => $l('Request a Quote')],
                    ['name' => $l('Professional'), 'price' => '185,000', 'currency' => 'LKR', 'period' => $l('per project'), 'summary' => $l('A full production day with a cut per platform.'), 'features' => [$l('Full-day shoot'), $l('Two-camera setup'), $l('Three platform cuts'), $l('30 retouched stills')], 'featured' => true, 'url' => 'quote', 'cta' => $l('Request a Quote')],
                    ['name' => $l('Premium'), 'price' => '420,000', 'currency' => 'LKR', 'period' => $l('per project'), 'summary' => $l('Multi-day campaign production end to end.'), 'features' => [$l('Multi-day shoot'), $l('Creative direction'), $l('Full crew'), $l('Campaign asset set')], 'url' => 'quote', 'cta' => $l('Request a Quote')],
                    ['name' => $l('Custom'), 'price' => '', 'period' => $l(''), 'summary' => $l('Retainers and anything that does not fit a tier.'), 'features' => [$l('Scoped with you'), $l('Monthly retainer option')], 'url' => 'quote', 'cta' => $l("Let's Talk")],
                ],
            ]],
            ['testimonials', [
                'title' => $l('Testimonials'),
                'intro' => $l('Carousel with rating, photo fallback and dots.'),
                'items' => [
                    ['quote' => $l('They turned a one-day shoot into a quarter of social content. The edit came back faster than we planned for.'), 'name' => $l('Nadeesha Perera'), 'role' => $l('Marketing Lead'), 'company' => $l('Ceylon Tea Exports'), 'photo' => '/media/placeholders/team-01.svg', 'rating' => 5],
                    ['quote' => $l('The live stream ran for six hours without a drop, and the recording was ready to cut the same evening.'), 'name' => $l('Rajitha Fernando'), 'role' => $l('Events Director'), 'company' => $l('Colombo City Centre'), 'photo' => '', 'rating' => 5],
                    ['quote' => $l('Our podcast finally looks like the brand. The clips alone doubled our reach.'), 'name' => $l('Ayesha Jayawardena'), 'role' => $l('Founder'), 'company' => $l('Island Ventures'), 'photo' => '/media/placeholders/team-03.svg', 'rating' => 4],
                ],
            ]],
            ['team_grid', [
                'title' => $l('Team grid'),
                'intro' => $l('Portraits with bio on hover and social links.'),
                'items' => [
                    ['name' => $l('Dinuka Silva'), 'role' => $l('Creative Director'), 'bio' => $l('Fifteen years across commercial film and brand campaigns.'), 'photo' => '/media/placeholders/team-01.svg', 'linkedin' => 'https://example.com', 'instagram' => 'https://example.com'],
                    ['name' => $l('Tharindu Alwis'), 'role' => $l('Head of Production'), 'bio' => $l('Runs the floor, the crew and the schedule.'), 'photo' => '/media/placeholders/team-02.svg', 'email' => 'hello@creativefx.lk'],
                    ['name' => $l('Shanika Mendis'), 'role' => $l('Lead Editor'), 'bio' => $l('Colour, grade and the final cut.'), 'photo' => '/media/placeholders/team-03.svg', 'linkedin' => 'https://example.com'],
                    ['name' => $l('Kasun Bandara'), 'role' => $l('Digital Strategist'), 'bio' => $l('Paid media, analytics and the reporting nobody else enjoys.'), 'photo' => '/media/placeholders/team-04.svg'],
                ],
            ]],
            ['faq', [
                'title' => $l('FAQ'),
                'intro' => $l('Native details/summary, plus FAQPage structured data.'),
                'items' => [
                    ['question' => $l('How far in advance should we book?'), 'answer' => $l('Two weeks is comfortable for a single shoot day. Campaign work and multi-day productions need a month so we can hold the crew and the kit.')],
                    ['question' => $l('Do you travel outside Colombo?'), 'answer' => $l('Yes, island-wide. Travel and accommodation are quoted separately and agreed before the shoot.')],
                    ['question' => $l('When do we get the files?'), 'answer' => $l('A first cut within five working days, and the final delivery within ten once feedback is in.')],
                ],
            ]],
            ['logo_wall', [
                'title'     => $l('Logo wall'),
                'intro'     => $l('Light plates so dark logo files stay legible.'),
                'grayscale' => true,
                'items'     => [
                    ['name' => $l('Client One'), 'logo' => '/media/placeholders/client-01.svg'],
                    ['name' => $l('Client Two'), 'logo' => '/media/placeholders/client-02.svg'],
                    ['name' => $l('Client Three'), 'logo' => '/media/placeholders/client-03.svg'],
                    ['name' => $l('Client Four'), 'logo' => '/media/placeholders/client-04.svg'],
                    ['name' => $l('No Logo File'), 'logo' => '/media/placeholders/does-not-exist.svg'],
                    ['name' => $l('Client Six'), 'logo' => '/media/placeholders/client-06.svg'],
                ],
            ]],
            ['cta', [
                'title'  => $l('Have a project in mind?'),
                'text'   => $l('Tell us what you are making and we will come back with a plan and a price.'),
                'button' => $l('Request a Quote'),
                'url'    => 'quote',
            ]],
        ];

        $pages = $this->db->table('pages');
        $data  = [
            'slug'       => 'block-preview',
            'title'      => json_encode($l('Block preview'), JSON_UNESCAPED_UNICODE),
            'template'   => 'default',
            'is_home'    => 0,
            'status'     => 'published',
            'updated_at' => $now,
        ];

        if ($pages->where('slug', 'block-preview')->get()->getRowArray() === null) {
            $pages->insert($data + ['created_at' => $now]);
        } else {
            $pages->where('slug', 'block-preview')->update($data);
        }
        $pageId = (int) $pages->where('slug', 'block-preview')->get()->getRowArray()['id'];

        $ids = array_column(
            $this->db->table('page_sections')->select('id')->where('page_id', $pageId)->get()->getResultArray(),
            'id'
        );
        if ($ids !== []) {
            $this->db->table('page_blocks')->whereIn('section_id', $ids)->delete();
            $this->db->table('page_sections')->where('page_id', $pageId)->delete();
        }

        $this->db->table('page_sections')->insert([
            'page_id' => $pageId, 'key' => 'preview', 'type' => 'generic',
            'sort_order' => 0, 'status' => 'published', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $sectionId = (int) $this->db->insertID();

        $i = 0;
        foreach ($blocks as [$type, $content]) {
            $this->db->table('page_blocks')->insert([
                'section_id' => $sectionId,
                'type'       => $type,
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                'sort_order' => $i++,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
