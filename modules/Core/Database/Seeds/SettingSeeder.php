<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $settings = [
            ['general', 'site_name', 'Kukuleganga Giants Forest', 'string', 1],
            ['general', 'tagline', 'Stay in Sapphire Land', 'string', 1],
            ['brand', 'color_primary', '#346142', 'string', 1],
            ['brand', 'color_secondary', '#1B2E22', 'string', 1],
            // The source site's theme obfuscates both addresses, so the real
            // ones could not be read off it. Left blank rather than guessed:
            // the footer and contact page render an address only when set, so
            // a wrong one cannot be published by accident.
            ['contact', 'email', '', 'string', 1],
            ['contact', 'phone', '+94 76 573 5600', 'string', 1],
            ['contact', 'phone_alt', '+94 76 758 4908', 'string', 1],
            ['contact', 'whatsapp', '+94765735600', 'string', 1],
            ['contact', 'address', 'Dam Site, Project Road, Kukuleganga, Kalawana 70450, Sri Lanka', 'string', 1],
            ['social', 'facebook', 'https://www.facebook.com/giantsforestkukuleganga/', 'string', 1],
            ['social', 'instagram', '', 'string', 1],
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
