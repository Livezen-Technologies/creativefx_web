<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $settings = [
            ['general', 'site_name', 'CreativeFX', 'string', 1],
            ['general', 'tagline', 'Production · Advertising · Digital Growth', 'string', 1],
            ['brand', 'color_primary', '#CF2030', 'string', 1],
            ['brand', 'color_secondary', '#000000', 'string', 1],
            // Contact and social values ship empty: the header and footer hide
            // each link until it is filled in, so a blank is a working default
            // rather than a wrong number on a live site.
            ['contact', 'email', 'hello@creativefx.lk', 'string', 1],
            ['contact', 'phone', '', 'string', 1],
            ['contact', 'whatsapp', '', 'string', 1],
            ['contact', 'address', '', 'string', 1],
            ['social', 'facebook', '', 'string', 1],
            ['social', 'instagram', '', 'string', 1],
            ['social', 'youtube', '', 'string', 1],
            ['social', 'tiktok', '', 'string', 1],
            ['social', 'linkedin', '', 'string', 1],
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
