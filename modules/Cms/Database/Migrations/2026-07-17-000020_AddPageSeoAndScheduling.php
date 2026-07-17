<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Page-level SEO + scheduled publishing (CMS blueprint §2/§18): an Open Graph
 * image per page and a publish_at datetime — a published page stays hidden
 * until its scheduled time passes.
 */
class AddPageSeoAndScheduling extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('pages', [
            'og_image'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'meta_description'],
            'publish_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('pages', ['og_image', 'publish_at']);
    }
}
