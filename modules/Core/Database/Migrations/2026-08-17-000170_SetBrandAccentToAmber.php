<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Moves the stored brand colour from Norlanka's red to the CreativeFX amber.
 *
 * The accent that actually paints the site is a CSS token, not this row — but
 * `brand.color_primary` is what the admin shows as "the brand colour", so
 * leaving it on #CF2030 would tell an editor the site is red when every button
 * on it is amber.
 *
 * Only rewritten where it still holds the Norlanka value, so a colour someone
 * has already set by hand survives.
 */
class SetBrandAccentToAmber extends Migration
{
    public function up(): void
    {
        $this->db->table('settings')
            ->where('group', 'brand')->where('key', 'color_primary')->where('value', '#CF2030')
            ->update(['value' => '#FFC107', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down(): void
    {
        $this->db->table('settings')
            ->where('group', 'brand')->where('key', 'color_primary')->where('value', '#FFC107')
            ->update(['value' => '#CF2030', 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
