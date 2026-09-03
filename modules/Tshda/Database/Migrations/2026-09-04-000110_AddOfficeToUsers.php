<?php

namespace Modules\Tshda\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Which office a member of staff belongs to.
 *
 * The Field Officer Portal has to answer "information relevant to the officer's
 * division and role" (section 3.5), and it cannot do that from a login alone.
 * Putting the office on the user record means an officer's submissions are
 * routed to their own regional office without them choosing it from a list —
 * and without the portal trusting a value the browser sent.
 */
class AddOfficeToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'office_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true,
                'null' => true, 'after' => 'last_name',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'office_id');
    }
}
