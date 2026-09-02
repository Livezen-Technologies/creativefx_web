<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Point the Facebook link at the hotel's actual page.
 *
 * The seeded URL was facebook.com/giantsforest — a reasonable guess from the
 * hotel's name, and wrong. The page is facebook.com/giantsforestkukuleganga.
 * A link in the header and the footer of every page had been going somewhere
 * that is not this hotel.
 *
 * Correcting the seeder is not enough on a live site: SettingSeeder inserts
 * with `ignore`, so a row that already exists is never revisited. Conditional
 * on the wrong value still being there, so a hand-edit in the admin survives.
 */
class CorrectFacebookPageUrl extends Migration
{
    private const WRONG = 'https://www.facebook.com/giantsforest/';
    private const RIGHT = 'https://www.facebook.com/giantsforestkukuleganga/';

    public function up(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->where('group', 'social')->where('key', 'facebook')
            ->where('value', self::WRONG)
            ->update(['value' => self::RIGHT, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->where('group', 'social')->where('key', 'facebook')
            ->where('value', self::RIGHT)
            ->update(['value' => self::WRONG, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
