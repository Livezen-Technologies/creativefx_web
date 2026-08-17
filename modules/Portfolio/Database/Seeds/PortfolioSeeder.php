<?php

namespace Modules\Portfolio\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Portfolio starter content: the nine disciplines CreativeFX sells, and twelve
 * projects written as short case studies (brief, approach, measured result).
 *
 * Upserts by slug and never updates or deletes, so anything an editor changes
 * in the admin console survives a redeploy. Use a data migration if an existing
 * install needs a seeded value corrected.
 */
class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->seedCategories($now);
        $this->seedProjects($now);
    }

    private function seedCategories(string $now): void
    {
        $j = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);

        $categories = [
            ['photography',       ['en' => 'Photography']],
            ['videography',       ['en' => 'Videography']],
            ['commercial',        ['en' => 'Commercial']],
            ['corporate',         ['en' => 'Corporate']],
            ['events',            ['en' => 'Events']],
            ['podcast',           ['en' => 'Podcast']],
            ['live-streaming',    ['en' => 'Live Streaming']],
            ['advertising',       ['en' => 'Advertising']],
            ['digital-marketing', ['en' => 'Digital Marketing']],
        ];

        $table = $this->db->table('portfolio_categories');
        foreach ($categories as $i => [$slug, $name]) {
            $exists = $table->select('id')->where('slug', $slug)->get()->getRowArray();
            $table->resetQuery();
            if ($exists === null) {
                $this->db->table('portfolio_categories')->insert([
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

    private function seedProjects(string $now): void
    {
        $j = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);
        $g = static fn (array $frames): string => json_encode(
            array_map(static fn (array $f): array => ['src' => $f[0], 'caption' => ['en' => $f[1]]], $frames),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        // slug => id map for category references.
        $catId = [];
        foreach ($this->db->table('portfolio_categories')->select('id, slug')->get()->getResultArray() as $row) {
            $catId[$row['slug']] = (int) $row['id'];
        }

        $projects = [
            [
                'slug'         => 'ceylon-tea-exports-highland-harvest',
                'category_id'  => $catId['commercial'] ?? null,
                'title'        => $j(['en' => 'Highland Harvest — an origin film for Ceylon Tea Exports']),
                'client'       => 'Ceylon Tea Exports',
                'industry'     => 'Food & Beverage',
                'service'      => 'Commercial film production',
                'project_date' => '2026-03-14',
                'year'         => '2026',
                'excerpt'      => $j(['en' => 'A three-minute origin film shot across two Nuwara Eliya estates and the Colombo packing floor, cut into six edits for trade shows, buyer meetings and social.']),
                'description'  => $j(['en' => '<p>Ceylon Tea Exports sells single-origin Ceylon tea to distributors in Europe and the Gulf. Buyers arrive at the trade stand having never seen an estate, and the sales team was carrying that story in a slide deck.</p><p>We produced Highland Harvest: a three-minute film that follows one day of plucking at 1,900 metres through to the sealed carton leaving the Colombo warehouse, plus a stills library that replaced the stock imagery in the export catalogue.</p>']),
                'challenge'    => $j(['en' => 'Buyers in Hamburg and Dubai were comparing quotes on price alone. The brief was to make provenance felt in under three minutes, with no presenter on camera and no claim the team could not evidence.']),
                'approach'     => $j(['en' => 'Five shooting days across the estates, the Hatton grading room and the Colombo packing floor. A two-person crew worked plucking hours from 6am for the light, with a gimbal-led A camera and a drone for the terrain, and we recorded room tone and machine sound on location so the film carries itself without voice-over. The grade holds the field greens cool and the factory warm, so both halves read as one journey.']),
                'results'      => $j(['en' => 'Highland Harvest opened every buyer meeting at Gulfood 2026, where the team signed three new distributor agreements. The 45-second vertical cut ran for six weeks and delivered 312,000 views at LKR 1.90 per view, and the estate stills now carry the export catalogue and the packaging refresh.']),
                'cover_image'  => '/media/placeholders/project-01.svg',
                'gallery'      => $g([
                    ['/media/placeholders/gear-01.svg', 'The A camera rigged for the plucking sequence at 1,900 metres.'],
                    ['/media/placeholders/gear-05.svg', 'Lighting the grading room before the factory pass.'],
                    ['/media/placeholders/service-photography-videography.svg', 'A frame from the estate stills library.'],
                ]),
                'featured'         => 1,
                'meta_description' => $j(['en' => 'How CreativeFX filmed Highland Harvest for Ceylon Tea Exports: five shooting days from estate to export carton, and the trade-show film that came out of it.']),
            ],
            [
                'slug'         => 'colombo-city-centre-festive-season',
                'category_id'  => $catId['advertising'] ?? null,
                'title'        => $j(['en' => 'Festive season campaign for Colombo City Centre']),
                'client'       => 'Colombo City Centre',
                'industry'     => 'Retail',
                'service'      => 'Social media advertising',
                'project_date' => '2025-11-21',
                'year'         => '2025',
                'excerpt'      => $j(['en' => 'Six weeks of paid social built on one studio shoot: 18 creatives, three audiences and a footfall promise the mall could measure at the door.']),
                'description'  => $j(['en' => '<p>Colombo City Centre wanted the December weekends back. Their festive spend had been split across radio and print, and nothing tied a rupee of it to a visitor walking through the doors.</p><p>We shot one studio day with the anchor tenants, built 18 creatives from it and ran them as three audience sets across Meta and TikTok, with the offer, the date and the floor number in the first two seconds of every cut.</p>']),
                'challenge'    => $j(['en' => 'Every mall in Colombo advertises the same fortnight with the same festive imagery. The campaign had to look unmistakably like Colombo City Centre and drive dated visits, not generic seasonal goodwill.']),
                'approach'     => $j(['en' => 'A single-day studio shoot produced product, talent and interior plates that we recombined into vertical, square and story formats. Creative was built in three tiers — a hero film, weekly offer cards and last-mile reminders inside a 12 km radius — and refreshed every fortnight so the six-week run never fatigued.']),
                'results'      => $j(['en' => 'The campaign reached 1.4 million people in the Western Province and drove 46,000 clicks at LKR 12 per click. Weekend footfall over the six weeks was 22% up on the same period a year earlier, tracked on the mall\'s own door counters.']),
                'cover_image'  => '/media/placeholders/project-02.svg',
                'gallery'      => $g([
                    ['/media/placeholders/service-social-media-advertising.svg', 'Creative variants built from the single studio day.'],
                    ['/media/placeholders/gear-03.svg', 'Studio lighting set for the tenant product plates.'],
                ]),
                'featured'         => 0,
                'meta_description' => $j(['en' => 'A six-week festive paid social campaign for Colombo City Centre: one studio shoot, 18 creatives and a 22% lift in weekend footfall.']),
            ],
            [
                'slug'         => 'kandy-heritage-hotels-suite-stories',
                'category_id'  => $catId['photography'] ?? null,
                'title'        => $j(['en' => 'Suite Stories — property photography for Kandy Heritage Hotels']),
                'client'       => 'Kandy Heritage Hotels',
                'industry'     => 'Hospitality',
                'service'      => 'Hotel & interiors photography',
                'project_date' => '2026-01-19',
                'year'         => '2026',
                'excerpt'      => $j(['en' => 'A four-night shoot across three properties: 240 delivered frames covering rooms, dining, spa and the lake terrace, licensed for booking channels and print.']),
                'description'  => $j(['en' => '<p>Kandy Heritage Hotels runs three colonial-era properties above the lake. Their booking-channel galleries were a decade old, shot on three different cameras, and the suites photographed smaller than they are.</p><p>We rebuilt the library from scratch — rooms, suites, dining, spa, grounds and the staff moments in between — to one lighting and grading standard that holds up on Booking.com, in the brochure and on a 6-metre trade-show wall.</p>']),
                'challenge'    => $j(['en' => 'Heritage rooms are dark, narrow and full of mixed light: tungsten lamps, daylight through deep verandas and a lake that blows out by nine in the morning. The properties also stayed open, so no shoot could take a room out of service for more than four hours.']),
                'approach'     => $j(['en' => 'A tilt-shift lens kept the verticals true in tight rooms, and each frame was built from a bracketed exposure blend so the veranda light and the lamp-lit interior sit in one image. We scheduled around housekeeping — rooms at dawn, public areas after checkout, the terrace at golden hour — and art-directed with the front-office team so every frame matches a room type guests can actually book.']),
                'results'      => $j(['en' => 'Direct booking-page conversion rose 18% in the quarter after the gallery was replaced, and the group cut its stock-image licensing entirely. The same set now runs across the OTA listings, the 2026 brochure and the tourism trade-fair stand.']),
                'cover_image'  => '/media/placeholders/project-03.svg',
                'gallery'      => $g([
                    ['/media/placeholders/gear-02.svg', 'Tilt-shift setup in a heritage suite, dawn call.'],
                    ['/media/placeholders/gear-06.svg', 'Balancing veranda daylight against tungsten room lamps.'],
                ]),
                'featured'         => 1,
                'meta_description' => $j(['en' => 'Hotel photography for Kandy Heritage Hotels: 240 frames across three heritage properties, and an 18% lift in direct booking conversion.']),
            ],
            [
                'slug'         => 'serendib-podcast-made-in-ceylon',
                'category_id'  => $catId['podcast'] ?? null,
                'title'        => $j(['en' => 'Made in Ceylon — a twelve-episode studio season']),
                'client'       => 'Serendib Podcast Network',
                'industry'     => 'Media & Publishing',
                'service'      => 'Podcast production',
                'project_date' => '2025-09-03',
                'year'         => '2025',
                'excerpt'      => $j(['en' => 'Twelve episodes recorded in our Colombo studio — three-camera video, four-mic audio, and a clip pipeline that turned each session into nine short-form cuts.']),
                'description'  => $j(['en' => '<p>Serendib Podcast Network commissioned a season about Sri Lankan manufacturers who export. Their first season had been recorded on laptop microphones in a boardroom, and the audio was costing them listeners in the first ninety seconds.</p><p>We took the season into the CreativeFX podcast studio: twelve two-hour sessions, three-camera video, individually tracked microphones, and a delivery pipeline that shipped the episode plus nine vertical clips within four working days.</p>']),
                'challenge'    => $j(['en' => 'Guests were factory owners with a two-hour window and no broadcast experience, and the network needed video, audio and social cuts from a single sitting — with no re-records once a guest left Colombo.']),
                'approach'     => $j(['en' => 'A four-microphone acoustically treated set with each guest on their own track, so a cough or a phone buzz never costs the whole take. Three cameras (host, guest, wide) rolled to timecode for frame-accurate edits. We ran a producer in the room to catch the two or three lines per episode that make good clips, then cut those first while the session was still fresh.']),
                'results'      => $j(['en' => 'The season averaged 11,400 downloads an episode against 3,200 for season one, and the clip pipeline added 68,000 followers across the network\'s YouTube and Instagram channels. Serendib booked the studio for a second season before episode nine aired.']),
                'cover_image'  => '/media/placeholders/project-04.svg',
                'gallery'      => $g([
                    ['/media/placeholders/service-podcast-studio.svg', 'The four-mic studio set, dressed for the season.'],
                    ['/media/placeholders/gear-07.svg', 'Individually tracked microphones at the desk.'],
                    ['/media/placeholders/gear-09.svg', 'Three-camera video rolling to timecode.'],
                ]),
                'featured'         => 1,
                'meta_description' => $j(['en' => 'Podcast production for Serendib Podcast Network: twelve episodes in the CreativeFX Colombo studio, with video, audio and nine social clips per session.']),
            ],
            [
                'slug'         => 'lanka-fintech-summit-live',
                'category_id'  => $catId['live-streaming'] ?? null,
                'title'        => $j(['en' => 'Three-stage live stream for the Lanka Fintech Summit']),
                'client'       => 'Lanka Fintech Summit',
                'industry'     => 'Financial Services',
                'service'      => 'Multi-camera live streaming',
                'project_date' => '2026-02-27',
                'year'         => '2026',
                'excerpt'      => $j(['en' => 'Two days, three stages, nine cameras and a bonded-connection stream to YouTube, LinkedIn and the summit app — 4,100 remote delegates, no dropped minutes.']),
                'description'  => $j(['en' => '<p>The Lanka Fintech Summit sells a hybrid ticket: regional delegates who cannot fly to Colombo watch the same programme live. That promise only holds if the stream never stutters during a keynote.</p><p>We ran all three stages — main hall, workshop room and demo floor — from a single control position, with switched multi-camera output, live lower-thirds from the running order and same-day session recordings in the delegate portal.</p>']),
                'challenge'    => $j(['en' => 'Three stages ran in parallel with a shared six-minute changeover, the venue uplink was a single shared line, and sponsors had contracted logo visibility on every remote stream.']),
                'approach'     => $j(['en' => 'Nine cameras fed three switchers at one control position, with bonded 4G and fibre failover so a venue outage never reached the audience. Graphics were driven from the published running order, so a late speaker swap was a two-keystroke change rather than a re-render, and a dedicated operator monitored the sponsor bug and the caption feed on every output.']),
                'results'      => $j(['en' => '4,100 remote delegates watched across the two days, with 71% average completion on keynote sessions and zero minutes of downtime across 19 hours of live output. Session recordings were in the delegate portal within four hours of each stage going dark.']),
                'cover_image'  => '/media/placeholders/project-05.svg',
                'gallery'      => $g([
                    ['/media/placeholders/service-live-streaming.svg', 'The single control position running all three stages.'],
                    ['/media/placeholders/gear-04.svg', 'Bonded uplink and fibre failover at the venue.'],
                ]),
                'featured'         => 1,
                'meta_description' => $j(['en' => 'Multi-camera live streaming for the Lanka Fintech Summit: three stages, nine cameras, 4,100 remote delegates and no dropped minutes.']),
            ],
            [
                'slug'         => 'araliya-bridal-couture-lookbook',
                'category_id'  => $catId['photography'] ?? null,
                'title'        => $j(['en' => 'Couture lookbook for Araliya Bridal']),
                'client'       => 'Araliya Bridal',
                'industry'     => 'Fashion & Bridal',
                'service'      => 'Fashion photography',
                'project_date' => '2025-12-07',
                'year'         => '2025',
                'excerpt'      => $j(['en' => 'Twenty-two looks photographed over two days — studio on white for the catalogue, Galle Fort at dawn for the campaign.']),
                'description'  => $j(['en' => '<p>Araliya Bridal releases one couture collection a year, and the lookbook has to serve two jobs at once: an accurate catalogue for the atelier and an aspirational campaign for social.</p><p>We split the shoot — a controlled studio day for fabric, fall and colour accuracy, then a dawn location day inside Galle Fort for the campaign frames — and delivered a single retouched set that reads as one collection.</p>']),
                'challenge'    => $j(['en' => 'Hand-worked beadwork and ivory silk are unforgiving: the catalogue frames had to show the true colour a bride will see in the fitting room, while the campaign frames needed atmosphere without losing that detail.']),
                'approach'     => $j(['en' => 'Studio work was shot tethered against a colour-checked reference so the atelier could sign off tone on the spot. The location day started at 5.30am for one hour of soft light on the ramparts, with a two-person styling team resetting each look between frames. Retouching kept beadwork texture intact and corrected only what the light did, never the garment.']),
                'results'      => $j(['en' => 'The collection sold out its made-to-order slots eight weeks after launch, and the campaign frames carried the brand\'s first paid social run — 890,000 impressions and 1,100 consultation enquiries in the bridal season.']),
                'cover_image'  => '/media/placeholders/project-06.svg',
                'gallery'      => $g([
                    ['/media/placeholders/gear-08.svg', 'Tethered studio capture with a colour reference in frame.'],
                    ['/media/placeholders/hero-services.svg', 'Dawn call on the Galle Fort ramparts.'],
                ]),
                'featured'         => 0,
                'meta_description' => $j(['en' => 'Fashion photography for Araliya Bridal: 22 couture looks shot across a tethered studio day and a dawn location day in Galle Fort.']),
            ],
            [
                'slug'         => 'nuwara-eliya-half-marathon',
                'category_id'  => $catId['events'] ?? null,
                'title'        => $j(['en' => 'Race-day coverage for the Nuwara Eliya Half Marathon']),
                'client'       => 'Nuwara Eliya Half Marathon',
                'industry'     => 'Sport & Community',
                'service'      => 'Event photography & videography',
                'project_date' => '2026-04-05',
                'year'         => '2026',
                'excerpt'      => $j(['en' => 'Six photographers and two motorbike video units covering 2,800 runners, with bib-tagged galleries live before the finish-line barriers came down.']),
                'description'  => $j(['en' => '<p>The Nuwara Eliya Half Marathon funds school sports facilities in the hill country, and the organisers wanted every finisher to leave with a photograph of themselves — not just the podium.</p><p>We covered the course from the 5.45am start to the last finisher, delivered a bib-searchable gallery the same afternoon, and cut a two-minute highlight film for the sponsors within 48 hours.</p>']),
                'challenge'    => $j(['en' => 'Hill-country weather turns twice before eight in the morning, the course climbs through 400 metres of elevation, and 2,800 runners had to be findable in the gallery by their own bib number without a manual tagging crew.']),
                'approach'     => $j(['en' => 'Six photographers were positioned at the start, two climbs, the turnaround and the finish, with two motorbike units running the lead and tail groups. Cards were ferried to a mobile edit station at the finish, where images were culled, graded to a single profile and pushed through bib-number recognition, so runners were tagged automatically as frames landed.']),
                'results'      => $j(['en' => 'Galleries went live at 2pm on race day with 11,600 images and 96% of finishers matched to a bib. The sponsor highlight film ran across the organisers\' channels for 340,000 views, and next year\'s title sponsorship was renewed on the strength of the coverage package.']),
                'cover_image'  => '/media/placeholders/project-07.svg',
                'gallery'      => $g([
                    ['/media/placeholders/gear-06.svg', 'Motorbike unit ready for the lead group.'],
                    ['/media/placeholders/gear-02.svg', 'Mobile edit station at the finish line.'],
                ]),
                'featured'         => 0,
                'meta_description' => $j(['en' => 'Event coverage for the Nuwara Eliya Half Marathon: 11,600 bib-tagged images live on race day and a sponsor highlight film in 48 hours.']),
            ],
            [
                'slug'         => 'sapphire-logistics-one-network',
                'category_id'  => $catId['corporate'] ?? null,
                'title'        => $j(['en' => 'One Network — a corporate film for Sapphire Logistics Lanka']),
                'client'       => 'Sapphire Logistics Lanka',
                'industry'     => 'Logistics & Supply Chain',
                'service'      => 'Corporate film production',
                'project_date' => '2025-08-15',
                'year'         => '2025',
                'excerpt'      => $j(['en' => 'A four-minute company film shot across the Port of Colombo, the Katunayake bond and the Peliyagoda fleet yard, with a 90-second recruitment cut.']),
                'description'  => $j(['en' => '<p>Sapphire Logistics Lanka moves freight for garment exporters, and every tender response opened with the same wall of numbers: warehouse square footage, fleet size, customs clearance times.</p><p>We built One Network — a four-minute film that follows a single consignment from the factory floor to the vessel, told by the people who touch it — plus a 90-second cut the HR team uses to recruit drivers and warehouse supervisors.</p>']),
                'challenge'    => $j(['en' => 'Port and bonded-warehouse access is tightly restricted, operations could not pause for a film crew, and the leadership wanted the company to sound confident without a word of corporate boilerplate.']),
                'approach'     => $j(['en' => 'Three shooting days planned around the operations calendar, with security clearances filed a month ahead and a compact two-person crew that could work inside live areas. Instead of a scripted voice-over we recorded seven staff interviews — a driver, a customs officer, a night-shift supervisor — and let the consignment provide the structure. Timelapses of the yard and port carry the transitions.']),
                'results'      => $j(['en' => 'The film now opens every tender presentation, and Sapphire credits it in two contract wins with European buyers who never visited the facilities. The recruitment cut halved the time-to-hire for warehouse supervisors, from nine weeks to four.']),
                'cover_image'  => '/media/placeholders/project-08.svg',
                'gallery'      => $g([
                    ['/media/placeholders/gear-01.svg', 'Compact crew configuration for live warehouse areas.'],
                    ['/media/placeholders/gear-05.svg', 'Interview lighting on the night shift.'],
                ]),
                'featured'         => 1,
                'meta_description' => $j(['en' => 'Corporate film production for Sapphire Logistics Lanka: one consignment from factory floor to vessel, told by the people who move it.']),
            ],
            [
                'slug'         => 'villa-serene-bentota-always-on',
                'category_id'  => $catId['digital-marketing'] ?? null,
                'title'        => $j(['en' => 'Always-on social growth for Villa Serene Bentota']),
                'client'       => 'Villa Serene Bentota',
                'industry'     => 'Hospitality',
                'service'      => 'Digital marketing retainer',
                'project_date' => '2026-05-11',
                'year'         => '2026',
                'excerpt'      => $j(['en' => 'A six-month retainer: monthly content days, a vertical-first posting calendar and direct-booking tracking that showed exactly which posts filled rooms.']),
                'description'  => $j(['en' => '<p>Villa Serene is a nine-room boutique villa on the Bentota river. Ninety per cent of its bookings came through OTAs, and the commission was eating the season.</p><p>We took on the social and search retainer with one target: shift the booking mix towards direct. That meant a monthly content day at the property, a calendar built around search demand, and tracking that ties an enquiry back to the post that caused it.</p>']),
                'challenge'    => $j(['en' => 'A nine-room property cannot outspend the OTAs. Growth had to come from content that ranks and shares rather than from a bigger media budget, and the owner needed to see the return in bookings, not impressions.']),
                'approach'     => $j(['en' => 'One shooting day a month produced four weeks of vertical video and stills — sunrise on the river, the kitchen, the neighbourhood — planned against the questions guests actually search. We rebuilt the booking page for speed and mobile, added enquiry tracking with UTM tagging end to end, and reported monthly on direct revenue rather than reach.']),
                'results'      => $j(['en' => 'Instagram followers grew from 4,200 to 19,800 over six months, and direct bookings rose from 10% to 34% of total room nights — worth about LKR 4.1 million in commission the villa no longer pays. Occupancy in the low season held at 62%, up from 38%.']),
                'cover_image'  => '/media/placeholders/project-09.svg',
                'gallery'      => $g([
                    ['/media/placeholders/service-digital-marketing.svg', 'The monthly content calendar, built from search demand.'],
                    ['/media/placeholders/gear-03.svg', 'Gimbal setup for the sunrise river sequence.'],
                ]),
                'featured'         => 1,
                'meta_description' => $j(['en' => 'A six-month digital marketing retainer for Villa Serene Bentota: monthly content days, vertical-first publishing and direct bookings up from 10% to 34%.']),
            ],
            [
                'slug'         => 'nexus-bank-annual-report-portraits',
                'category_id'  => $catId['corporate'] ?? null,
                'title'        => $j(['en' => 'Leadership portraits and report imagery for Nexus Bank Ceylon']),
                'client'       => 'Nexus Bank Ceylon',
                'industry'     => 'Banking & Finance',
                'service'      => 'Corporate photography',
                'project_date' => '2025-10-10',
                'year'         => '2025',
                'excerpt'      => $j(['en' => 'Forty-one executives photographed in two days on a travelling studio set, plus branch and operations imagery for the annual report and the investor deck.']),
                'description'  => $j(['en' => '<p>Nexus Bank Ceylon publishes an annual report that goes to regulators, investors and every branch. Its leadership portraits had been collected over six years from five photographers, and no two matched.</p><p>We built one portrait standard and shot the entire board and senior management against it in two days, then covered branch, call-centre and operations scenes for the report\'s narrative sections.</p>']),
                'challenge'    => $j(['en' => 'Forty-one executives with ten-minute windows each, spread across the head office and two branches, and a report design that placed portraits side by side where any difference in light or crop would show.']),
                'approach'     => $j(['en' => 'A travelling studio set — the same two-light setup, backdrop and lens on every sitting — moved between floors so executives never left their building. Each sitter got ten minutes with a fixed shot list: formal, three-quarter and an environmental frame. Selections were made the same week against the report layout, so the design team never waited on imagery.']),
                'results'      => $j(['en' => 'The 2025 annual report shipped on schedule with a single consistent portrait set, and the bank now re-shoots only new appointments against the same standard. The environmental frames replaced purchased stock across the investor deck and the careers site.']),
                'cover_image'  => '/media/placeholders/project-10.svg',
                'gallery'      => $g([
                    ['/media/placeholders/gear-07.svg', 'The travelling two-light portrait set.'],
                    ['/media/placeholders/gear-04.svg', 'Same lens, same backdrop, floor after floor.'],
                ]),
                'featured'         => 0,
                'meta_description' => $j(['en' => 'Corporate photography for Nexus Bank Ceylon: 41 leadership portraits to one standard in two days, plus branch and operations imagery for the annual report.']),
            ],
            [
                'slug'         => 'galle-fort-music-nights',
                'category_id'  => $catId['videography'] ?? null,
                'title'        => $j(['en' => 'Galle Fort Music Nights — a two-night concert film']),
                'client'       => 'Galle Fort Music Nights',
                'industry'     => 'Arts & Culture',
                'service'      => 'Concert film & aftermovie',
                'project_date' => '2026-01-31',
                'year'         => '2026',
                'excerpt'      => $j(['en' => 'Five cameras across two nights on the ramparts, with a multitrack audio capture that let the festival release live performance videos, not phone footage.']),
                'description'  => $j(['en' => '<p>Galle Fort Music Nights programmes two evenings of live music on the ramparts each January. The festival had grown past its documentation: sponsors wanted broadcast-quality assets and artists wanted performance videos worth releasing.</p><p>We filmed five performances across the two nights with a five-camera crew and a multitrack split from the front-of-house desk, delivering per-artist performance films and a two-minute aftermovie for the next campaign.</p>']),
                'challenge'    => $j(['en' => 'Stage lighting was designed for the audience, not for cameras, the ramparts allow no rigging into the stonework, and the sea wind at that hour puts noise into every open microphone.']),
                'approach'     => $j(['en' => 'Two operated long lenses from the rear towers, two roaming handhelds on the pit and a locked wide, all timecode-synced to a multitrack split from front-of-house so the mix was recorded rather than reconstructed. Free-standing ballast positions replaced any rig into the fort walls, and we cut the aftermovie against the festival\'s own opening track so the release landed while the weekend was still trending.']),
                'results'      => $j(['en' => 'Five artist performance films went out in the fortnight after the festival, drawing 210,000 combined views, and the aftermovie became the opening asset of the 2027 sponsor pack. Two of the performing artists licensed their films for their own album campaigns.']),
                'cover_image'  => '/media/placeholders/project-11.svg',
                'gallery'      => $g([
                    ['/media/placeholders/gear-09.svg', 'Long-lens position on the rear tower.'],
                    ['/media/placeholders/gear-08.svg', 'Multitrack split taken from front-of-house.'],
                ]),
                'featured'         => 0,
                'meta_description' => $j(['en' => 'A five-camera concert film for Galle Fort Music Nights: per-artist performance videos and an aftermovie cut from two nights on the ramparts.']),
            ],
            [
                'slug'         => 'sunrise-ayurveda-vertical-launch',
                'category_id'  => $catId['advertising'] ?? null,
                'title'        => $j(['en' => 'Vertical-first launch campaign for Sunrise Ayurveda']),
                'client'       => 'Sunrise Ayurveda',
                'industry'     => 'Health & Wellness',
                'service'      => 'Social media advertising',
                'project_date' => '2026-07-02',
                'year'         => '2026',
                'excerpt'      => $j(['en' => 'Thirty vertical films for a new hair-oil range, tested in three creative rounds and scaled on the four that beat a LKR 400 cost per order.']),
                'description'  => $j(['en' => '<p>Sunrise Ayurveda launched a hair-oil range with a direct-to-consumer store and no retail shelf to lean on. Everything depended on paid social converting cold traffic on the first view.</p><p>We shot thirty vertical films in two studio days — demonstration, ingredient, testimonial and comparison formats — and ran them as a structured test, retiring what failed weekly and scaling what cleared the target cost per order.</p>']),
                'challenge'    => $j(['en' => 'Ayurvedic claims are regulated, the category is crowded with unbranded sellers, and the founder needed a repeatable cost per order before committing stock to a second production run.']),
                'approach'     => $j(['en' => 'Creative was built as a test matrix: four hooks against four formats, each shot to work with sound off and captioned in Sinhala and English. Claims were kept to what the product registration supports. Every round ran for seven days on equal budget, and the losers were cut rather than optimised, so spend concentrated behind the four films that repeatedly converted.']),
                'results'      => $j(['en' => 'The winning film held a LKR 318 cost per order across an eight-week scale-up, against a LKR 400 target, on 3.2 million impressions. The range sold through its first production run in five weeks and the store\'s repeat purchase rate reached 24% by month three.']),
                'cover_image'  => '/media/placeholders/project-12.svg',
                'gallery'      => $g([
                    ['/media/placeholders/service-social-media-advertising.svg', 'The four-hook, four-format test matrix.'],
                    ['/media/placeholders/gear-05.svg', 'Tabletop rig for the product demonstration films.'],
                ]),
                'featured'         => 0,
                'meta_description' => $j(['en' => 'A vertical-first paid social launch for Sunrise Ayurveda: 30 films, a structured creative test and a LKR 318 cost per order at scale.']),
            ],
        ];

        $table = $this->db->table('portfolio_projects');
        foreach ($projects as $i => $project) {
            $exists = $table->select('id')->where('slug', $project['slug'])->get()->getRowArray();
            $table->resetQuery();
            if ($exists === null) {
                $this->db->table('portfolio_projects')->insert($project + [
                    'sort_order' => $i + 1,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            // Existing rows are left untouched — editors own them after first seed.
        }
    }
}
