<?php

namespace Modules\Auth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoleUserTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'role_user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'role_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'user_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
        ]);
        $this->forge->addKey('role_user_id', true);
        $this->forge->addUniqueKey(['role_id', 'user_id']);
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_user', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('role_user', true);
    }
}
