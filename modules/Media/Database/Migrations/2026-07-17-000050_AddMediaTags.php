<?php

namespace Modules\Media\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Media library upgrades (blueprint §16): searchable tags per file.
 */
class AddMediaTags extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('media_library', [
            'tags' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'folder'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('media_library', 'tags');
    }
}
