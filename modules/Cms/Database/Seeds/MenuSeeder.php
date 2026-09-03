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
 * The labels are the same language keys the header already used, resolved at
 * render time rather than frozen here, so an item nobody has renamed still
 * follows the translation files.
 */
class MenuSeeder extends Seeder
{
    private const ITEMS = [
        ['accommodation', 'Site.nav.accommodation', 'Accommodation'],
        ['dining',        'Site.nav.dining',         'Dining'],
        ['things-to-do',  'Site.nav.things_to_do',   'Things to Do'],
        ['kalawana',      'Site.nav.kalawana',       'Explore Kalawana'],
        ['gallery',       'Site.nav.gallery',        'Gallery'],
        ['contact',       'Site.nav.contact',        'Contact Us'],
    ];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (['header', 'footer'] as $location) {
            $existing = $this->db->table('menu_items')->where('location', $location)->countAllResults();
            if ($existing > 0) {
                continue;
            }

            $order = 0;
            foreach (self::ITEMS as [$slug, $key, $fallback]) {
                // Stored as the language key. render_menu() resolves a value
                // that looks like a key through lang(), so an untouched item
                // stays translatable while an edited one keeps what was typed.
                $this->db->table('menu_items')->insert([
                    'location'   => $location,
                    'parent_id'  => null,
                    'label'      => json_encode(['en' => $key], JSON_UNESCAPED_UNICODE),
                    'url'        => $slug,
                    'target'     => '_self',
                    'sort_order' => $order++,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
