<?php

namespace Modules\Admin\Config;

/**
 * What the Settings screen offers, as data.
 *
 * The settings table is key/value, and the admin screen for it was a list of
 * rows: a group, a key, and a textarea. Everything was editable and nothing was
 * explained — an editor had to already know that `whatsapp` lives in `contact`
 * and wants digits with a country code and no spaces, and that a typo there
 * silently breaks the chat button rather than showing an error.
 *
 * Declaring the fields lets one form render them all with labels, help, types
 * and validation, and lets the same declaration decide what is encrypted and
 * what is never echoed back. A setting that is not listed here still works and
 * is still editable on the raw Settings screen; this is the guided front for
 * the ones an editor actually reaches for.
 *
 * Field shape:
 *   key, label, type, help?, options?, rules?, placeholder?, secret?, recommend?
 *   type ∈ text|textarea|email|url|tel|number|select|checkbox|image|password|color
 */
final class SettingsSchema
{
    /** @return array<string, array{label:string, blurb:string, fields:list<array>}> */
    public static function groups(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'blurb' => 'The hotel\'s name, how to reach it, and the images the site falls back to.',
                'fields' => [
                    ['key' => 'site_name', 'group' => 'general', 'label' => 'Website name', 'type' => 'text', 'rules' => 'required|max_length[120]', 'help' => 'Used in the browser tab, the loading screen, the footer copyright and every share card'],
                    ['key' => 'tagline', 'group' => 'general', 'label' => 'Tagline', 'type' => 'text', 'help' => 'Shown under the footer logo, and as the description of any page that has none of its own'],
                    ['key' => 'site_url', 'group' => 'general', 'label' => 'Website address', 'type' => 'url', 'help' => 'For reference only — the live address comes from the server configuration and changing it here does not move the site'],
                    ['key' => 'admin_email', 'group' => 'general', 'label' => 'Admin email', 'type' => 'email', 'help' => 'Where system notices go. Booking and contact notifications have their own recipients under Email'],
                    ['key' => 'timezone', 'group' => 'general', 'label' => 'Time zone', 'type' => 'select', 'options' => self::TIMEZONES, 'help' => 'Used when a date is shown or a booking is stamped'],
                    ['key' => 'date_format', 'group' => 'general', 'label' => 'Date format', 'type' => 'select', 'options' => [
                        'j M Y' => '3 Sep 2026', 'd/m/Y' => '03/09/2026', 'm/d/Y' => '09/03/2026', 'Y-m-d' => '2026-09-03', 'l, j F Y' => 'Thursday, 3 September 2026',
                    ]],
                    ['key' => 'currency', 'group' => 'general', 'label' => 'Currency', 'type' => 'select', 'options' => ['LKR' => 'LKR — Sri Lankan rupee', 'USD' => 'USD — US dollar', 'EUR' => 'EUR — Euro', 'GBP' => 'GBP — Pound sterling'], 'help' => 'Shown wherever a price appears'],
                ],
            ],

            'contact' => [
                'label' => 'Contact details',
                'blurb' => 'Shown in the footer, on the contact page, and used by the chat button. Anything left blank is simply not shown.',
                'fields' => [
                    ['key' => 'phone', 'group' => 'contact', 'label' => 'Phone', 'type' => 'tel'],
                    ['key' => 'phone_alt', 'group' => 'contact', 'label' => 'Second phone', 'type' => 'tel'],
                    ['key' => 'email', 'group' => 'contact', 'label' => 'Email', 'type' => 'email'],
                    ['key' => 'address', 'group' => 'contact', 'label' => 'Address', 'type' => 'textarea'],
                    ['key' => 'whatsapp', 'group' => 'contact', 'label' => 'WhatsApp number', 'type' => 'tel', 'placeholder' => '+94765735600', 'help' => 'With the country code. Spaces and dashes are stripped before the link is built, so type it however reads best'],
                ],
            ],

