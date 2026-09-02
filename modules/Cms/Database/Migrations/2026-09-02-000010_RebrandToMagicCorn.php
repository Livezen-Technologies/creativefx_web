<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rebrand of this deployment from the Norlanka apparel site to Magic Corn.
 *
 * The seeders cannot do this on their own:
 *   - CorporateContentSeeder only rewrites the slugs it knows about, so the
 *     retired pages would stay published and reachable.
 *   - SettingSeeder inserts with ignore() and TranslationSeeder writes with
 *     onlyIfMissing, so neither ever rewrites a row that already exists.
 * This clears what they cannot, and the seeders — which run straight after
 * migrations — repopulate from the rebranded files.
 *
 * Guarded on the configured base URL. These repositories share history with
 * the Norlanka site, so a misdirected deploy could otherwise rebrand the wrong
 * installation. If the guard skips, nothing here runs.
 */
class RebrandToMagicCorn extends Migration
{
    /** Pages with no Magic Corn equivalent. */
    private const RETIRED_PAGES = [
        'our-story', 'our-expertise', 'manufacturing', 'impact', 'careers', 'showroom',
    ];

    private function isMagicCorn(): bool
    {
        $base = (string) (env('app.baseURL') ?: config('App')->baseURL);
        $db   = (string) ($this->db->getDatabase() ?? '');

        // Either signal is enough: the deployed install has both a magiccorn
        // base URL and the magiccorn_prod database, while the Norlanka install
        // matches on neither.
        return str_contains($base, 'magiccorn') || str_contains(basename($db), 'magiccorn');
    }

    public function up(): void
    {
        if (! $this->isMagicCorn()) {
            echo "  skipped: baseURL is not a Magic Corn install\n";

            return;
        }

        // 1. Drop the retired pages, and the sections/blocks hanging off them.
        $rows = $this->db->table('pages')->select('id')
            ->whereIn('slug', self::RETIRED_PAGES)->get()->getResultArray();
        $pageIds = array_column($rows, 'id');
        if ($pageIds !== []) {
            $sectionIds = array_column(
                $this->db->table('page_sections')->select('id')
                    ->whereIn('page_id', $pageIds)->get()->getResultArray(),
                'id'
            );
            if ($sectionIds !== []) {
                $this->db->table('page_blocks')->whereIn('section_id', $sectionIds)->delete();
            }
            $this->db->table('page_sections')->whereIn('page_id', $pageIds)->delete();
            $this->db->table('pages')->whereIn('id', $pageIds)->delete();
        }

        // 2. Retire the apparel catalogue. Deleted by the seeders' own slugs
        //    rather than truncating the tables, so anything added by hand in
        //    Admin -> Products survives.
        $oldCats = ['babywear', 'childrenswear', 'kids-nightwear', 'school-wear', 'accessories',
                    'true-knits', 'hosiery-toys', 'adults-woven', 'adults-jersey', 'activewear',
                    'maternity', 'adults-essentials', 'nightwear'];
        $catRows = $this->db->table('product_categories')->select('id')
            ->whereIn('slug', $oldCats)->get()->getResultArray();
        $catIds = array_column($catRows, 'id');
        if ($catIds !== []) {
            $this->db->table('products')->whereIn('category_id', $catIds)->delete();
            $this->db->table('product_categories')->whereIn('id', $catIds)->delete();
        }

        // 3. Clear the Site UI strings so TranslationSeeder re-imports the
        //    rebranded language files. A rebrand touches almost every string,
        //    so clearing the group beats enumerating keys — and it also sweeps
        //    up the keys that no longer exist.
        $this->db->table('translations')->where('group', 'Site')->delete();

        // 4. Point the brand and contact settings at Magic Corn.
        $now = date('Y-m-d H:i:s');
        $settings = [
            ['general', 'site_name', 'Magic Corn'],
            ['general', 'tagline',   'Corn in a Cup'],
            // Was a real address belonging to somebody at an unrelated company.
            // It is nobody's contact address for this site, and an empty
            // setting renders nothing rather than publishing a stranger.
            ['contact', 'email',     ''],
            ['contact', 'phone',     '+94 11 366 4444'],
            ['contact', 'whatsapp',  '+94714545610'],
        ];
        foreach ($settings as [$group, $key, $value]) {
            $this->db->table('settings')->where('group', $group)->where('key', $key)
                ->update(['value' => $value, 'updated_at' => $now]);
        }

        // Social: LinkedIn is retired, Facebook and Instagram take its place.
        $this->db->table('settings')->where('group', 'social')->where('key', 'linkedin')->delete();
        foreach ([['facebook', 'https://www.facebook.com/magiccornlk/'],
                  ['instagram', 'https://www.instagram.com/magiccorn.lk/']] as [$key, $url]) {
            $existing = $this->db->table('settings')->where('group', 'social')->where('key', $key)->get()->getRowArray();
            if ($existing === null) {
                $this->db->table('settings')->insert([
                    'group' => 'social', 'key' => $key, 'value' => $url,
                    'type' => 'string', 'is_public' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            } else {
                $this->db->table('settings')->where('group', 'social')->where('key', $key)
                    ->update(['value' => $url, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // One-way: the retired pages and the Norlanka strings are recreated only
        // by re-seeding from the pre-rebrand files.
    }
}
