<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The 1kg frozen pack was standing in with a field photograph of unhusked
 * corn, because no pack shot had been imported when the catalogue was seeded.
 * The live shop does publish one, and it is now in the repository.
 *
 * ProductSeeder only inserts rows it cannot find by slug, so it will never
 * rewrite a product that already exists — the correction has to happen here.
 * It is applied only where the row still holds the stand-in, so a picture
 * chosen in the admin afterwards is left alone.
 */
class UseFrozenPackPhotography extends Migration
{
    private const SLUG      = '1kg-frozen-sweet-corn-pack';
    private const STAND_IN  = '/media/magiccorn/4.jpg';
    private const PACK_SHOT = '/media/magiccorn/frozan3.png';

    public function up(): void
    {
        $this->db->table('products')
            ->where('slug', self::SLUG)
            ->where('hero_image', self::STAND_IN)
            ->update([
                'hero_image' => self::PACK_SHOT,
                'gallery'    => json_encode([
                    self::PACK_SHOT,
                    '/media/magiccorn/frozan.png',
                    self::STAND_IN,
                ], JSON_UNESCAPED_SLASHES),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down(): void
    {
        $this->db->table('products')
            ->where('slug', self::SLUG)
            ->where('hero_image', self::PACK_SHOT)
            ->update([
                'hero_image' => self::STAND_IN,
                'gallery'    => json_encode([
                    self::STAND_IN,
                    '/media/magiccorn/6.jpg',
                ], JSON_UNESCAPED_SLASHES),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
