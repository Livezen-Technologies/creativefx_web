<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-administrator preferences.
 *
 * The dashboard layout is the first of these, and it has to be per person
 * rather than per site: the manager who wants traffic at the top and the person
 * who only ever looks at the enquiry inbox are both right, and a single shared
 * arrangement makes one of them wrong every time they sign in.
 *
 * Deliberately key/value rather than a column per preference. What an
 * administrator can arrange will grow, and a table that needs a migration each
 * time is one where the migration is skipped and the preference goes into a
 * settings row shared by everyone instead.
 */
class CreateUserPreferencesTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('user_preferences')) {
            return;
        }

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 64],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // One row per person per preference; the upsert relies on it.
        $this->forge->addUniqueKey(['user_id', 'key']);
        $this->forge->createTable('user_preferences');
    }

    public function down(): void
    {
        $this->forge->dropTable('user_preferences', true);
    }
}
