<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The hotel's Instagram, X and TikTok accounts.
 *
 * The seeder inserts and never updates, which is right — it must not overwrite
 * an address an editor has corrected in the console. But `social.instagram` was
 * seeded blank when this codebase was stood up as the hotel's site, so the row
 * already exists and a new default in the seeder would never reach it. The two
 * genuinely new keys would arrive on their own; this makes all three land the
 * same way, and only where nothing has been set.
 */
class AddHotelSocialAccounts extends Migration
{
    private const ACCOUNTS = [
        'instagram' => 'https://www.instagram.com/giants_forest/',
        'x'         => 'https://x.com/kukuleganga',
        'tiktok'    => 'https://www.tiktok.com/@giantsforest_kukuleganga',
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (self::ACCOUNTS as $key => $url) {
            $row = $this->db->table('settings')
                ->where('group', 'social')->where('key', $key)
                ->get()->getRowArray();

            if ($row === null) {
                $this->db->table('settings')->insert([
                    'group'      => 'social',
                    'key'        => $key,
                    'value'      => $url,
                    'type'       => 'string',
                    'is_public'  => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                continue;
            }

            // Anything already set is somebody's decision; leave it alone.
            if (trim((string) $row['value']) === '') {
                $this->db->table('settings')
                    ->where('id', $row['id'])
                    ->update(['value' => $url, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Blanking these would hide accounts the hotel actually has, and there
        // is no record here of which of them this migration set. The two keys
        // it may have created are dropped; the pre-existing one is left.
        $this->db->table('settings')
            ->where('group', 'social')
            ->whereIn('key', ['x', 'tiktok'])
            ->delete();
    }
}
