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
            ['contact', 'email', 'giantsforest.kukuleganga@gmail.com', 'string', 1],
            ['contact', 'phone', '+94 76 573 5600', 'string', 1],
            ['contact', 'phone_alt', '+94 76 758 4908', 'string', 1],
            ['contact', 'whatsapp', '+94765735600', 'string', 1],
            ['contact', 'address', 'Dam Site, Project Road, Kukuleganga, Kalawana 70450, Sri Lanka', 'string', 1],
            ['social', 'facebook', 'https://www.facebook.com/giantsforestkukuleganga/', 'string', 1],
            ['social', 'instagram', 'https://www.instagram.com/giants_forest/', 'string', 1],
            ['social', 'x', 'https://x.com/kukuleganga', 'string', 1],
            ['social', 'tiktok', 'https://www.tiktok.com/@giantsforest_kukuleganga', 'string', 1],
            // The rating badge in the hero. Both numbers go stale on their own —
            // the count climbs whenever a guest writes something and the score
            // moves with it — so they are settings an editor can correct in the
            // console rather than constants that need a deploy. A blank rating
            // hides the badge entirely.
            //
            // These two were read off a search summary of the hotel's Google
            // listing rather than the listing itself, which is a JavaScript
            // application this build cannot render. Worth checking against the
            // Business Profile before they drift.
            ['social', 'google_reviews_url', 'https://www.google.com/travel/search?q=Giants%20Forest%20-%20Kukuleganga&ap=ugEHcmV2aWV3cw', 'string', 1],
            ['social', 'google_rating', '4.7', 'string', 1],
            ['social', 'google_review_count', '125', 'string', 1],
            // Facebook stopped publishing a star average years ago and shows the
            // share of reviewers who recommend the place instead, so the badge
            // carries a percentage, not a score out of five.
            ['social', 'facebook_reviews_url', 'https://www.facebook.com/giantsforestkukuleganga/reviews', 'string', 1],
            ['social', 'facebook_recommend', '100', 'string', 1],
            ['social', 'facebook_review_count', '22', 'string', 1],
            ['analytics', 'ga4_measurement_id', '', 'string', 0],

            // Header, footer and chat-button behaviour. Every default here is
            // what the site did before these were settings, so seeding them
            // changes nothing on a site that has never opened the screen — it
            // only gives the Settings form real rows to edit.
            ['header', 'sticky', '1', 'bool', 1],
            ['header', 'show_socials', '1', 'bool', 1],
            ['header', 'cta_label', '', 'string', 1],
            ['header', 'cta_url', 'booking', 'string', 1],
            ['footer', 'show_nav', '1', 'bool', 1],
            ['footer', 'show_contact', '1', 'bool', 1],
            ['footer', 'show_reviews', '1', 'bool', 1],
            ['footer', 'copyright', '', 'string', 1],
            ['footer', 'privacy_url', '', 'string', 1],
            ['footer', 'terms_url', '', 'string', 1],
            ['whatsapp', 'enabled', '1', 'bool', 1],
            ['whatsapp', 'label', '', 'string', 1],
            ['whatsapp', 'message', '', 'string', 1],
            ['whatsapp', 'position', 'left', 'string', 1],
            ['whatsapp', 'hide_on', '', 'string', 1],
            ['seo', 'robots', '1', 'bool', 1],
            ['analytics', 'enabled', '1', 'bool', 1],

            // Mail and spam protection. All blank or off: until a hotel supplies
            // its own SMTP account and reCAPTCHA keys, the site saves enquiries
            // without emailing and accepts forms without scoring them — which is
            // exactly what it did before either existed.
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
            ['seo', 'meta_keywords', '', 'string', 1],
            // Blank means "use the files that ship with the build"; an upload
            // in the console replaces them without a deploy.
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
