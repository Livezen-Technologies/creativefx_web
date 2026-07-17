<?php

namespace Modules\News\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Blog & News (blueprint §4): categorised articles with a featured image,
 * tags, author, per-post SEO fields and scheduled publishing (published_at).
 */
class CreateNewsTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'        => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'description' => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('news_categories', true);

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'category_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'            => ['type' => 'TEXT', 'null' => true],       // JSON locale-map
            'excerpt'          => ['type' => 'TEXT', 'null' => true],       // JSON locale-map
            'body'             => ['type' => 'MEDIUMTEXT', 'null' => true], // JSON locale-map
            'image'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // featured image path
            'tags'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // comma-separated
            'author'           => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'meta_title'       => ['type' => 'TEXT', 'null' => true],       // JSON locale-map
            'meta_description' => ['type' => 'TEXT', 'null' => true],       // JSON locale-map
            'status'           => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'draft'],
            'published_at'     => ['type' => 'DATETIME', 'null' => true],   // future date = scheduled
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category_id');
        $this->forge->addKey(['status', 'published_at']);
        $this->forge->createTable('news_posts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('news_posts', true);
        $this->forge->dropTable('news_categories', true);
    }
}
