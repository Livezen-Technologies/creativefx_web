<?php

namespace Modules\Tshda\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The tables the Authority's own subject matter needs, which the platform did
 * not already have: notices, the service catalogue, the document repository,
 * the office and staff directory, published statistics, Hantana's programmes
 * and their bookings, the society register, the moderated discussion, alert
 * subscriptions, public feedback and field-officer submissions.
 *
 * Convention, matching the rest of the platform: every reader-facing text
 * column is a JSON locale-map ({"en":…,"si":…,"ta":…}) resolved at render time
 * by t_field(), so a record can go live in English while Tamil is still being
 * translated without the page breaking. Anything an officer types for internal
 * use — a person's name, a reference number, an email address — is a plain
 * column, because translating it would be nonsense.
 */
class CreateTshdaTables extends Migration
{
    public function up(): void
    {
        // ── B.IV Priority notices ────────────────────────────────────────────
        // Deliberately its own table rather than a news category: a notice is
        // published in seconds, carries a window rather than a date, and has to
        // be findable by "what is showing right now" without scanning articles.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'TEXT', 'null' => true],
            'body'       => ['type' => 'TEXT', 'null' => true],
            'severity'   => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'notice'], // notice|urgent
            'url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'starts_at'  => ['type' => 'DATETIME', 'null' => true],
            'ends_at'    => ['type' => 'DATETIME', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'starts_at', 'ends_at']);
        $this->forge->createTable('notices', true);

        // ── D / E Service catalogue ──────────────────────────────────────────
        // Clause 3.9 D asks for eligibility, process, forms, responsible
        // division and contact point against every service, so each is a
        // column rather than prose an editor has to remember to include.
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'          => ['type' => 'VARCHAR', 'constraint' => 191],
            'area'          => ['type' => 'VARCHAR', 'constraint' => 48, 'default' => 'general'], // land|societies|training|welfare|general
            'audience'      => ['type' => 'VARCHAR', 'constraint' => 48, 'default' => 'smallholder'], // smallholder|society|officer|supplier|jobseeker
            'title'         => ['type' => 'TEXT', 'null' => true],
            'summary'       => ['type' => 'TEXT', 'null' => true],
            'eligibility'   => ['type' => 'TEXT', 'null' => true],
            'process'       => ['type' => 'TEXT', 'null' => true],   // JSON locale-map of a numbered list
            'documents'     => ['type' => 'TEXT', 'null' => true],   // JSON locale-map of a list
            'fee'           => ['type' => 'TEXT', 'null' => true],
            'duration'      => ['type' => 'TEXT', 'null' => true],
            'division'      => ['type' => 'TEXT', 'null' => true],
            'contact_point' => ['type' => 'TEXT', 'null' => true],
            'form_url'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'window_open'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1], // application window currently open
            'icon'          => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'sort_order'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'area']);
        $this->forge->createTable('services', true);

        // ── H Downloads ──────────────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'       => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('document_categories', true);

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'category_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'          => ['type' => 'TEXT', 'null' => true],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'file_path'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_size'      => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'file_type'      => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'language'       => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'en'],
            // Clause 3.12 asks for full-text search *inside* PDFs. The text is
            // extracted once at upload and kept here, so search is a query
            // rather than a per-request read of every document on the server.
            'extracted_text' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'download_count' => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'published_at'   => ['type' => 'DATETIME', 'null' => true],
            'expires_at'     => ['type' => 'DATETIME', 'null' => true], // tender closing dates
            'status'         => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'category_id']);
        $this->forge->createTable('documents', true);

        // ── K FAQs ───────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'category'   => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'general'],
            'question'   => ['type' => 'TEXT', 'null' => true],
            'answer'     => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'category']);
        $this->forge->createTable('faqs', true);

        // ── E.d / J Offices and staff ────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'       => ['type' => 'TEXT', 'null' => true],
            'kind'       => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'regional'], // head|regional|sub|centre
            'district'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'province'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'address'    => ['type' => 'TEXT', 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'fax'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'latitude'   => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'  => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'kind']);
        $this->forge->createTable('offices', true);

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'office_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            // A person's name is not translated. Their designation is.
            'name'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'designation' => ['type' => 'TEXT', 'null' => true],
            'division'   => ['type' => 'TEXT', 'null' => true],
            'subject_area' => ['type' => 'TEXT', 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'mobile'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'photo'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_senior'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'office_id']);
        $this->forge->addKey('is_senior');
        $this->forge->createTable('staff', true);

        // ── F Statistics ─────────────────────────────────────────────────────
        // The figures live as JSON rather than one row per cell: a published
        // statistical table is replaced wholesale when the next year's data
        // arrives, never edited cell by cell, and this keeps a dataset one
        // record that can be exported, versioned and archived as a unit.
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'       => ['type' => 'TEXT', 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'unit'        => ['type' => 'TEXT', 'null' => true],
            'chart'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'bar'], // bar|line|area|table
            'columns'     => ['type' => 'TEXT', 'null' => true], // JSON list of locale-maps
            'rows'        => ['type' => 'MEDIUMTEXT', 'null' => true], // JSON list of lists
            'source'      => ['type' => 'TEXT', 'null' => true],
            'period'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('statistics_datasets', true);

        // ── 3.6 Hantana National Training Centre ─────────────────────────────
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'       => ['type' => 'TEXT', 'null' => true],
            'summary'     => ['type' => 'TEXT', 'null' => true],
            'description' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'audience'    => ['type' => 'TEXT', 'null' => true],
            'starts_on'   => ['type' => 'DATE', 'null' => true],
            'ends_on'     => ['type' => 'DATE', 'null' => true],
            'closes_on'   => ['type' => 'DATE', 'null' => true], // applications close
            'capacity'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            // Confirmed bookings only. Kept denormalised so the capacity check
            // that closes a programme is one read, not a count over a table
            // that the public form hits on every submission.
            'booked'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'residential' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fee'         => ['type' => 'TEXT', 'null' => true],
            'venue'       => ['type' => 'TEXT', 'null' => true],
            'image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'starts_on']);
        $this->forge->createTable('programmes', true);

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'programme_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'reference'     => ['type' => 'VARCHAR', 'constraint' => 32],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 191],
            'nic'           => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'phone'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'address'       => ['type' => 'TEXT', 'null' => true],
            'district'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'society'       => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'participants'  => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'residential'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'locale'        => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'en'],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'], // pending|confirmed|waitlisted|declined|cancelled
            'handled_by'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'handled_at'    => ['type' => 'DATETIME', 'null' => true],
            'officer_note'  => ['type' => 'TEXT', 'null' => true],
            'ip_hash'       => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['programme_id', 'status']);
        $this->forge->createTable('programme_bookings', true);

        // ── E.b Society register ─────────────────────────────────────────────
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'registration' => ['type' => 'VARCHAR', 'constraint' => 48],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'tsds'], // tsds|coop|teashakthi
            'district'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'division'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'office_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'members'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'secretary'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'registered_on' => ['type' => 'DATE', 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('registration');
        $this->forge->addKey(['status', 'district']);
        $this->forge->createTable('societies', true);

        // ── B.V / 3.14 Moderated discussion ──────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'      => ['type' => 'TEXT', 'null' => true],
            'body'       => ['type' => 'TEXT', 'null' => true],
            'opens_at'   => ['type' => 'DATETIME', 'null' => true],
            'closes_at'  => ['type' => 'DATETIME', 'null' => true],
            'is_current' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('discussion_topics', true);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'topic_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'author'       => ['type' => 'VARCHAR', 'constraint' => 128],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'district'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'body'         => ['type' => 'TEXT', 'null' => true],
            'locale'       => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'en'],
            // Nothing a member of the public writes appears on the site until a
            // moderator releases it. Clause 3.14 asks for pre-publication
            // moderation, and 'pending' is the default for that reason.
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'], // pending|approved|rejected
            'moderated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'moderated_at' => ['type' => 'DATETIME', 'null' => true],
            'ip_hash'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['topic_id', 'status']);
        $this->forge->createTable('discussion_comments', true);

        // ── 3.13 Alert subscriptions ─────────────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 128],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'locale'        => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'en'],
            'topics'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // comma-separated keys
            // Double opt-in: a row is 'pending' until the address proves itself
            // by following the confirm link, so nobody can subscribe a stranger.
            'status'        => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'], // pending|active|unsubscribed|bounced
            'token'         => ['type' => 'VARCHAR', 'constraint' => 64],
            'confirmed_at'  => ['type' => 'DATETIME', 'null' => true],
            'unsubscribed_at' => ['type' => 'DATETIME', 'null' => true],
            'last_sent_at'  => ['type' => 'DATETIME', 'null' => true],
            'bounce_count'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('status');
        $this->forge->createTable('subscribers', true);

        // ── J.a.iii / 3.14 Public feedback and petitions ─────────────────────
        // Every submission gets a reference the citizen can quote and the CMT
        // can track, which is the whole point of the clause: "so nothing is
        // lost". open -> assigned -> answered -> closed, with a due date so the
        // overdue count on the dashboard is a fact rather than an impression.
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'reference'    => ['type' => 'VARCHAR', 'constraint' => 32],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'feedback'], // feedback|query|petition|complaint
            'name'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'district'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'subject'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'message'      => ['type' => 'TEXT', 'null' => true],
            'page_url'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attachment'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'locale'       => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'en'],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'open'],
            'assigned_to'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'division'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'due_at'       => ['type' => 'DATETIME', 'null' => true],
            'answered_at'  => ['type' => 'DATETIME', 'null' => true],
            'response'     => ['type' => 'TEXT', 'null' => true],
            'ip_hash'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['status', 'due_at']);
        $this->forge->createTable('feedback', true);

        // ── 3.5 Field Officer Portal submissions ─────────────────────────────
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'reference'    => ['type' => 'VARCHAR', 'constraint' => 32],
            'user_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'office_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 48, 'default' => 'field_report'],
            'subject'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'body'         => ['type' => 'TEXT', 'null' => true],
            'payload'      => ['type' => 'TEXT', 'null' => true], // JSON of the structured form
            // Uploads are stored outside the web root and served through the
            // controller after an authorisation check, per section 3.5.
            'attachment'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attachment_name' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'submitted'], // submitted|received|actioned|returned
            'reviewed_by'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'reviewed_at'  => ['type' => 'DATETIME', 'null' => true],
            'officer_note' => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->createTable('officer_submissions', true);

        // ── B.VII Links to related organisations ─────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'TEXT', 'null' => true],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'logo'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'group_key'  => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'related'], // related|government
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'group_key']);
        $this->forge->createTable('org_links', true);
    }

    public function down(): void
    {
        foreach ([
            'org_links', 'officer_submissions', 'feedback', 'subscribers',
            'discussion_comments', 'discussion_topics', 'societies',
            'programme_bookings', 'programmes', 'statistics_datasets',
            'staff', 'offices', 'faqs', 'documents', 'document_categories',
            'services', 'notices',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
