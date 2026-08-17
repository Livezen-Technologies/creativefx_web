<?php

namespace Modules\Portfolio\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Portfolio categories: the discipline a piece of work is filed under
 * (photography, videography, podcast, live streaming …). They drive the filter
 * pills on /{locale}/portfolio, so the slug is part of a public URL.
 */
class CreatePortfolioCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'       => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('portfolio_categories', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('portfolio_categories', true);
    }
}
