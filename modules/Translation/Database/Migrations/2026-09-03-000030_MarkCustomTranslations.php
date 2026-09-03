<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tell an edited translation apart from an imported one.
 *
 * TranslationSeeder writes onlyIfMissing so that re-seeding never overwrites
 * what somebody typed in the Translation Manager. The cost is that a string
 * reworded in a language FILE never reaches a site that has already been
 * seeded — DbLanguage prefers the table, so the old wording is served forever.
 *
 * That has now needed a one-off migration six separate times: the showroom
 * standfirst, the map legend, the impact pillars, a general sweep, the hero
 * calls to action, and the whole Site group after this codebase was cloned into
 * a hotel. Every one of them was the same bug, and the next edit to a language
 * file would be the seventh.
 *
 * The distinction the seeder actually needs is not "does a row exist" but "did
 * a person write this". With that recorded, the seeder can keep file-managed
 * rows in step with the files on every deploy and still never touch an edit.
 *
 * Existing rows default to 0 — file-managed. On this site that is exactly
 * right: the table holds imported copy, some of it from the previous brand.
 * Anyone who had edited a string before this migration would have that edit
 * replaced once, on the next deploy, by the file's wording.
 */
class MarkCustomTranslations extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        if (! in_array('is_custom', $this->db->getFieldNames('translations'), true)) {
            $this->forge->addColumn('translations', [
                'is_custom' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('translations')
            && in_array('is_custom', $this->db->getFieldNames('translations'), true)) {
            $this->forge->dropColumn('translations', 'is_custom');
        }
    }
}
