<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Turns maintenance mode on.
 *
 * The switch lives in the `settings` table, and SettingSeeder inserts with
 * ignore(), so seeding alone would never flip an existing install. This
 * migration is what takes the deployed site offline the moment
 * `deploy/update.sh` runs its `php spark migrate --all` step.
 *
 * Rolling back (`php spark migrate:rollback`) brings the site back, as does
 * Admin -> Maintenance or `php spark maintenance off` — those are the normal
 * way back up; this migration only sets the starting position.
 */
class EnableMaintenanceMode extends Migration
{
    /** [key, value] — the options this migration is responsible for. */
    private const DEFAULTS = [
        ['enabled', '1', 'bool'],
        ['brand', '', 'string'],
        ['headline', '', 'string'],
        ['message', '', 'string'],
        ['until', '', 'string'],
        ['allow_ips', '', 'string'],
        ['retry_after', '3600', 'int'],
        ['bypass_key', '', 'string'],
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (self::DEFAULTS as [$key, $value, $type]) {
            $exists = $this->db->table('settings')
                ->where('group', 'maintenance')->where('key', $key)
                ->countAllResults() > 0;

            if ($exists) {
                // Only `enabled` is forced — copy someone already wrote in the
                // admin survives.
                if ($key === 'enabled') {
                    $this->db->table('settings')
                        ->where('group', 'maintenance')->where('key', 'enabled')
                        ->update(['value' => '1', 'updated_at' => $now]);
                }

                continue;
            }

            $this->db->table('settings')->insert([
                'group'      => 'maintenance',
                'key'        => $key,
                'value'      => $value,
                'type'       => $type,
                'is_public'  => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->db->table('settings')
            ->where('group', 'maintenance')->where('key', 'enabled')
            ->update(['value' => '0', 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