            'branding' => [
                'label' => 'Logos and icons',
                'blurb' => 'Two colourways of the logo are kept because the header sits on a photograph at the top of a page and on a solid ground once scrolled — and a visitor can switch the whole site to dark at any moment.',
                'fields' => [
                    ['key' => 'logo_color', 'group' => 'brand', 'label' => 'Logo — colour', 'type' => 'image', 'recommend' => 'Transparent PNG or SVG, at least 300×200. Used on light grounds.'],
                    ['key' => 'logo_white', 'group' => 'brand', 'label' => 'Logo — white', 'type' => 'image', 'recommend' => 'The same mark knocked out in white, transparent PNG or SVG. Used on dark grounds and over photography.'],
                    ['key' => 'favicon', 'group' => 'brand', 'label' => 'Favicon', 'type' => 'image', 'recommend' => 'Square, at least 512×512. A wide logo squeezed into 16px is unreadable — crop to a single mark.'],
                    ['key' => 'og_default', 'group' => 'brand', 'label' => 'Default share image', 'type' => 'image', 'recommend' => '1200×630. Used for any page without a share image of its own.'],
                    ['key' => 'color_primary', 'group' => 'brand', 'label' => 'Primary colour', 'type' => 'color'],
                    ['key' => 'color_secondary', 'group' => 'brand', 'label' => 'Secondary colour', 'type' => 'color'],
                ],
            ],

            'header' => [
                'label' => 'Header',
                'blurb' => 'The bar itself. Its links live under Navigation menus.',
                'fields' => [
                    ['key' => 'sticky', 'group' => 'header', 'label' => 'Stay visible when scrolling', 'type' => 'checkbox', 'help' => 'On: the bar follows the reader down the page, shrinking as it goes. Off: it scrolls away with the rest'],
                    ['key' => 'show_socials', 'group' => 'header', 'label' => 'Show social icons', 'type' => 'checkbox', 'help' => 'Only on wide screens either way — below 1280px the bar has no room for them, and the mobile menu carries them'],
                    ['key' => 'cta_label', 'group' => 'header', 'label' => 'Button text', 'type' => 'text', 'placeholder' => 'Book Now', 'help' => 'Leave blank for no button in the bar'],
                    ['key' => 'cta_url', 'group' => 'header', 'label' => 'Button links to', 'type' => 'text', 'placeholder' => 'contact', 'help' => 'A page slug, or a full address. Type "booking" to open the booking form instead of going anywhere'],
                ],
            ],

            'footer' => [
                'label' => 'Footer',
                'blurb' => 'The columns, the sign-off, and the small print.',
                'fields' => [
                    ['key' => 'show_nav', 'group' => 'footer', 'label' => 'Show the navigation column', 'type' => 'checkbox'],
                    ['key' => 'show_contact', 'group' => 'footer', 'label' => 'Show the contact column', 'type' => 'checkbox'],
                    ['key' => 'show_reviews', 'group' => 'footer', 'label' => 'Show the guest reviews column', 'type' => 'checkbox'],
                    ['key' => 'copyright', 'group' => 'footer', 'label' => 'Copyright line', 'type' => 'text', 'help' => 'Leave blank for "© {year} {website name}. All rights reserved." The year is always the current one'],
                    ['key' => 'privacy_url', 'group' => 'footer', 'label' => 'Privacy policy link', 'type' => 'text', 'placeholder' => 'privacy', 'help' => 'A page slug or full address. Blank hides the link'],
                    ['key' => 'terms_url', 'group' => 'footer', 'label' => 'Terms link', 'type' => 'text', 'placeholder' => 'terms', 'help' => 'A page slug or full address. Blank hides the link'],
                ],
            ],

