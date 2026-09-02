<?php

namespace Modules\Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A room request is a contact message with dates attached.
 *
 * It could have been folded into the message body, and staff would have read it
 * perfectly well — but then the dates are prose, and nothing can sort by
 * arrival, count the party, or answer "who is arriving this weekend" without a
 * person reading every row. Columns cost one additive migration and keep the
 * request in the inbox staff already watch.
 *
 * Every column is nullable, so the existing contact form and every row already
 * in the table are untouched: a plain message simply leaves them empty.
 */
class AddBookingFieldsToContacts extends Migration
{
    private const COLUMNS = [
        'room_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
        'check_in'  => ['type' => 'DATE', 'null' => true],
        'check_out' => ['type' => 'DATE', 'null' => true],
        'adults'    => ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'null' => true],
        'children'  => ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'null' => true],
    ];

    public function up(): void
    {
        // Adding a column that is already there is an error, not a no-op, and
        // this migration has to be able to run against a database that a newer
        // deploy already touched.
        $existing = $this->db->getFieldNames('contacts');
        $missing  = array_diff_key(self::COLUMNS, array_flip($existing));

        if ($missing !== []) {
            $this->forge->addColumn('contacts', $missing);
        }
    }

    public function down(): void
    {
        $existing = array_intersect(array_keys(self::COLUMNS), $this->db->getFieldNames('contacts'));

        if ($existing !== []) {
            $this->forge->dropColumn('contacts', $existing);
        }
    }
}
