<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marks a page whose content an administrator has taken charge of.
 *
 * The content seeders delete every section and block belonging to their pages
 * and write them again, on every deploy. That is the right behaviour for a page
 * nobody has touched — it is how the site's copy is version-controlled — and it
 * is fatal for one that has been edited in the admin console, because the edit
 * survives exactly until the next release and then vanishes with no error and
 * no trace of what it was.
 *
 * It is the same problem the translations table had, and it takes the same
 * shape of answer: a row records whether it is still seed-managed, and the
 * seeder writes only what it still owns. Here the flag sits on the page rather
 * than on each section, because sections and blocks are deleted and recreated
 * wholesale — a flag on a row that is about to be dropped cannot protect
 * anything. One page is the smallest unit the seeder can actually skip.
 */
class AddPageCustomFlag extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('is_custom', 'pages')) {
            return;
        }

        $this->forge->addColumn('pages', [
            'is_custom' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'status',
                'comment'    => 'Content edited in the admin console; seeders leave this page alone.',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('is_custom', 'pages')) {
            $this->forge->dropColumn('pages', 'is_custom');
        }
    }
}
