<?php

namespace Modules\Video\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVideoSubtitlesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'video_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 5],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'vtt_path'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['video_id', 'locale']);
        $this->forge->addForeignKey('video_id', 'videos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('video_subtitles', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('video_subtitles', true);
    }
}
