<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $settings = [
            ['general', 'site_name', 'Tea Small Holdings Development Authority', 'string', 1],
            ['general', 'site_short', 'TSHDA', 'string', 1],
            ['general', 'tagline', 'Serving Sri Lanka’s tea smallholders', 'string', 1],
            ['general', 'parent_org', 'Ministry of Plantation and Community Infrastructure', 'string', 1],
            ['brand', 'color_primary', '#0F6B45', 'string', 1],
            ['brand', 'color_secondary', '#8A6A18', 'string', 1],

            // Head Office details. The telephone and postal address are the
            // published ones; the general email address is deliberately blank
            // rather than guessed, because a wrong address on a government
            // contact page routes the public's petitions into nowhere. Set it
            // under Settings -> Contact before launch.
            ['contact', 'email', '', 'string', 1],
            ['contact', 'phone', '+94 117 909 021', 'string', 1],
            ['contact', 'phone_alt', '', 'string', 1],
            ['contact', 'fax', '', 'string', 1],
            ['contact', 'whatsapp', '', 'string', 1],
            ['contact', 'address', 'No. 70, Parliament Road, Pelawatte, Battaramulla, Sri Lanka', 'string', 1],
            ['contact', 'hours', 'Monday to Friday, 8.30 a.m. to 4.30 p.m.', 'string', 1],

            ['social', 'facebook', 'https://www.facebook.com/p/Tea-Small-Holdings-Development-Authority-100066699876421/', 'string', 1],
            ['social', 'youtube', 'https://www.youtube.com/@tshda', 'string', 1],
            ['social', 'instagram', '', 'string', 1],
            ['social', 'x', '', 'string', 1],
            ['social', 'tiktok', '', 'string', 1],
            // The review badges are a hotel's furniture, not an Authority's.
            // Blank rating hides the badge row entirely.
            ['social', 'google_reviews_url', '', 'string', 1],
            ['social', 'google_rating', '', 'string', 1],
            ['social', 'google_review_count', '', 'string', 1],
            ['social', 'facebook_reviews_url', '', 'string', 1],
            ['social', 'facebook_recommend', '', 'string', 1],
            ['social', 'facebook_review_count', '', 'string', 1],
            ['analytics', 'ga4_measurement_id', '', 'string', 0],

            ['header', 'sticky', '1', 'bool', 1],
            ['header', 'show_socials', '0', 'bool', 1],
            ['header', 'cta_label', '', 'string', 1],
            ['header', 'cta_url', 'services', 'string', 1],
            ['footer', 'show_nav', '1', 'bool', 1],
            ['footer', 'show_contact', '1', 'bool', 1],
            ['footer', 'show_reviews', '0', 'bool', 1],
            ['footer', 'show_gov_links', '1', 'bool', 1],
            ['footer', 'copyright', '', 'string', 1],
            ['footer', 'privacy_url', 'privacy', 'string', 1],
            ['footer', 'terms_url', 'terms', 'string', 1],
            ['footer', 'accessibility_url', 'accessibility', 'string', 1],
            ['whatsapp', 'enabled', '0', 'bool', 1],
            ['whatsapp', 'label', '', 'string', 1],
            ['whatsapp', 'message', '', 'string', 1],
            ['whatsapp', 'position', 'left', 'string', 1],
            ['whatsapp', 'hide_on', '', 'string', 1],
            ['seo', 'robots', '1', 'bool', 1],
            ['analytics', 'enabled', '1', 'bool', 1],
            ['analytics', 'retention_days', '400', 'string', 0],

            // Mail and spam protection. All blank or off until the Authority
            // supplies its own SMTP account and reCAPTCHA keys: the site saves
            // enquiries without emailing and accepts forms without scoring them.
            ['smtp', 'host', '', 'string', 0],
            ['smtp', 'port', '587', 'string', 0],
            ['smtp', 'crypto', 'tls', 'string', 0],
            ['smtp', 'user', '', 'string', 0],
            ['smtp', 'pass', '', 'string', 0],
            ['smtp', 'from_email', '', 'string', 0],
            ['smtp', 'from_name', '', 'string', 0],
            ['smtp', 'booking_to', '', 'string', 0],
            ['smtp', 'contact_to', '', 'string', 0],
            ['recaptcha', 'enabled', '0', 'bool', 0],
            ['recaptcha', 'site_key', '', 'string', 1],
            ['recaptcha', 'secret_key', '', 'string', 0],
            ['recaptcha', 'threshold', '0.5', 'string', 0],
            ['recaptcha', 'on_booking', '1', 'bool', 0],
            ['recaptcha', 'on_contact', '1', 'bool', 0],
            ['recaptcha', 'on_login', '0', 'bool', 0],
            ['seo', 'meta_keywords', 'tea smallholdings, TSHDA, Sri Lanka tea, replanting subsidy, fertilizer subsidy, tea nursery, Tea Shakthi', 'string', 1],
            ['brand', 'logo_color', '', 'string', 1],
            ['brand', 'logo_white', '', 'string', 1],
            ['brand', 'favicon', '', 'string', 1],
            ['brand', 'og_default', '', 'string', 1],
            ['general', 'site_url', '', 'string', 0],
            ['general', 'admin_email', '', 'string', 0],
            ['general', 'timezone', 'Asia/Colombo', 'string', 1],
            ['general', 'date_format', 'j M Y', 'string', 1],
            ['general', 'currency', 'LKR', 'string', 1],
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
