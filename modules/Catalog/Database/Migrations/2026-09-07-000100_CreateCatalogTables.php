<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The catalogue: what is taught, by whom, where, and on which dates.
 *
 * The one decision the whole schema turns on is the **course / session split**.
 *
 *   A *course* is the evergreen thing — "Photoshop Level 1". It owns the URL,
 *   the outline, the outcomes, the reviews and the search ranking. It has no
 *   dates and no price.
 *
 *   A *session* is one deliverable instance of it — "Photoshop Level 1, live
 *   online, 14–15 October, 09:00–16:00 IST, 12 seats, LKR 45,000 / USD 495".
 *   Seats, prices, calendars, attendance, certificates and revenue all hang off
 *   sessions.
 *
 * Self-paced study is modelled as a session with no dates and no seat limit, so
 * one commerce and enrolment pipeline serves all four delivery modes rather
 * than the on-demand library growing a second, parallel one.
 *
 * Naming: the table is `course_sessions`, not `sessions`. CodeIgniter's own
 * database session handler owns that name, and a training company saying
 * "session" and a web framework saying "session" mean different things — a
 * collision here would be found at the worst possible moment.
 *
 * Convention, matching the rest of the platform: every reader-facing text
 * column is a JSON locale map ({"en":…,"si":…}) resolved at render time by
 * t_field(), so a course can go live in English while the Sinhala is still
 * being written. Anything typed for internal use — a person's name, a slug, a
 * timezone, a room's capacity — is a plain column, because translating it would
 * be nonsense.
 *
 * Money never appears here except as integer minor units alongside a currency:
 * see session_prices and bundle_prices. There are no floats in this schema.
 */
