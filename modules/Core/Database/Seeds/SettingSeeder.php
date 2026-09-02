<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $settings = [
            ['general', 'site_name', 'Magic Corn', 'string', 1],
            ['general', 'tagline', 'Corn in a Cup', 'string', 1],
            ['brand', 'color_primary', '#CF2030', 'string', 1],
            ['brand', 'color_secondary', '#000000', 'string', 1],
            ['contact', 'email', 'shankerv@viswakula.com', 'string', 1],
            ['contact', 'phone', '+94 11 366 4444', 'string', 1],
            ['contact', 'whatsapp', '+94714545610', 'string', 1],
            ['social', 'facebook', 'https://www.facebook.com/magiccornlk/', 'string', 1],
            ['social', 'instagram', 'https://www.instagram.com/magiccorn.lk/', 'string', 1],
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
