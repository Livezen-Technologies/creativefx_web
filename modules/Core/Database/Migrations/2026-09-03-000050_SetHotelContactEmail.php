<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The hotel's own email address, supplied by the owner.
 *
 * SettingSeeder now carries it as the default, which covers a fresh install and
 * nothing else: it inserts with `ignore`, so the row every deployed site
 * already has is never revisited. This writes it once.
 *
 * Applied only where the row is empty or still holds the foreign address the
 * previous migration cleared — so if somebody has entered a different address in
 * the admin, theirs stands.
 */
class SetHotelContactEmail extends Migration
{
    private const EMAIL   = 'giantsforest.kukuleganga@gmail.com';
    private const FOREIGN = 'shankerv@viswakula.com';

    public function up(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->where('group', 'contact')->where('key', 'email')
            ->groupStart()
                ->where('value', '')
                ->orWhere('value', null)
                ->orWhere('value', self::FOREIGN)
            ->groupEnd()
            ->update(['value' => self::EMAIL, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->where('group', 'contact')->where('key', 'email')
            ->where('value', self::EMAIL)
            ->update(['value' => '', 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
