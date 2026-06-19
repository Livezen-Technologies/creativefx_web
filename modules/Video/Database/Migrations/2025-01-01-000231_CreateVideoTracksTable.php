<?php

namespace Modules\Video\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVideoTracksTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'video_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 5],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'audio_path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'kind'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'audio'],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['video_id', 'locale']);
        $this->forge->addForeignKey('video_id', 'videos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('video_tracks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('video_tracks', true);
    }
}
