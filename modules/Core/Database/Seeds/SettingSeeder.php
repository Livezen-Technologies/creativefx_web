<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $settings = [
            ['general', 'site_name', 'Norlanka', 'string', 1],
            ['general', 'tagline', 'Responsible Sourcing · Design · Innovation', 'string', 1],
            ['brand', 'color_primary', '#CF2030', 'string', 1],
            ['brand', 'color_secondary', '#000000', 'string', 1],
            ['contact', 'email', 'info@norlankamfg.com', 'string', 1],
            ['contact', 'phone', '+94 11 300 3010', 'string', 1],
            ['contact', 'whatsapp', '+94770000000', 'string', 1],
            ['social', 'linkedin', 'https://www.linkedin.com/company/norlanka', 'string', 1],
            ['social', 'instagram', 'https://instagram.com/norlanka', 'string', 1],
            ['analytics', 'ga4_measurement_id', '', 'string', 0],

            // Maintenance mode. A fresh install comes up live; everything else
            // is empty so the offline page falls back to its own built-in copy,
            // which is already written in all four site languages.
            ['maintenance', 'enabled', '0', 'bool', 0],
            ['maintenance', 'brand', '', 'string', 0],
            ['maintenance', 'headline', '', 'string', 0],
            ['maintenance', 'message', '', 'string', 0],
            ['maintenance', 'until', '', 'string', 0],
            ['maintenance', 'allow_ips', '', 'string', 0],
            ['maintenance', 'retry_after', '3600', 'int', 0],
            ['maintenance', 'bypass_key', '', 'string', 0],
        ];

        $rows = [];
        foreach ($settings as [$group, $key, $value, $type, $public]) {
            $rows[] = [
                'group'      => $group,
                'key'        => $key,
                'value'      => $value,
                'type'       => $type,
                'is_public'  => $public,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('settings')->ignore(true)->insertBatch($rows);
    }
}
