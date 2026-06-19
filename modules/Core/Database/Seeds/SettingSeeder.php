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
            ['contact', 'email', 'hello@norlanka.com', 'string', 1],
            ['contact', 'phone', '+94 11 000 0000', 'string', 1],
            ['contact', 'whatsapp', '+94770000000', 'string', 1],
            ['social', 'linkedin', 'https://www.linkedin.com/company/norlanka', 'string', 1],
            ['social', 'instagram', 'https://instagram.com/norlanka', 'string', 1],
            ['analytics', 'ga4_measurement_id', '', 'string', 0],
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
