<?php

namespace Modules\Gear\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gear rental items: one row per piece of kit that goes out on hire.
 *
 * Rates are nullable because some kit is only ever quoted on request, and both
 * are DECIMAL rather than INT so a rate can carry cents if a client ever wants
 * them — the grid formats whole rupees when there is no fractional part.
 * `availability` is the day-to-day switch a coordinator flips (available |
 * booked | maintenance); `status` is the editorial one that decides whether the
 * item is on the site at all. Deleting a category keeps its items and simply
 * unfiles them (ON DELETE SET NULL), so no kit ever disappears with a tidy-up.
 */
class CreateGearItemsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'category_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'         => ['type' => 'TEXT', 'null' => true],   // JSON locale-map
            'summary'      => ['type' => 'TEXT', 'null' => true],   // JSON locale-map — one line on the card
            'specs'        => ['type' => 'TEXT', 'null' => true],   // JSON list of {label: locale-map, value: string}
            'image'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // site-root-absolute path
            'rate_daily'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'rate_weekly'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'currency'     => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'LKR'],
            'availability' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'available'], // available|booked|maintenance
            'featured'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('category_id', 'gear_categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('gear_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('gear_items', true);
    }
}
