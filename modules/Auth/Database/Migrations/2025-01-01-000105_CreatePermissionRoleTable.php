<?php

namespace Modules\Auth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionRoleTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'permission_role_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'permission_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'role_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
        ]);
        $this->forge->addKey('permission_role_id', true);
        $this->forge->addUniqueKey(['permission_id', 'role_id']);
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('permission_role', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('permission_role', true);
    }
}
