<?php

namespace Modules\Portfolio\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Portfolio projects: one row per piece of client work shown on
 * /{locale}/portfolio and /{locale}/portfolio/{slug}.
 *
 * The case-study fields (challenge, approach, results) are all optional — a
 * project can be a single cover image and a title — so the detail view omits
 * whatever an editor leaves empty. Deleting a category keeps its projects and
 * simply unfiles them (ON DELETE SET NULL).
 */
class CreatePortfolioProjectsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'       => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'client'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'industry'    => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'service'     => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'project_date' => ['type' => 'DATE', 'null' => true],
            'year'        => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
            'excerpt'     => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'description' => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'challenge'   => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'approach'    => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'results'     => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'cover_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'video_url'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // local file or embed URL
            'gallery'     => ['type' => 'TEXT', 'null' => true],   // JSON list of {src, caption locale-map}
            'featured'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'meta_title'       => ['type' => 'TEXT', 'null' => true], // JSON locale-map
            'meta_description' => ['type' => 'TEXT', 'null' => true], // JSON locale-map
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('category_id', 'portfolio_categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('portfolio_projects', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('portfolio_projects', true);
    }
}
