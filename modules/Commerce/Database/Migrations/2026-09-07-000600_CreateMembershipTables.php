<?php

namespace Modules\Commerce\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Memberships: a pass to the self-paced library for a fixed term.
 *
 * Three tables and no fourth. What a membership *costs* lives in
 * `membership_plan_prices`, exactly as a session's price lives in
 * `session_prices` and a bundle's in `bundle_prices` — one row per currency,
 * each a number a human chose. There is no rate anywhere in this feature; see
 * PricingService's class comment for why converting at runtime is the thing
 * this site refuses to do.
 *
 * **`memberships` records the pass, not the access.** A membership does not
 * itself let anybody into a course: it causes an enrolment to be written the
 * first time a member opens one, and that enrolment is what the LMS reads.
 * Every downstream thing — lesson progress, quiz attempts, the certificate
 * rules, the account dashboard — is built on enrolments, and a second parallel
 * notion of access would mean teaching all of them about it. See
 * MembershipService for the whole of that argument.
 *
 * `expires_at` is stored, not derived from `starts_at` plus the plan's months.
 * The plan can be re-priced or its term changed, and neither may quietly move
 * the end date of a pass somebody has already paid for.
 */
class CreateMembershipTables extends Migration
{
    public function up(): void
    {
        // ── The plans on sale ───────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            // Short and stable: it appears in order line snapshots, which are a
            // permanent record and must not be re-keyed if a name changes.
            'code'       => ['type' => 'VARCHAR', 'constraint' => 32],
            'months'     => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            // Translatable, like every other name on this site.
            'name'       => ['type' => 'TEXT'],
            'summary'    => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('membership_plans', true);

        // ── What each costs, per currency ───────────────────────────────────
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'plan_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'currency'         => ['type' => 'VARCHAR', 'constraint' => 3],
            // Minor units, like every other money column here. There is exactly
            // one division by 100 on this site and it is in money().
            'price_cents'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'compare_at_cents' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // One price per plan per currency, enforced by the database rather than
        // by whoever writes the next seeder.
        $this->forge->addUniqueKey(['plan_id', 'currency']);
        $this->forge->createTable('membership_plan_prices', true);

        // ── The passes people have bought ───────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'plan_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            // Null for a pass an administrator granted by hand; the column is
            // how a pass is traced back to the money that paid for it.
            'order_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'starts_at'     => ['type' => 'DATETIME'],
            'expires_at'    => ['type' => 'DATETIME'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // The question asked on every gated page load: has this person got a
        // live pass? Indexed on the two columns that answer it.
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey('expires_at');
        // A gateway that delivers its webhook twice must not sell two passes.
        $this->forge->addUniqueKey('order_item_id');
        $this->forge->createTable('memberships', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('memberships', true);
        $this->forge->dropTable('membership_plan_prices', true);
        $this->forge->dropTable('membership_plans', true);
    }
}
