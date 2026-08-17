<?php

namespace Modules\Services\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The six services CreativeFX sells, in the order they appear in the header
 * dropdown, the footer and the Services overview grid.
 *
 * Upserts by slug — never updates, never deletes — so wording and ordering
 * changed in Admin survive a redeploy. Copy here is the short, repeated kind
 * (name, one-line tagline, two-sentence summary); the long-form service page
 * lives in the CMS under the slug 'services/<slug>'.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $j   = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);

        $services = [
            [
                'slug'    => 'photography-videography',
                'icon'    => 'camera',
                'name'    => ['en' => 'Photography & Videography'],
                'tagline' => ['en' => 'Corporate, product, event and campaign work, shot and finished in one place.'],
                'summary' => ['en' => 'We cover corporate and product shoots, events, fashion, commercial films, weddings and full brand campaigns. Every project is graded and delivered in the formats you actually publish in, so a single shoot day feeds print, web and social.'],
            ],
            [
                'slug'    => 'podcast-studio',
                'icon'    => 'mic',
                'name'    => ['en' => 'Podcast Studio'],
                'tagline' => ['en' => 'A studio booked by the hour for recording, filming and cutting your show.'],
                'summary' => ['en' => 'Our Colombo studio handles the recording and the multi-camera film, then the editing, branding and colour that turn it into a finished episode. You leave with the full-length cut and a set of short-form clips sized for Instagram, TikTok and YouTube Shorts.'],
            ],
            [
                'slug'    => 'live-streaming',
                'icon'    => 'broadcast',
                'name'    => ['en' => 'Live Streaming'],
                'tagline' => ['en' => 'Multi-camera broadcasts to YouTube, Facebook, Instagram and LinkedIn.'],
                'summary' => ['en' => 'We stream conferences, webinars, product launches, weddings and concerts with a switched multi-camera feed, live graphics and a monitored connection. Hybrid events run to the room and the stream at once, and the full recording is ready to edit the same day.'],
            ],
            [
                'slug'    => 'gear-renting',
                'icon'    => 'box',
                'name'    => ['en' => 'Gear Renting'],
                'tagline' => ['en' => 'Cameras, lenses, lighting and audio, out on daily and weekly rates.'],
                'summary' => ['en' => 'Rent cameras, lenses, lighting, audio kits, tripods, gimbals, drones and studio equipment, tested and charged before every collection. Rates are quoted in LKR by the day or the week, and an operator can go out with the kit when you need one.'],
            ],
            [
                'slug'    => 'social-media-advertising',
                'icon'    => 'megaphone',
                'name'    => ['en' => 'Social Media Advertising'],
                'tagline' => ['en' => 'Paid campaigns on Meta, TikTok and YouTube, built around creative that converts.'],
                'summary' => ['en' => 'We set the strategy, produce the creative and run the campaigns across Facebook, Instagram, TikTok and YouTube. Audience testing, retargeting and weekly optimisation keep the cost per result moving in the right direction.'],
            ],
            [
                'slug'    => 'digital-marketing',
                'icon'    => 'chart',
                'name'    => ['en' => 'Digital Marketing'],
                'tagline' => ['en' => 'SEO, content, Google Ads and analytics working to a single plan.'],
                'summary' => ['en' => 'We handle search optimisation, content, Google Ads, email and the analytics underneath them. Conversion optimisation ties the channels together, so the traffic you pay for turns into enquiries you can measure.'],
            ],
        ];

        $table = $this->db->table('services');

        foreach ($services as $i => $service) {
            $exists = $table->select('id')->where('slug', $service['slug'])->get()->getRowArray();
            $table->resetQuery();

            if ($exists !== null) {
                continue; // Editors own the row after the first seed.
            }

            $this->db->table('services')->insert([
                'slug'       => $service['slug'],
                'name'       => $j($service['name']),
                'tagline'    => $j($service['tagline']),
                'summary'    => $j($service['summary']),
                'icon'       => $service['icon'],
                'card_image' => '/media/placeholders/service-' . $service['slug'] . '.svg',
                'hero_image' => '/media/placeholders/service-hero-' . $service['slug'] . '.svg',
                'sort_order' => $i + 1,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
