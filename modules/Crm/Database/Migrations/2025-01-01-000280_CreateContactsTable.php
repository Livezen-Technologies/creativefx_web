<?php

namespace Modules\Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 128],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'message'    => ['type' => 'TEXT', 'null' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'source'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'new'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->createTable('contacts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('contacts', true);
    }
}
