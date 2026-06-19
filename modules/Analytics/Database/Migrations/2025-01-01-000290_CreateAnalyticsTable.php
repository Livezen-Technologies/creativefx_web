<?php

namespace Modules\Analytics\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnalyticsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'event'      => ['type' => 'VARCHAR', 'constraint' => 96],
            'path'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'session_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'user_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'referrer'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta'       => ['type' => 'TEXT', 'null' => true],   // JSON
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['event', 'created_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('analytics', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('analytics', true);
    }
}
