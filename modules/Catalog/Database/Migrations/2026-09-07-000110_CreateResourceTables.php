<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The top of the funnel: free resources and webinars.
 *
 * Neither of these sells anything directly, which is exactly why they exist.
 * Somebody who is not ready to spend LKR 60,000 on a two-day class will
 * download a shortcut sheet, and the email address that comes with it is the
 * beginning of the relationship that eventually does. The blueprint budgets for
 * this as a first-class workstream rather than a launch afterthought, so it
 * gets tables rather than a page of links.
 *
 * `gated` is the only interesting column. An ungated resource is a page that
 * ranks; a gated one is a page that ranks *and* captures a lead. Which of the
 * two a given asset should be is a marketing decision that changes, so it is a
 * flag rather than two different features.
 */
class CreateResourceTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 191],
            'type'            => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'guide'], // cheatsheet|guide|checklist|template
            'title'           => ['type' => 'TEXT', 'null' => true],
            'summary'         => ['type' => 'TEXT', 'null' => true],
            'body'            => ['type' => 'TEXT', 'null' => true],
            // The file itself lives in the media library. Null is a supported
            // state: a resource can be published as a page while the PDF is
            // still with the designer, and the form then says so rather than
            // handing somebody a broken download.
            'file_path'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gated'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'download_count'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'seo_title'       => ['type' => 'TEXT', 'null' => true],
            'seo_description' => ['type' => 'TEXT', 'null' => true],
            'seo_keywords'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_custom'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'sort_order']);
        $this->forge->createTable('resources', true);

        // Webinars are deliberately not sessions. A session is a seat somebody
        // buys, with inventory and a price; a webinar is free, has no seat
        // limit worth enforcing, and is usually hosted somewhere else entirely.
        // Forcing them through the commerce pipeline would mean a zero-priced
        // order for every registration.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'           => ['type' => 'TEXT', 'null' => true],
            'summary'         => ['type' => 'TEXT', 'null' => true],
            'body'            => ['type' => 'TEXT', 'null' => true],
            'starts_at'       => ['type' => 'DATETIME', 'null' => true],
            'duration_min'    => ['type' => 'INT', 'constraint' => 11, 'default' => 60],
            'timezone'        => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Asia/Colombo'],
            'register_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // Set after the event. A past webinar with a recording is an asset
            // that keeps earning; one without is just an old date, and the
            // listing treats them differently.
            'recording_url'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'course_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'seo_title'       => ['type' => 'TEXT', 'null' => true],
            'seo_description' => ['type' => 'TEXT', 'null' => true],
            'seo_keywords'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'starts_at']);
        $this->forge->createTable('webinars', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('webinars', true);
        $this->forge->dropTable('resources', true);
    }
}
