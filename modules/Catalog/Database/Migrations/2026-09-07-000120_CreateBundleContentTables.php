<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Programme-level content for a bundle.
 *
 * `data/bundles/*.json` has carried `outcomes`, `includes` and `faqs` since the
 * catalogue was written, and the seeder read seven of the file's fourteen keys
 * — so a certificate programme, the most expensive thing on sale, had a page
 * with less on it than any single course inside it. The content existed; there
 * was simply nowhere to put it.
 *
 * These are deliberately NOT the union of the member courses' own lists. A
 * programme promises something the courses do not promise individually — a
 * portfolio at the end, a sequence that builds, a certificate covering the
 * whole track — and its FAQs answer questions only a programme raises ("can I
 * take them out of order", "what if I miss a course"). Concatenating the
 * courses' lists would produce forty outcomes nobody wrote and answer none of
 * those questions.
 *
 * `Bundles::detail()` does fall back to that concatenation, but only for a
 * programme whose own lists are empty — a stopgap for a bundle somebody has
 * created in the admin and not yet written up, not the intended content.
 *
 * The shapes mirror `course_outcomes` and `course_faqs` exactly, so anything
 * that already renders one renders the other.
 */
class CreateBundleContentTables extends Migration
{
    public function up(): void
    {
        // What you can do at the end of the programme.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'bundle_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'text'       => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['bundle_id', 'sort_order']);
        $this->forge->createTable('bundle_outcomes', true);

        // What the price covers. `icon` matches `course_includes`, so the same
        // partial can mark up both.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'bundle_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'text'       => ['type' => 'TEXT', 'null' => true],
            'icon'       => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['bundle_id', 'sort_order']);
        $this->forge->createTable('bundle_includes', true);

        // Marked up as FAQPage on the programme page, so — as with the course
        // FAQs — these are answers to what a buyer actually asks. Filler in
        // structured data is worse than no structured data.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'bundle_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'question'   => ['type' => 'TEXT', 'null' => true],
            'answer'     => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['bundle_id', 'sort_order']);
        $this->forge->createTable('bundle_faqs', true);
    }

    public function down(): void
    {
        foreach (['bundle_faqs', 'bundle_includes', 'bundle_outcomes'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
