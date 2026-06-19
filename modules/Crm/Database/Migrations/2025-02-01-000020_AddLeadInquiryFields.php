<?php

namespace Modules\Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Showroom inquiry form captures phone + quantity requirement in addition to
 * the existing lead fields.
 */
class AddLeadInquiryFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('leads', [
            'phone'    => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true, 'after' => 'company'],
            'quantity' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'interest'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('leads', ['phone', 'quantity']);
    }
}
