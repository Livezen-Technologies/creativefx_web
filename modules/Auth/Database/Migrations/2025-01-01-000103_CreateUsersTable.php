<?php

namespace Modules\Auth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'last_name'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'active'],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
