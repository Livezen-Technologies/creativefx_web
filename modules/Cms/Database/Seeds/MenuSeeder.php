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
 * files — which is how the same menu reads in Sinhala without an editor
 * retyping it.
 *
 * Every key below has to actually resolve. A key that does not is rendered
 * literally, so a menu reading "Site.nav.downloads" appears in the header of
 * every page on the site — which is exactly what happened after the last
 * rebrand, because the seeder still named the previous site's sections.
 */
class MenuSeeder extends Seeder
{
    /** [path, language key, children[]] */
    private const HEADER = [
        ['courses', 'Site.nav.courses', [
            ['courses',             'Catalog.courses.all'],
            ['adobe',               'Catalog.pillar.adobe'],
            ['ai',                  'Catalog.pillar.ai'],
            ['on-demand',           'Catalog.ondemand.title'],
            ['certificates',        'Catalog.bundles.certificates_title'],
            ['bootcamps',           'Catalog.bundles.bootcamps_title'],
            ['adobe/certification', 'Catalog.pillars.cert_title'],
        ]],
        ['schedule', 'Site.nav.schedule', []],
        ['corporate', 'Site.home.corporate_heading', []],
        ['resources', 'Catalog.resources.title', [
            ['resources', 'Catalog.resources.title'],
            ['webinars',  'Catalog.webinars.title'],
            ['blog',      'Site.news.title'],
        ]],
        ['locations', 'Catalog.locations.title', []],
        ['contact', 'Site.nav.contact', []],
    ];

    private const FOOTER = [
        ['courses',       'Catalog.courses.all',                []],
        ['schedule',      'Catalog.schedule.title',             []],
        ['certificates',  'Catalog.bundles.certificates_title', []],
        ['on-demand',     'Catalog.ondemand.title',             []],
        ['corporate',     'Site.home.corporate_heading',        []],
        ['instructors',   'Catalog.instructors.title',          []],
        ['reviews',       'Catalog.reviews.title',              []],
        ['blog',          'Site.news.title',                    []],
        ['about',         'Site.nav.about',                     []],
        ['careers',       'Site.careers.title',                 []],
        ['faq',           'Site.nav.faq',                       []],
        ['sitemap',       'Site.sitemap.title',                 []],
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
