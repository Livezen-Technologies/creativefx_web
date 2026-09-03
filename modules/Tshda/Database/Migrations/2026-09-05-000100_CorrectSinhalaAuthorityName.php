<?php

namespace Modules\Tshda\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Correct the Sinhala word order in the Authority's name.
 *
 * It was seeded as "තේ කුඩා වතු" and the correct form is "කුඩා තේ වතු" — the
 * compound is *small tea holdings*, not *tea small holdings*, and it reads as a
 * mistranslation to anyone who reads Sinhala. The same compound appears inside
 * ordinary phrases too ("registered tea smallholdings by district"), so this
 * fixes the term wherever it occurs rather than only the organisation's name.
 *
 * Why a migration rather than a re-seed. Content rows are protected from being
 * overwritten by a release — that is deliberate, and it means a term seeded
 * wrongly stays wrong on every site already running. The language files and the
 * `translations` table do follow the files, so those correct themselves on
 * deploy; this exists for the rows that do not.
 *
 * It is safe to run against edited content: it only touches rows that contain
 * the wrong term, replaces exactly that term, and leaves the rest of the text
 * alone. An editor who has rewritten a sentence around the term still gets the
 * term corrected and keeps their sentence.
 */
class CorrectSinhalaAuthorityName extends Migration
{
    private const WRONG = 'තේ කුඩා වතු';
    private const RIGHT = 'කුඩා තේ වතු';

    /**
     * The tables whose reader-facing text can carry the term. Columns are read
     * from the schema rather than listed here, so a column added later is
     * covered and a column renamed does not turn this into a fatal error on a
     * site that is only trying to deploy.
     */
    private const TABLES = [
        'services', 'statistics_datasets', 'programmes', 'societies',
        'faqs', 'notices', 'documents', 'document_categories',
        'offices', 'staff', 'org_links', 'discussion_topics',
        'pages', 'page_blocks', 'news_posts', 'jobs',
    ];

    public function up(): void
    {
        $this->rewrite(self::WRONG, self::RIGHT);
    }

    /**
     * Reversible, because a migration that cannot be undone is a migration you
     * cannot roll a release back through.
     */
    public function down(): void
    {
        $this->rewrite(self::RIGHT, self::WRONG);
    }

    private function rewrite(string $from, string $to): void
    {
        foreach (self::TABLES as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            foreach ($this->textColumns($table) as $column) {
                // REPLACE() is in both SQLite and MySQL, so the whole column is
                // corrected in one statement — no reading rows into PHP, and no
                // chance of a half-finished pass leaving mixed spellings behind.
                $this->db->query(
                    'UPDATE ' . $this->db->protectIdentifiers($table, true)
                    . ' SET ' . $this->db->protectIdentifiers($column, false)
                    . ' = REPLACE(' . $this->db->protectIdentifiers($column, false) . ', ?, ?)'
                    . ' WHERE ' . $this->db->protectIdentifiers($column, false) . ' LIKE ?',
                    [$from, $to, '%' . $from . '%']
                );
            }
        }
    }

    /** @return list<string> */
    private function textColumns(string $table): array
    {
        $out = [];

        try {
            foreach ($this->db->getFieldData($table) as $field) {
                if (preg_match('/char|text|clob|json/i', (string) ($field->type ?? ''))) {
                    $out[] = $field->name;
                }
            }
        } catch (\Throwable $e) {
            // A table we cannot introspect is one we skip, not one that stops
            // the deploy. The term being wrong is a defect; a migration that
            // aborts a release over it is a worse one.
            log_message('error', 'Sinhala name migration: could not read ' . $table . ' — ' . $e->getMessage());
        }

        return $out;
    }
}
