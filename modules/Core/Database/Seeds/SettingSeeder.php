<?php

namespace Modules\Core\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Every row here is a default, not a decision. `ignore(true)` on the
        // insert means a re-run never overwrites an edit, so this file is the
        // floor a fresh install starts from and nothing more.
        //
        // Blank is used deliberately and often. A telephone number, a street
        // address, an SMTP host and a merchant key are all things the school
        // supplies; guessing any of them produces a site that looks finished
        // and misroutes real customers. The README lists what is still blank.
        $settings = [
            ['general', 'site_name', 'MyLearnPlus', 'string', 1],
            ['general', 'site_short', 'MyLearnPlus', 'string', 1],
            ['general', 'tagline', 'Adobe Creative Cloud and AI training', 'string', 1],
            ['general', 'parent_org', 'Livezen Technologies', 'string', 1],
            ['brand', 'color_primary', '#3F35C7', 'string', 1],
            ['brand', 'color_secondary', '#92400E', 'string', 1],

            // Contact. Left blank rather than invented — see the note above.
            ['contact', 'email', 'hello@mylearnplus.com', 'string', 1],
            ['contact', 'phone', '', 'string', 1],
            ['contact', 'phone_alt', '', 'string', 1],
            ['contact', 'fax', '', 'string', 1],
            ['contact', 'whatsapp', '', 'string', 1],
            ['contact', 'address', '', 'string', 1],
            ['contact', 'hours', 'Monday to Friday, 9.00 a.m. to 5.30 p.m.', 'string', 1],

            // No social accounts are seeded. A link to a profile that does not
            // exist is worse than no link, and the footer renders only the
            // networks that carry a URL.
            ['social', 'facebook', '', 'string', 1],
            ['social', 'linkedin', '', 'string', 1],
            ['social', 'youtube', '', 'string', 1],
            ['social', 'instagram', '', 'string', 1],
            ['social', 'x', '', 'string', 1],
            ['social', 'tiktok', '', 'string', 1],
            // Review badges show only when a genuine rating is entered. Blank
            // hides the row, which is where a new school starts.
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
            ['header', 'cta_url', 'courses', 'string', 1],
            ['footer', 'show_nav', '1', 'bool', 1],
            ['footer', 'show_contact', '1', 'bool', 1],
            ['footer', 'show_reviews', '0', 'bool', 1],
            ['footer', 'show_categories', '1', 'bool', 1],
            ['footer', 'copyright', '', 'string', 1],
            ['footer', 'privacy_url', 'policies-privacy', 'string', 1],
            ['footer', 'terms_url', 'policies-terms', 'string', 1],
            ['footer', 'accessibility_url', 'policies-accessibility', 'string', 1],

            ['whatsapp', 'enabled', '0', 'bool', 1],
            ['whatsapp', 'label', '', 'string', 1],
            ['whatsapp', 'message', '', 'string', 1],
            ['whatsapp', 'position', 'left', 'string', 1],
            ['whatsapp', 'hide_on', '', 'string', 1],
            ['assistant', 'enabled', '1', 'bool', 1],
            ['assistant', 'hide_on', 'checkout,cart', 'string', 1],
            ['language_modal', 'enabled', '1', 'bool', 1],

            ['seo', 'robots', '1', 'bool', 1],
            ['seo', 'meta_keywords', 'Adobe training, Photoshop course, Illustrator course, InDesign course, Premiere Pro course, AI training, prompt engineering, Colombo, Sri Lanka', 'string', 1],
            ['analytics', 'enabled', '1', 'bool', 1],
            ['analytics', 'retention_days', '400', 'string', 0],

            // Mail and spam protection. All blank or off until the school
            // supplies its own SMTP account and reCAPTCHA keys: the site saves
            // enquiries without emailing and accepts forms without scoring them,
            // which is the right way round — losing a booking to an unreachable
            // mail server is worse than a missed notification.
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

            // ── Payments ────────────────────────────────────────────────────
            // Every gateway ships unconfigured. GatewayRegistry offers only the
            // ones that have keys, so the site takes bookings by bank transfer
            // on day one and gains a card button the moment a merchant account
            // is live — with no half-working state in between.
            ['payments', 'enabled', 'payhere,stripe,paypal,bank', 'string', 0],
            ['payments', 'stripe_secret', '', 'string', 0],
            ['payments', 'stripe_webhook_secret', '', 'string', 0],
            ['payments', 'payhere_merchant_id', '', 'string', 0],
            ['payments', 'payhere_merchant_secret', '', 'string', 0],
            ['payments', 'payhere_sandbox', '1', 'bool', 0],
            ['payments', 'paypal_client_id', '', 'string', 0],
            ['payments', 'paypal_secret', '', 'string', 0],
            ['payments', 'paypal_webhook_id', '', 'string', 0],
            ['payments', 'paypal_sandbox', '1', 'bool', 0],
            ['payments', 'bank_instructions', '', 'string', 1],

            ['brand', 'logo_color', '', 'string', 1],
            ['brand', 'logo_white', '', 'string', 1],
            ['brand', 'favicon', '', 'string', 1],
            ['brand', 'og_default', '', 'string', 1],
            ['general', 'site_url', '', 'string', 0],
            ['general', 'admin_email', '', 'string', 0],
            ['general', 'timezone', 'Asia/Colombo', 'string', 1],
            ['general', 'date_format', 'j M Y', 'string', 1],
            ['general', 'currency', 'USD', 'string', 1],
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
