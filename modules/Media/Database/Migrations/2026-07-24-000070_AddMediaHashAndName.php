<?php

namespace Modules\Media\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Media Manager support columns: a content hash for duplicate detection and
 * the original client file name for display/search.
 */
class AddMediaHashAndName extends Migration
{
    public function up(): void
    {
        $fields = $this->db->getFieldNames('media_library') ?: [];

        if (! in_array('hash', $fields, true)) {
            $this->forge->addColumn('media_library', [
                'hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'size_bytes'],
            ]);
        }
        if (! in_array('original_name', $fields, true)) {
            $this->forge->addColumn('media_library', [
                'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'path'],
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('media_library', 'hash');
        $this->forge->dropColumn('media_library', 'original_name');
    }
}