            'social' => [
                'label' => 'Social and reviews',
                'blurb' => 'Each icon appears only when its address is filled in. The review badges need both a link and a figure before they show.',
                'fields' => [
                    ['key' => 'facebook', 'group' => 'social', 'label' => 'Facebook page', 'type' => 'url'],
                    ['key' => 'instagram', 'group' => 'social', 'label' => 'Instagram', 'type' => 'url'],
                    ['key' => 'x', 'group' => 'social', 'label' => 'X', 'type' => 'url'],
                    ['key' => 'tiktok', 'group' => 'social', 'label' => 'TikTok', 'type' => 'url'],
                    ['key' => 'google_reviews_url', 'group' => 'social', 'label' => 'Google reviews link', 'type' => 'url'],
                    ['key' => 'google_rating', 'group' => 'social', 'label' => 'Google rating', 'type' => 'text', 'placeholder' => '4.7', 'help' => 'Out of 5. Blank hides the Google badge'],
                    ['key' => 'google_review_count', 'group' => 'social', 'label' => 'Google review count', 'type' => 'number'],
                    ['key' => 'facebook_reviews_url', 'group' => 'social', 'label' => 'Facebook reviews link', 'type' => 'url'],
                    ['key' => 'facebook_recommend', 'group' => 'social', 'label' => 'Facebook recommend %', 'type' => 'number', 'placeholder' => '100', 'help' => 'Facebook publishes a share of reviewers who recommend, not a star average. Blank hides the Facebook badge'],
                    ['key' => 'facebook_review_count', 'group' => 'social', 'label' => 'Facebook review count', 'type' => 'number'],
                ],
            ],

            'whatsapp' => [
                'label' => 'WhatsApp button',
                'blurb' => 'The floating chat button. The number itself is under Contact details.',
                'fields' => [
                    ['key' => 'enabled', 'group' => 'whatsapp', 'label' => 'Show the button', 'type' => 'checkbox'],
                    ['key' => 'label', 'group' => 'whatsapp', 'label' => 'Button label', 'type' => 'text', 'help' => 'Shown beside the icon on wider screens. Blank for the icon alone'],
                    ['key' => 'message', 'group' => 'whatsapp', 'label' => 'Prefilled message', 'type' => 'textarea', 'help' => 'What the guest\'s message box already contains when the chat opens'],
                    ['key' => 'position', 'group' => 'whatsapp', 'label' => 'Position', 'type' => 'select', 'options' => ['left' => 'Bottom left', 'right' => 'Bottom right']],
                    ['key' => 'hide_on', 'group' => 'whatsapp', 'label' => 'Hide on these pages', 'type' => 'text', 'placeholder' => 'contact, gallery', 'help' => 'Comma-separated page slugs. Blank shows it everywhere'],
                ],
            ],

            'seo' => [
                'label' => 'SEO and analytics',
                'blurb' => 'Site-wide defaults. Each page has its own title, description and share card under Pages.',
                'fields' => [
                    ['key' => 'enabled', 'group' => 'analytics', 'label' => 'Measure visits on this server', 'type' => 'checkbox', 'help' => 'Records page views in the site\'s own database for the Analytics screen. No third-party script, no cookie, and no IP address is stored — a visitor is counted by a hash that changes every day. Requests sending Do Not Track are never recorded'],
                    ['key' => 'ga4_measurement_id', 'group' => 'analytics', 'label' => 'Google Analytics 4 ID', 'type' => 'text', 'placeholder' => 'G-XXXXXXXXXX', 'help' => 'Separate from the above, and optional. Blank means no Google tag is loaded at all — no script, no cookie'],
                    ['key' => 'meta_keywords', 'group' => 'seo', 'label' => 'Default keywords', 'type' => 'text', 'help' => 'Comma-separated. Google has not used these for many years; other engines still read them'],
                    ['key' => 'robots', 'group' => 'seo', 'label' => 'Allow search engines', 'type' => 'checkbox', 'help' => 'Off puts a noindex on every page. Use while the site is being prepared — and remember to turn it back on'],
                ],
            ],
        ];
    }

    /** A short, hand-kept list rather than 400 entries nobody will scroll. */
    private const TIMEZONES = [
        'Asia/Colombo'    => 'Colombo (Sri Lanka)',
        'Asia/Kolkata'    => 'Kolkata (India)',
        'Asia/Dubai'      => 'Dubai',
        'Europe/London'   => 'London',
        'Europe/Berlin'   => 'Berlin',
        'America/New_York' => 'New York',
        'UTC'             => 'UTC',
    ];

    /** Every field in every group, flattened. */
    public static function fields(): array
    {
        $all = [];
        foreach (self::groups() as $group) {
            foreach ($group['fields'] as $field) {
                $all[] = $field;
            }
        }

        return $all;
    }
}