class CreateCatalogTables extends Migration
{
    public function up(): void
    {
        // ── Taxonomy ─────────────────────────────────────────────────────────
        // `course_categories`, not `categories`: the News module already owns
        // that name for article categories, and two different trees under one
        // name is a bug waiting for whoever writes the next join.
        //
        // parent_id makes the tree — Adobe Creative Cloud → Photoshop — which is
        // what produces the deep, keyword-rich URLs the whole SEO plan rests on.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'            => ['type' => 'TEXT', 'null' => true],
            'summary'         => ['type' => 'TEXT', 'null' => true],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'icon'            => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            // adobe | ai | design — which of the two headline pillars (plus the
            // supporting one) this branch belongs to. Denormalised on purpose:
            // the pillar pages and the home page ask this question constantly
            // and walking to the root of the tree for every card is wasteful.
            'pillar'          => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'adobe'],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'seo_title'       => ['type' => 'TEXT', 'null' => true],
            'seo_description' => ['type' => 'TEXT', 'null' => true],
            'seo_keywords'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'pillar', 'sort_order']);
        $this->forge->addKey('parent_id');
        $this->forge->createTable('course_categories', true);

        // ── Courses ──────────────────────────────────────────────────────────
        // The money page's whole content, minus its dates. Every field here is
        // one the course page renders and the catalogue filters on; a course
        // with any of them empty publishes as a thin page, which is why the
        // admin form marks them required rather than leaving it to discipline.
        $this->forge->addField([
            'id'                      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'category_id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'slug'                    => ['type' => 'VARCHAR', 'constraint' => 191],
            'title'                   => ['type' => 'TEXT', 'null' => true],
            'subtitle'                => ['type' => 'TEXT', 'null' => true],
            'summary'                 => ['type' => 'TEXT', 'null' => true],
            'description'             => ['type' => 'TEXT', 'null' => true],
            'level'                   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'duration_hours'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'duration_days'           => ['type' => 'DECIMAL', 'constraint' => '4,1', 'default' => 0],
            // "Creative Cloud 2026". Kills the single most common objection to
            // buying a software course — that it teaches last year's release —
            // and gives the annual content-refresh sprint something to filter
            // on rather than a memory of which pages were rewritten.
            'software_version'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            // Which Adobe Certified Professional exam this prepares for, if any.
            // Translatable because it is a sentence on the page, not a code.
            'certification_alignment' => ['type' => 'TEXT', 'null' => true],
            'hero_image'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pillar'                  => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'adobe'],
            'default_mode'            => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'LIVE_ONLINE'],
            // Which band of the price book this course sits in: 1day | 2day |
            // bootcamp | selfpaced. The actual numbers live per session and per
            // currency; this is what a new session's price defaults from.
            'price_band'              => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => '1day'],
            'is_featured'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            // Maintained from approved reviews only, by ReviewModel — never
            // seeded, never typed. A rating nobody left is a lie in a rich
            // snippet, and Google treats it as one.
            'rating_avg'              => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'rating_count'            => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'seo_title'               => ['type' => 'TEXT', 'null' => true],
            'seo_description'         => ['type' => 'TEXT', 'null' => true],
            'seo_keywords'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // Set the moment an editor saves the course in the admin. The
            // seeder checks it and refuses to overwrite, so a release can never
            // undo the content team's work.
            'is_custom'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'                  => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'draft'],
            'published_at'            => ['type' => 'DATETIME', 'null' => true],
            'created_by'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'pillar']);
        $this->forge->addKey(['category_id', 'status']);
        $this->forge->addKey(['status', 'is_featured']);
        $this->forge->createTable('courses', true);

        // ── The four ordered text lists on a course ──────────────────────────
        // Outcomes, prerequisites, audiences and includes are the same shape:
        // an ordered list of one translatable sentence each, hanging off a
        // course. Writing the identical column block out four times invites the
        // fifth one to drift, so it is declared once here.
        //
        // They are rows rather than a JSON array column because the admin edits
        // them one at a time and the course page renders them as separate
        // elements — and because "what does every Photoshop course promise?" is
        // a question worth being able to ask in SQL.
        foreach (['course_outcomes', 'course_prerequisites', 'course_audiences'] as $table) {
            $this->textListTable($table);
        }

        // `includes` carries an icon as well — "recordings", "certificate",
        // "free retake" each get their own mark on the page.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'text'       => ['type' => 'TEXT', 'null' => true],
            'icon'       => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['course_id', 'sort_order']);
        $this->forge->createTable('course_includes', true);

        // ── The curriculum accordion ─────────────────────────────────────────
        // Modules hold topics. The topic's duration_min is what lets the page
        // state a running total and lets an editor notice that a "two-day"
        // course has six hours of content in it.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'title'      => ['type' => 'TEXT', 'null' => true],
            'summary'    => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['course_id', 'sort_order']);
        $this->forge->createTable('course_modules', true);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'module_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'title'        => ['type' => 'TEXT', 'null' => true],
            'duration_min' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'sort_order'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['module_id', 'sort_order']);
        $this->forge->createTable('course_topics', true);

        // ── Which of the four modes this course is offered in ────────────────
        // A row per offered mode rather than a set column, because the sticky
        // booking panel switches between them and needs to know which tabs to
        // draw before it has looked for a single date.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'mode'       => ['type' => 'VARCHAR', 'constraint' => 24], // LIVE_ONLINE|CLASSROOM|SELF_PACED|PRIVATE
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['course_id', 'mode']);
        $this->forge->createTable('course_delivery_modes', true);

        // ── Per-course FAQs ──────────────────────────────────────────────────
        // Marked up as FAQPage on the course page, so these are answers to what
        // a buyer actually asks — do I need my own laptop, can my employer be
        // invoiced — and not filler. Filler in structured data is worse than no
        // structured data.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'question'   => ['type' => 'TEXT', 'null' => true],
            'answer'     => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['course_id', 'sort_order']);
        $this->forge->createTable('course_faqs', true);

        // ── Related courses ──────────────────────────────────────────────────
        // Curated rather than computed: "people also viewed" needs traffic this
        // site does not have yet, and an editor knows that Illustrator Level 1
        // follows Photoshop Level 1 without being told.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'related_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['course_id', 'related_id']);
        $this->forge->createTable('course_related', true);

        // ── Instructors ──────────────────────────────────────────────────────
        // A person's name is not translatable; their headline and biography are.
        // is_placeholder marks a faculty entry that stands in for a named
        // trainer who has not been signed off yet, so the site can ship without
        // inventing a person and the admin can list exactly what still needs a
        // real name and photograph.
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 128],
            'headline'         => ['type' => 'TEXT', 'null' => true],
            'bio'              => ['type' => 'TEXT', 'null' => true],
            'photo'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'credentials_json' => ['type' => 'TEXT', 'null' => true],
            'links_json'       => ['type' => 'TEXT', 'null' => true],
            'is_placeholder'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'sort_order']);
        $this->forge->createTable('instructors', true);

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'instructor_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'sort_order'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['course_id', 'instructor_id']);
        $this->forge->createTable('course_instructor', true);

        // ── Venues ───────────────────────────────────────────────────────────
        // Both the classrooms and the virtual "rooms" a live online class runs
        // in, because a session needs a place and a timezone whether or not
        // anybody travels to it. The city pages are built from these.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 191],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 128],
            'type'            => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'classroom'], // classroom|virtual
            'address'         => ['type' => 'TEXT', 'null' => true],
            'city'            => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'country'         => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'timezone'        => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Asia/Colombo'],
            'capacity'        => ['type' => 'INT', 'constraint' => 11, 'default' => 12],
            'map_url'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'directions'      => ['type' => 'TEXT', 'null' => true],
            // The local-SEO lander's own prose. Genuinely different per city —
            // the blueprint is explicit that templated city pages are spam.
            'body'            => ['type' => 'TEXT', 'null' => true],
            'heading'         => ['type' => 'TEXT', 'null' => true],
            'summary'         => ['type' => 'TEXT', 'null' => true],
            'seo_title'       => ['type' => 'TEXT', 'null' => true],
            'seo_description' => ['type' => 'TEXT', 'null' => true],
            'seo_keywords'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'published'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'type']);
        $this->forge->createTable('venues', true);

        // ── Sessions: one deliverable instance of a course ────────────────────
        // seats_total / seats_sold / seats_reserved are the inventory. Only
        // InventoryService may write them, and only inside a transaction: two
        // buyers taking the last seat of a classroom is the failure that ends a
        // training business's reputation, and it is entirely a locking problem.
        //
        // seats_reserved is a cache of the unexpired rows in seat_holds, kept
        // in step inside the same transaction so a listing can show "2 seats
        // left" without a subquery. `spark seats:reconcile` recomputes it if it
        // ever drifts; seat_holds, not this column, is the truth.
        //
        // min_to_run is what makes a schedule honest. A class below it is not
        // confirmed, and the site says so rather than taking money for a date
        // that will be cancelled.
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'mode'             => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'LIVE_ONLINE'],
            'venue_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'instructor_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'language'         => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'en'],
            // Null for self-paced: the on-demand library is a session with no
            // dates and no seat limit, so it rides the same pipeline.
            'start_date'       => ['type' => 'DATE', 'null' => true],
            'end_date'         => ['type' => 'DATE', 'null' => true],
            'timezone'         => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Asia/Colombo'],
            'daily_start'      => ['type' => 'TIME', 'null' => true],
            'daily_end'        => ['type' => 'TIME', 'null' => true],
            // 0 means unlimited — self-paced and, occasionally, a webinar.
            'seats_total'      => ['type' => 'INT', 'constraint' => 11, 'default' => 12],
            'seats_reserved'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'seats_sold'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'min_to_run'       => ['type' => 'INT', 'constraint' => 11, 'default' => 3],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'open'],
            'cancel_reason'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meeting_provider' => ['type' => 'VARCHAR', 'constraint' => 24, 'null' => true], // zoom|teams|other
            // Encrypted at rest: a joining link is a key to a paid class, and
            // an unencrypted one in a database dump lets anybody walk in.
            // Written and read through Core's SecretBox.
            'meeting_url_enc'  => ['type' => 'TEXT', 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'is_private'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['course_id', 'status', 'start_date']);
        $this->forge->addKey(['status', 'start_date']);
        $this->forge->addKey(['mode', 'status', 'start_date']);
        $this->forge->createTable('course_sessions', true);

        // Each day of a multi-day class, so the calendar file, the attendance
        // register and "day two starts an hour later" all have somewhere to
        // live. A one-day class still gets a row.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'day_date'        => ['type' => 'DATE', 'null' => true],
            'start_time'      => ['type' => 'TIME', 'null' => true],
            'end_time'        => ['type' => 'TIME', 'null' => true],
            'meeting_url_enc' => ['type' => 'TEXT', 'null' => true],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['session_id', 'day_date']);
        $this->forge->createTable('session_days', true);

        // ── Prices ───────────────────────────────────────────────────────────
        // One row per currency per session. Published, never converted at
        // runtime: an FX-converted price produces LKR 47,382 on the page and
        // destroys margin control the day the rate moves. compare_at_cents is
        // the struck-through number, and is null unless there genuinely was a
        // higher price.
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'currency'          => ['type' => 'CHAR', 'constraint' => 3],
            'price_cents'       => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'compare_at_cents'  => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['session_id', 'currency']);
        $this->forge->createTable('session_prices', true);

        // ── Seat holds ───────────────────────────────────────────────────────
        // A cart holds seats for fifteen minutes so that filling in four
        // attendees' details cannot lose you the class. Expiry is enforced by
        // reading `expires_at > now` everywhere availability is computed, not
        // by trusting the sweeper to have run: a queue that is late must not be
        // able to oversell.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'cart_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'qty'        => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['session_id', 'cart_id']);
        $this->forge->addKey(['session_id', 'expires_at']);
        $this->forge->createTable('seat_holds', true);

        // ── Waitlist ─────────────────────────────────────────────────────────
        // A full class is a lead, not a dead end, and a waitlist is what turns
        // a marginal class into a confirmed one.
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'course_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'user_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'note'        => ['type' => 'TEXT', 'null' => true],
            'notified_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['session_id', 'notified_at']);
        $this->forge->addKey(['course_id', 'notified_at']);
        $this->forge->createTable('waitlist', true);

        // ── Bundles: bootcamps and certificate programmes ────────────────────
        // The single biggest lever on average order value, and the reason the
        // reference site sells an intro and an advanced class together at a
        // discount rather than hoping the buyer comes back.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 191],
            'type'            => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'certificate'], // bootcamp|certificate
            'title'           => ['type' => 'TEXT', 'null' => true],
            'subtitle'        => ['type' => 'TEXT', 'null' => true],
            'summary'         => ['type' => 'TEXT', 'null' => true],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'seo_title'       => ['type' => 'TEXT', 'null' => true],
            'seo_description' => ['type' => 'TEXT', 'null' => true],
            'seo_keywords'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_custom'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'draft'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'type', 'sort_order']);
        $this->forge->createTable('bundles', true);

        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'bundle_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'course_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['bundle_id', 'course_id']);
        $this->forge->createTable('bundle_items', true);

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'bundle_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'currency'         => ['type' => 'CHAR', 'constraint' => 3],
            'price_cents'      => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'compare_at_cents' => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['bundle_id', 'currency']);
        $this->forge->createTable('bundle_prices', true);

        // ── Reviews ──────────────────────────────────────────────────────────
        // enrolment_id is not optional in practice: a review is written by
        // somebody who took the class, and that link is what stops the reviews
        // page becoming a form anybody on the internet can fill in. It is
        // nullable only so that a review imported from elsewhere later has
        // somewhere to go, and such a row is marked by its source.
        //
        // Nothing is ever seeded into this table. A launch with no reviews
        // shows an empty state; invented testimonials on a commercial site are
        // a lie a customer can act on.
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'user_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'enrolment_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'author_name'  => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'author_role'  => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'rating'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 5],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'body'         => ['type' => 'TEXT', 'null' => true],
            'source'       => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'site'],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['course_id', 'status']);
        $this->forge->createTable('course_reviews', true);
    }

    public function down(): void
    {
        // Children before parents, so a foreign key added later cannot make the
        // rollback fail half way and leave the schema in a state no migration
        // describes.
        foreach ([
            'course_reviews', 'bundle_prices', 'bundle_items', 'bundles',
            'waitlist', 'seat_holds', 'session_prices', 'session_days',
            'course_sessions', 'venues', 'course_instructor', 'instructors',
            'course_related', 'course_faqs', 'course_delivery_modes',
            'course_topics', 'course_modules', 'course_includes',
            'course_audiences', 'course_prerequisites', 'course_outcomes',
            'courses', 'course_categories',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    /**
     * An ordered list of one translatable sentence per row, hanging off a
     * course. Three tables share this exact shape; declaring it once means the
     * fourth cannot quietly differ.
     */
    private function textListTable(string $table): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'text'       => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['course_id', 'sort_order']);
        $this->forge->createTable($table, true);
    }
}
