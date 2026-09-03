<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The rest of the per-page SEO fields.
 *
 * The table already carried a meta title, a meta description and an Open Graph
 * image. What it could not express: keywords, a canonical URL, and an OG title
 * and description that differ from the page's own — which they usually should,
 * because a search result is read in a list of ten and a shared card is read on
 * its own.
 *
 * The three text fields are locale maps like the titles beside them, so a page
 * can be described differently in each language. The canonical is a single URL:
 * it names one address for the page, and that does not vary by locale in the
 * way copy does — a localized page canonicalises to itself.
 */
class AddPageSeoFields extends Migration
{
    private const COLUMNS = [
        'meta_keywords'   => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map of comma-separated keywords'],
        'canonical_url'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'Absolute URL; blank means the page canonicalises to itself'],
        'og_title'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map; falls back to meta_title then title'],
        'og_description'  => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map; falls back to meta_description'],
    ];

    public function up(): void
    {
        $add = [];
        foreach (self::COLUMNS as $name => $spec) {
            if (! $this->db->fieldExists($name, 'pages')) {
                $add[$name] = $spec;
            }
        }
        if ($add !== []) {
            $this->forge->addColumn('pages', $add);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::COLUMNS) as $name) {
            if ($this->db->fieldExists($name, 'pages')) {
                $this->forge->dropColumn('pages', $name);
            }
        }
    }
}
