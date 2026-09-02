<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The shop renders empty cards on the live site: the card only emits an <img>
 * when hero_image is set, and on that database it is null for every product.
 *
 * The catalogue was seeded during the rebrand, when no photography had been
 * imported yet and ProductSeeder's own docblock said so — it wrote null. The
 * photography arrived a fortnight later and the seeder was updated to point at
 * it, but ProductSeeder inserts only rows it cannot find by slug and never
 * updates one, so every existing product kept its null. A fresh install has
 * looked correct the whole time, which is why this was invisible locally.
 *
 * Fills only what is empty, so a picture chosen in the admin is never replaced.
 */
class BackfillProductPhotography extends Migration
{
    private const CUP_SHOT = '/media/magiccorn/Magic-Corn-with-corn.png';

    /** slug => [hero, gallery] — mirrors what ProductSeeder now writes. */
    private const PHOTOGRAPHY = [
        'butter-corn-cup'            => [self::CUP_SHOT, [self::CUP_SHOT]],
        'garlic-corn-cup'            => [self::CUP_SHOT, [self::CUP_SHOT]],
        'cheese-corn-cup'            => [self::CUP_SHOT, [self::CUP_SHOT]],
        'mayo-corn-cup'              => [self::CUP_SHOT, [self::CUP_SHOT]],
        'minced-chicken-corn-cup'    => [self::CUP_SHOT, [self::CUP_SHOT]],
        'lime-oyster-corn-cup'       => [self::CUP_SHOT, [self::CUP_SHOT]],
        '1kg-frozen-sweet-corn-pack' => [
            '/media/magiccorn/frozan3.png',
            ['/media/magiccorn/frozan3.png', '/media/magiccorn/frozan.png', '/media/magiccorn/4.jpg'],
        ],
        'magic-corn-diy-home-pack'   => [
            '/media/magiccorn/DIY-pack.png',
            ['/media/magiccorn/DIY-pack.png', '/media/magiccorn/DIY4.png'],
        ],
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('products')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        foreach (self::PHOTOGRAPHY as $slug => [$hero, $gallery]) {
            $row = $this->db->table('products')
                ->select('id, hero_image, gallery')
                ->where('slug', $slug)
                ->get()
                ->getRowArray();

            if ($row === null) {
                continue;   // not seeded here — ProductSeeder will insert it with photography
            }

            $update = [];

            if (trim((string) $row['hero_image']) === '') {
                $update['hero_image'] = $hero;
            }

            $existing = json_decode((string) $row['gallery'], true);
            if (! is_array($existing) || $existing === []) {
                $update['gallery'] = json_encode($gallery, JSON_UNESCAPED_SLASHES);
            }

            if ($update !== []) {
                $update['updated_at'] = $now;
                $this->db->table('products')->where('id', $row['id'])->update($update);
            }
        }
    }

    public function down(): void
    {
        // Clearing the images again would only restore the broken state.
    }
}
