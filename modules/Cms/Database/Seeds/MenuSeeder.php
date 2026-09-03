<?php

namespace Modules\Cms\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The starting header and footer menus.
 *
 * Insert-only, and only when the menu is empty. Once a menu has rows in it,
 * it is somebody's — reordering, renaming and hiding items is the whole reason
 * the table exists, and a seeder that rewrote them on every deploy would undo
 * that work exactly the way the content seeders used to undo page edits.
 *
 * Labels are stored as the language key, resolved at render time rather than
 * frozen here, so an item nobody has renamed still follows the translation
 * files — which is how the same menu reads in Sinhala and Tamil without an
 * editor retyping it three times.
 */
class MenuSeeder extends Seeder
{
    /** [slug, language key, children[]] */
    private const HEADER = [
        ['about-us', 'Site.nav.about', [
            ['about-us',                 'Site.nav.overview'],
            ['vision-mission',           'Site.nav.vision_mission'],
            ['strategic-plan',           'Site.nav.strategic_plan'],
            ['senior-management',        'Site.nav.senior_management'],
            ['divisions',                'Site.nav.divisions'],
            ['organisational-structure', 'Site.nav.org_structure'],
        ]],
        ['services', 'Site.nav.services', [
            ['services',         'Site.nav.all_services'],
            ['land-development', 'Site.nav.land_development'],
            ['societies',        'Site.nav.societies'],
            ['hantana',          'Site.nav.hantana'],
        ]],
        ['media-centre', 'Site.nav.media_centre', [
            ['news',          'Site.nav.news'],
            ['announcements', 'Site.nav.announcements'],
            ['gallery',       'Site.nav.photo_gallery'],
            ['videos',        'Site.nav.video_gallery'],
        ]],
        ['statistics', 'Site.nav.statistics', []],
        ['downloads',  'Site.nav.downloads',  []],
        ['vacancies',  'Site.nav.vacancies',  []],
        ['directory',  'Site.nav.directory',  []],
        ['contact',    'Site.nav.contact',    []],
    ];

    private const FOOTER = [
        ['about-us',        'Site.nav.about',         []],
        ['services',        'Site.nav.services',      []],
        ['downloads',       'Site.nav.downloads',     []],
        ['statistics',      'Site.nav.statistics',    []],
        ['vacancies',       'Site.nav.vacancies',     []],
        ['directory',       'Site.nav.directory',     []],
        ['faqs',            'Site.nav.faqs',          []],
        ['sitemap',         'Site.nav.sitemap',       []],
        ['feedback',        'Site.nav.feedback',      []],
        ['field-officer',   'Site.nav.field_officer', []],
    ];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (['header' => self::HEADER, 'footer' => self::FOOTER] as $location => $items) {
            if ($this->db->table('menu_items')->where('location', $location)->countAllResults() > 0) {
                continue;
            }

            $order = 0;
            foreach ($items as [$slug, $key, $children]) {
                $parentId = $this->insertItem($location, null, $slug, $key, $order++, $now);

                $childOrder = 0;
                foreach ($children as [$childSlug, $childKey]) {
                    $this->insertItem($location, $parentId, $childSlug, $childKey, $childOrder++, $now);
                }
            }
        }
    }

    private function insertItem(string $location, ?int $parentId, string $slug, string $key, int $order, string $now): int
    {
        $this->db->table('menu_items')->insert([
            'location'   => $location,
            'parent_id'  => $parentId,
            'label'      => json_encode(['en' => $key], JSON_UNESCAPED_UNICODE),
            'url'        => $slug,
            'target'     => '_self',
            'sort_order' => $order,
            'status'     => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }
}
