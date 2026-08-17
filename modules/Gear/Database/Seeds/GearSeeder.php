<?php

namespace Modules\Gear\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The rental catalogue CreativeFX opens with: nine kit families and the
 * fourteen items that go out most often, with Colombo day and week rates in
 * LKR.
 *
 * Upserts by slug — never updates, never deletes — so rates, availability and
 * wording changed in Admin survive a redeploy. Rates are the ones to expect an
 * editor to change first; they are seeded as a starting price list, not as a
 * contract.
 *
 * Artwork comes from the placeholder set in public/media/placeholders (see
 * scripts/gen-placeholders.mjs), matched to the kit rather than cycled blindly:
 * gear-01 camera, gear-02 lens, gear-03 light, gear-04 mic, gear-05 video,
 * gear-06 drone, gear-07 broadcast, gear-08 film, gear-09 grid.
 */
class GearSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->seedCategories($now);
        $this->seedItems($now);
    }

    private function seedCategories(string $now): void
    {
        $j = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);

        $categories = [
            ['cameras',   ['en' => 'Cameras']],
            ['lenses',    ['en' => 'Lenses']],
            ['lighting',  ['en' => 'Lighting']],
            ['audio',     ['en' => 'Audio']],
            ['tripods',   ['en' => 'Tripods']],
            ['gimbals',   ['en' => 'Gimbals']],
            ['drones',    ['en' => 'Drones']],
            ['streaming', ['en' => 'Streaming']],
            ['studio',    ['en' => 'Studio']],
        ];

        $table = $this->db->table('gear_categories');

        foreach ($categories as $i => [$slug, $name]) {
            $exists = $table->select('id')->where('slug', $slug)->get()->getRowArray();
            $table->resetQuery();

            if ($exists !== null) {
                continue; // Editors own the row after the first seed.
            }

            $this->db->table('gear_categories')->insert([
                'slug'       => $slug,
                'name'       => $j($name),
                'sort_order' => $i + 1,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedItems(string $now): void
    {
        $j = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);

        // Specs are authored as label => value pairs and stored as the list of
        // {label: locale-map, value: string} the grid reads.
        $specs = static function (array $pairs) use ($j): string {
            $out = [];
            foreach ($pairs as $label => $value) {
                $out[] = ['label' => ['en' => $label], 'value' => $value];
            }

            return $j($out);
        };

        // slug => id map for the category references below.
        $catId = [];
        foreach ($this->db->table('gear_categories')->select('id, slug')->get()->getResultArray() as $row) {
            $catId[$row['slug']] = (int) $row['id'];
        }

        $items = [
            [
                'slug'     => 'sony-fx3-cinema-camera',
                'category' => 'cameras',
                'name'     => ['en' => 'Sony FX3 Cinema Camera'],
                'summary'  => ['en' => 'Full-frame cinema camera for documentary and commercial work, with in-body stabilisation and active cooling that lets it roll all day.'],
                'specs'    => [
                    'Sensor'    => 'Full-frame 10.2MP Exmor R',
                    'Recording' => '4K 120p 10-bit 4:2:2 S-Cinetone',
                    'Mount'     => 'Sony E',
                    'Included'  => '2 batteries, charger, 160GB CFexpress Type A',
                ],
                'image'       => '/media/placeholders/gear-01.svg',
                'rate_daily'  => 18000,
                'rate_weekly' => 72000,
                'featured'    => 1,
            ],
            [
                'slug'     => 'canon-eos-r5',
                'category' => 'cameras',
                'name'     => ['en' => 'Canon EOS R5'],
                'summary'  => ['en' => 'A 45MP hybrid body that shoots stills and 8K video on the same card — the default choice for product, editorial and event days.'],
                'specs'    => [
                    'Sensor'    => 'Full-frame 45MP CMOS',
                    'Recording' => '8K 30p / 4K 120p 10-bit',
                    'Mount'     => 'Canon RF',
                    'Included'  => '2 LP-E6NH batteries, charger, 128GB CFexpress B',
                ],
                'image'       => '/media/placeholders/gear-01.svg',
                'rate_daily'  => 12500,
                'rate_weekly' => 50000,
                'featured'    => 1,
            ],
            [
                'slug'     => 'blackmagic-pocket-6k-pro',
                'category' => 'cameras',
                'name'     => ['en' => 'Blackmagic Pocket Cinema Camera 6K Pro'],
                'summary'  => ['en' => 'Super 35 cinema camera with built-in ND filters and BRAW, for narrative and music-video work that is graded in post.'],
                'specs'    => [
                    'Sensor'    => 'Super 35 6K HDR',
                    'Recording' => '6K BRAW / ProRes',
                    'Mount'     => 'Canon EF',
                    'Included'  => 'Built-in 2/4/6-stop ND, 3 NP-F570 batteries, 1TB SSD',
                ],
                'image'       => '/media/placeholders/gear-05.svg',
                'rate_daily'  => 14000,
                'rate_weekly' => 56000,
            ],
            [
                'slug'     => 'canon-rf-24-70mm-f28',
                'category' => 'lenses',
                'name'     => ['en' => 'Canon RF 24-70mm f/2.8L IS USM'],
                'summary'  => ['en' => 'The workhorse zoom for the EOS R bodies: one lens that covers a wide interior and a head-and-shoulders portrait without a change.'],
                'specs'    => [
                    'Focal length' => '24-70mm',
                    'Aperture'     => 'f/2.8 constant',
                    'Mount'        => 'Canon RF',
                    'Filter'       => '82mm',
                ],
                'image'       => '/media/placeholders/gear-02.svg',
                'rate_daily'  => 4500,
                'rate_weekly' => 18000,
            ],
            [
                'slug'     => 'sony-fe-70-200mm-gm-ii',
                'category' => 'lenses',
                'name'     => ['en' => 'Sony FE 70-200mm f/2.8 GM II'],
                'summary'  => ['en' => 'Fast telephoto for stage, sport and interview work, where the camera cannot move any closer to the subject.'],
                'specs'    => [
                    'Focal length'   => '70-200mm',
                    'Aperture'       => 'f/2.8 constant',
                    'Mount'          => 'Sony E',
                    'Stabilisation'  => 'Optical SteadyShot, 2 modes',
                ],
                'image'       => '/media/placeholders/gear-02.svg',
                'rate_daily'  => 6000,
                'rate_weekly' => 24000,
            ],
            [
                'slug'     => 'aputure-ls-600d-pro',
                'category' => 'lighting',
                'name'     => ['en' => 'Aputure LS 600d Pro'],
                'summary'  => ['en' => 'A 600W daylight LED that holds its own against sunlight through a window, on a Bowens mount for domes and softboxes.'],
                'specs'    => [
                    'Output'   => '600W daylight',
                    'Colour'   => '5600K, CRI 96+',
                    'Control'  => 'Sidus Link app, DMX',
                    'Included' => 'Hyper reflector, 120cm lantern, heavy-duty stand',
                ],
                'image'       => '/media/placeholders/gear-03.svg',
                'rate_daily'  => 9000,
                'rate_weekly' => 36000,
                'featured'    => 1,
            ],
            [
                'slug'     => 'godox-three-point-led-kit',
                'category' => 'lighting',
                'name'     => ['en' => 'Godox Three-Point LED Kit'],
                'summary'  => ['en' => 'Key, fill and hair light with stands and modifiers — the standard interview and podcast set-up, packed into two cases.'],
                'specs'    => [
                    'Fixtures'  => '3 x SL60II bi-colour',
                    'Colour'    => '2800-6500K',
                    'Modifiers' => 'Softbox, umbrella, barn doors',
                    'Included'  => '3 stands, 3 sandbags',
                ],
                'image'       => '/media/placeholders/gear-03.svg',
                'rate_daily'  => 5500,
                'rate_weekly' => 22000,
            ],
            [
                'slug'     => 'rode-wireless-go-ii',
                'category' => 'audio',
                'name'     => ['en' => 'Rode Wireless GO II'],
                'summary'  => ['en' => 'Two-channel wireless lavalier system that records on board as well, so an interview is never lost to a dropout.'],
                'specs'    => [
                    'Channels'  => '2 transmitters, 1 receiver',
                    'Recording' => 'On-board backup, 40 hours',
                    'Range'     => '200 m line of sight',
                    'Included'  => '2 lavaliers, windshields, charging case',
                ],
                'image'       => '/media/placeholders/gear-04.svg',
                'rate_daily'  => 3000,
                'rate_weekly' => 12000,
            ],
            [
                'slug'     => 'sennheiser-mke-600-zoom-h6',
                'category' => 'audio',
                'name'     => ['en' => 'Sennheiser MKE 600 and Zoom H6'],
                'summary'  => ['en' => 'Shotgun microphone and six-track field recorder for boom work, panel discussions and location dialogue.'],
                'specs'    => [
                    'Microphone' => 'MKE 600 supercardioid shotgun',
                    'Recorder'   => 'Zoom H6, 6 tracks',
                    'Power'      => 'Phantom or AA',
                    'Included'   => 'Boom pole, blimp, XLR cables',
                ],
                'image'       => '/media/placeholders/gear-04.svg',
                'rate_daily'  => 3500,
                'rate_weekly' => 14000,
            ],
            [
                'slug'     => 'manfrotto-546b-504x-tripod',
                'category' => 'tripods',
                'name'     => ['en' => 'Manfrotto 546B Tripod with 504X Head'],
                'summary'  => ['en' => 'Twin-leg video tripod with a fluid head rated for a fully rigged cinema camera, and a spreader for uneven ground.'],
                'specs'    => [
                    'Head'     => '504X fluid, 75mm ball',
                    'Payload'  => 'Up to 12 kg',
                    'Height'   => '160 cm maximum',
                    'Included' => 'Mid-level spreader, carry bag',
                ],
                'image'       => '/media/placeholders/gear-09.svg',
                'rate_daily'  => 2500,
                'rate_weekly' => 10000,
            ],
            [
                'slug'     => 'dji-rs-3-pro',
                'category' => 'gimbals',
                'name'     => ['en' => 'DJI RS 3 Pro Gimbal'],
                'summary'  => ['en' => 'Three-axis gimbal that carries a full cinema rig, with LiDAR focusing so a single operator can pull focus while moving.'],
                'specs'    => [
                    'Payload'  => 'Up to 4.5 kg',
                    'Battery'  => '12 hours',
                    'Focus'    => 'LiDAR range finder and focus motor',
                    'Included' => 'Carbon arms, briefcase handle, hard case',
                ],
                'image'       => '/media/placeholders/gear-08.svg',
                'rate_daily'  => 6500,
                'rate_weekly' => 26000,
                'featured'    => 1,
            ],
            [
                'slug'     => 'dji-mavic-3-pro',
                'category' => 'drones',
                'name'     => ['en' => 'DJI Mavic 3 Pro'],
                'summary'  => ['en' => 'Triple-camera drone for aerial coverage of venues, property and landscape. Goes out with an operator, so the flight permissions stay with us.'],
                'specs'    => [
                    'Cameras'     => '4/3 CMOS Hasselblad plus 2 tele',
                    'Recording'   => '5.1K 50p / 4K 120p',
                    'Flight time' => '43 minutes',
                    'Included'    => '3 batteries, ND set, RC Pro controller, operator',
                ],
                'image'        => '/media/placeholders/gear-06.svg',
                'rate_daily'   => 15000,
                'rate_weekly'  => 60000,
                'availability' => 'booked',
                'featured'     => 1,
            ],
            [
                'slug'     => 'atem-mini-extreme-streaming-kit',
                'category' => 'streaming',
                'name'     => ['en' => 'Blackmagic ATEM Mini Extreme Kit'],
                'summary'  => ['en' => 'Eight-input switcher, multiview monitor and cabling for a multi-camera stream to YouTube, Facebook or LinkedIn.'],
                'specs'    => [
                    'Inputs'   => '8 x HDMI, 2 x mic',
                    'Output'   => '1080p60 stream with ISO recording',
                    'Monitor'  => '17-inch multiview',
                    'Included' => '4 x 10m HDMI, network switch, flight case',
                ],
                'image'       => '/media/placeholders/gear-07.svg',
                'rate_daily'  => 8500,
                'rate_weekly' => 34000,
            ],
            [
                'slug'     => 'studio-backdrop-system',
                'category' => 'studio',
                'name'     => ['en' => 'Studio Backdrop and Green Screen System'],
                'summary'  => ['en' => 'Three-metre backdrop system with chroma green, white and black rolls, for portrait days and keyed video.'],
                'specs'    => [
                    'Width'    => '3 m rolls',
                    'Rolls'    => 'Chroma green, white, black',
                    'Support'  => 'Motorised, wall or stand mounted',
                    'Included' => 'Clamps, floor tape, 2 kicker lights',
                ],
                'image'       => '/media/placeholders/gear-09.svg',
                'rate_daily'  => 4000,
                'rate_weekly' => 16000,
            ],
        ];

        $table = $this->db->table('gear_items');

        foreach ($items as $i => $item) {
            $exists = $table->select('id')->where('slug', $item['slug'])->get()->getRowArray();
            $table->resetQuery();

            if ($exists !== null) {
                continue; // Editors own the row after the first seed.
            }

            $this->db->table('gear_items')->insert([
                'category_id'  => $catId[$item['category']] ?? null,
                'slug'         => $item['slug'],
                'name'         => $j($item['name']),
                'summary'      => $j($item['summary']),
                'specs'        => $specs($item['specs']),
                'image'        => $item['image'],
                'rate_daily'   => $item['rate_daily'],
                'rate_weekly'  => $item['rate_weekly'],
                'currency'     => 'LKR',
                'availability' => $item['availability'] ?? 'available',
                'featured'     => $item['featured'] ?? 0,
                'sort_order'   => $i + 1,
                'status'       => 'published',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
