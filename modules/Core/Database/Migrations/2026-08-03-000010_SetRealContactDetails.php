<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The contact email and phone were still the placeholders the site launched
 * with — hello@norlanka.com and +94 11 000 0000 — which show on the contact
 * page, in the footer and in the showroom enquiry link.
 *
 * SettingSeeder inserts with ignore(), so it never rewrites a row that already
 * exists; correcting the seeder alone would only change fresh installs. This
 * updates the live rows, and is written to touch only the two rows that still
 * hold the placeholder, so a value already corrected by hand in
 * Admin -> Settings is left alone.
 */
class SetRealContactDetails extends Migration
{
    private const CORRECTIONS = [
        ['email', 'hello@norlanka.com', 'info@norlankamfg.com'],
        ['phone', '+94 11 000 0000', '+94 11 300 3010'],
    ];

    public function up(): void
    {
        foreach (self::CORRECTIONS as [$key, $old, $new]) {
            $this->db->table('settings')
                ->where('group', 'contact')->where('key', $key)->where('value', $old)
                ->update(['value' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down(): void
    {
        foreach (self::CORRECTIONS as [$key, $old, $new]) {
            $this->db->table('settings')
                ->where('group', 'contact')->where('key', $key)->where('value', $new)
                ->update(['value' => $old, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
