<?php

namespace Modules\Cms\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The hotel's rooms and the places worth leaving them for.
 *
 * These were page blocks — two rooms inside the home page's Rooms section, six
 * places inside the Kalawana page's Things to Do section. The copy and the
 * photographs are carried over exactly, so moving them into their own tables
 * changes nothing a visitor sees; it changes who can add a third one.
 *
 * Insert-only, and only while the table is empty. Once a hotel has edited its
 * own rooms the seeder has nothing useful to say about them, and a release that
 * rewrote them would undo the work exactly as the page seeders used to.
 */
class RoomLocationSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $j   = static fn (array $m): string => json_encode($m, JSON_UNESCAPED_UNICODE);

        if ($this->db->table('rooms')->countAllResults() === 0) {
            $rooms = [
                [
                    'slug'       => 'deluxe-room',
                    'name'       => ['en' => 'Deluxe Room'],
                    'summary'    => ['en' => 'Superior double rooms are made with open living area comforts you and morning sight of Sabaragamuwa hills at balcony in the room feel you better.'],
                    'image'      => '/media/giantforests/Deluxe-Room-Giants-forest.jpg',
                    'features'   => ['Balcony with hill view', 'Open living area', 'Air conditioning', 'Private bathroom'],
                    'max_guests' => 2,
                    'bed'        => 'Double',
                ],
                [
                    'slug'       => 'standard-room',
                    'name'       => ['en' => 'Standard Room'],
                    'summary'    => ['en' => 'The rooms consist of wooden ceiling, make cool atmosphere. All the facilities are compiled with these rooms.'],
                    'image'      => '/media/giantforests/Single-Room-Giants-forest.jpg',
                    'features'   => ['Wooden ceiling', 'Air conditioning', 'Private bathroom'],
                    'max_guests' => 2,
                    'bed'        => 'Double',
                ],
            ];

            $order = 0;
            foreach ($rooms as $room) {
                $this->db->table('rooms')->insert([
                    'slug'       => $room['slug'],
                    'name'       => $j($room['name']),
                    'summary'    => $j($room['summary']),
                    'description' => $j($room['summary']),
                    'image'      => $room['image'],
                    'features'   => json_encode($room['features'], JSON_UNESCAPED_UNICODE),
                    'max_guests' => $room['max_guests'],
                    'bed'        => $room['bed'],
                    'sort_order' => $order++,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($this->db->table('locations')->countAllResults() === 0) {
            $places = [
                ['sinharaja-rain-forest', 'Explore Sinharaja Rain Forest', 'Join a guided walk through dense rainforest, home to endemic birds, butterflies, reptiles, and plant life.', '/media/giantforests/Hiking-Jungle-Tour-1-Giants-Forests-Hotel.jpg', 25.0, '45 minutes by road'],
                ['waterfall-adventures', 'Waterfall adventures', 'Discover scenic waterfalls and natural pools around the Kalawana and Sinharaja region.', '/media/giantforests/Bopath-Falls-Ratnapura.jpg', null, null],
                ['birdwatching-and-wildlife', 'Birdwatching and wildlife', 'Look out for the Sri Lanka blue magpie, giant squirrel, colourful butterflies, and rare rainforest species.', '/media/giantforests/Things-to-do-Giants-Forests-Hotel-1.jpg', null, null],
                ['village-and-tea-country', 'Village and tea-country experiences', 'Enjoy rural landscapes, tea estates, local food, and the slower rhythm of village life.', '/media/giantforests/Cycle-tour-1.jpg', null, null],
                ['nature-photography', 'Nature photography', 'Capture misty forest views, rivers, tropical greenery, and waterfalls.', '/media/giantforests/Sinharaja-Tracking-3.jpg', null, null],
                ['boat-rides', 'Boat rides on the reservoir', 'Take to the water on the Kukuleganga reservoir the hotel looks over, with the forest rising on every side.', '/media/giantforests/Boat-Tour-2-Giants-Forests-Hotel.jpg', 0.0, 'At the hotel'],
            ];

            $order = 0;
            foreach ($places as [$slug, $name, $summary, $image, $km, $time]) {
                $this->db->table('locations')->insert([
                    'slug'        => $slug,
                    'name'        => $j(['en' => $name]),
                    'summary'     => $j(['en' => $summary]),
                    'description' => $j(['en' => $summary]),
                    'image'       => $image,
                    'image_alt'   => $j(['en' => $name]),
                    'distance_km' => $km,
                    'travel_time' => $time,
                    'sort_order'  => $order++,
                    'status'      => 'published',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }
}
