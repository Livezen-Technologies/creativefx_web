<?php

namespace Modules\Video\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVideosTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'key'              => ['type' => 'VARCHAR', 'constraint' => 64],
            'title'            => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'poster_path'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'src_path'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'src_path_webm'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'hls_manifest'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'duration_seconds' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'is_muted_loop'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key');
        $this->forge->createTable('videos', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('videos', true);
    }
}
