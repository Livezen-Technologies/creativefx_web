<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Unpublishes the apparel-business pages left over from Norlanka.
 *
 * Their routes are gone with the rebuild, but the CMS catch-all
 * (/{locale}/{slug}) would still serve any published page by slug — so
 * /en/manufacturing would have kept rendering Norlanka content on the
 * CreativeFX site. Setting them to draft closes that door.
 *
 * The rows, sections and blocks are all left intact: this is reversible, and
 * an admin can republish any of them from Admin -> Pages.
 *
 * `our-story` and `contact` are deliberately NOT in this list — the CreativeFX
 * content seeder rewrites those two in place.
 */
class RetireNorlankaPages extends Migration
{
    private const SLUGS = ['our-expertise', 'manufacturing', 'impact', 'careers', 'showroom'];

    public function up(): void
    {
        $this->db->table('pages')
            ->whereIn('slug', self::SLUGS)
            ->where('status', 'published')
            ->update(['status' => 'draft', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down(): void
    {
        $this->db->table('pages')
            ->whereIn('slug', self::SLUGS)
            ->where('status', 'draft')
            ->update(['status' => 'published', 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
