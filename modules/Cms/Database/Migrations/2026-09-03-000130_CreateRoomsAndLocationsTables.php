<?php

namespace Modules\Cms\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rooms and tourist locations as records rather than page blocks.
 *
 * Both were blocks inside a page's structure, which made them editable but not
 * manageable: adding a third room meant adding a block to the right section of
 * the right page and typing its JSON, the same room could not appear on two
 * pages without being written twice, and there was nowhere to say a room is
 * temporarily unavailable short of deleting it.
 *
 * As tables they get a real screen each — create, edit, reorder, publish,
 * unpublish, delete — and the pages that show them read the list rather than
 * carrying it. The block types stay in place, so a page can still hold a
 * one-off card that is not a room.
 *
 * Locale-mapped text columns, like every other content table here, so a room's
 * name and description can be translated without a second row.
 */
class CreateRoomsAndLocationsTables extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('rooms')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'slug'        => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'name'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map'],
                'summary'     => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map — the short line on a card'],
                'description' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map — the full copy'],
                'image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'gallery'     => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON array of image paths'],
                'features'    => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON array of strings'],
                'max_guests'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'bed'         => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'size_sqm'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'price_from'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
                'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['status', 'sort_order']);
            $this->forge->createTable('rooms');
        }

        if (! $this->db->tableExists('locations')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'slug'        => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'name'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map'],
                'summary'     => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map'],
                'description' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map'],
                'image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'image_alt'   => ['type' => 'TEXT', 'null' => true, 'comment' => 'Locale map — describes the photograph'],
                'distance_km' => ['type' => 'DECIMAL', 'constraint' => '6,1', 'null' => true],
                'travel_time' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'url'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'Optional reference, e.g. a tourism board page'],
                'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['status', 'sort_order']);
            $this->forge->createTable('locations');
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('locations', true);
        $this->forge->dropTable('rooms', true);
    }
}
