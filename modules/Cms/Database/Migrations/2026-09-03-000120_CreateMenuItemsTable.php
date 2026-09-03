<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The header and footer navigation, as rows rather than a function.
 *
 * Both menus came from site_nav(), a PHP array. That was already better than
 * the two hard-coded copies it replaced — the footer used to render raw
 * language keys because its list had drifted from the header's — but it still
 * means adding a link is a deploy, and reordering one is a deploy, and hiding
 * one over a quiet season is a deploy.
 *
 * Shape worth explaining:
 *
 *   - `location` keeps both menus in one table. They are the same kind of
 *     thing, they are edited on the same screen, and a footer with its own
 *     table drifts from the header exactly the way the old copies did.
 *   - `parent_id` is what makes a dropdown. One level is deliberate: a second
 *     would need hover intent, keyboard traversal and a mobile pattern of its
 *     own, and a hotel with six pages does not have a use for it.
 *   - `url` is stored as written. A bare word is treated as a page slug and
 *     localized; anything with a scheme is left alone. That way an editor can
 *     type "contact" without knowing about locale prefixes, and can still
 *     link to a booking engine on another domain.
 */
class CreateMenuItemsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('menu_items')) {
            return;
        }

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'location'   => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'header', 'comment' => 'header | footer'],
            'parent_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'Set to nest this item in a dropdown'],
            'label'      => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map'],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'Page slug, or an absolute URL'],
            'target'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => '_self'],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published', 'comment' => 'published | draft'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['location', 'sort_order']);
        $this->forge->createTable('menu_items');
    }

    public function down(): void
    {
        $this->forge->dropTable('menu_items', true);
    }
}
