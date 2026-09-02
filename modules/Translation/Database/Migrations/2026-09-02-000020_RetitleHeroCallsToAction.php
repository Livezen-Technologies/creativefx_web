<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Point the home hero's two calls to action at what they now do.
 *
 * The hero used to offer "See the rooms" and "Things to do", both plain links.
 * It now opens the booking dialog and plays the hotel's film, so the labels
 * have to say so.
 *
 * Editing modules/Site/Language/en/Site.php is not enough on a site that is
 * already live: TranslationSeeder copies the language file into the
 * `translations` table only where a key is missing, deliberately, so that
 * re-seeding never overwrites what somebody edited in the admin. That is the
 * right rule — and it means a value that shipped wrong stays wrong until
 * something changes it once, on purpose. This is that change.
 *
 * Each update is conditional on the old text still being there, so if anyone
 * has already reworded these, their wording is left alone.
 */
class RetitleHeroCallsToAction extends Migration
{
    private const RETITLE = [
        'home.hero.primary'   => ['See the rooms', 'Book Now'],
        'home.hero.secondary' => ['Things to do', 'Watch the film'],
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        foreach (self::RETITLE as $key => [$from, $to]) {
            $this->db->table('translations')
                ->where('group', 'Site')->where('locale', 'en')
                ->where('key', $key)->where('value', $from)
                ->update(['value' => $to, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        foreach (self::RETITLE as $key => [$from, $to]) {
            $this->db->table('translations')
                ->where('group', 'Site')->where('locale', 'en')
                ->where('key', $key)->where('value', $to)
                ->update(['value' => $from, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
