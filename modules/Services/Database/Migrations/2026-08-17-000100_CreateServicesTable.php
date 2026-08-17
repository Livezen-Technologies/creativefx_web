<?php

namespace Modules\Services\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Services registry — the single source of truth for what CreativeFX sells.
 *
 * One row per service. The registry carries only the short, repeated copy
 * (name, tagline, summary) plus the artwork and ordering that the header
 * dropdown, the footer, the Services overview grid and related-service links
 * all read. The long-form service page itself is an ordinary CMS page under
 * the slug 'services/<slug>', so editors write it in Admin → Pages.
 */
class CreateServicesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 191], // also the CMS page slug: services/<slug>
            'name'       => ['type' => 'TEXT', 'null' => true],         // JSON locale-map
            'tagline'    => ['type' => 'TEXT', 'null' => true],         // JSON locale-map — one line, nav + card
            'summary'    => ['type' => 'TEXT', 'null' => true],         // JSON locale-map — two sentences, card + overview
            'icon'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],  // key into the inline glyph set
            'card_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // site-root-absolute path
            'hero_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // site-root-absolute path
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->createTable('services', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('services', true);
    }
}
