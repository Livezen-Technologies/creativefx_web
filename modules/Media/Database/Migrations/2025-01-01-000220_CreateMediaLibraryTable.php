<?php

namespace Modules\Media\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMediaLibraryTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'disk'        => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'local'],
            'path'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'url'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'mime_type'   => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true],
            'size_bytes'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'width'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'height'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'alt'         => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'folder'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'uploaded_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('folder');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('media_library', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('media_library', true);
    }
}
