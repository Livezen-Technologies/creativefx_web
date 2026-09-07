<?php

namespace Modules\Commerce\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Selling: currencies and price books, the cart, orders, payments, invoices,
 * coupons, and the corporate side — accounts, leads and quotes.
 *
 * Three rules run through every table here, and each of them exists because
 * the alternative has taken a real training business down:
 *
 *   1. **Money is an integer of minor units, plus a currency code.** Never a
 *      float, never a decimal string, never at any layer. `price_cents` and
 *      `currency` travel together; a figure without its currency is not a
 *      price, and this site publishes two of them.
 *
 *   2. **Prices are published per currency, never converted at runtime.** The
 *      LKR price is a number somebody chose; it is not the USD price through
 *      today's rate. Runtime conversion produces LKR 47,382 on the page and
 *      moves the margin every morning. `price_books` maps a country to a
 *      currency — it does not map one price to another.
 *
 *   3. **Payment is webhook-authoritative.** The browser coming back from a
 *      gateway proves nothing: it can be replayed, forged, or simply never
 *      arrive because the buyer closed the tab on a train. An enrolment is
 *      created by a verified webhook, or by a human in the admin marking a bank
 *      transfer received. That is why `payments` is a table of received facts
 *      with the raw payload kept, and not a status column on the order.
 *
 * Idempotency is explicit rather than hoped for: `orders.idempotency_key` and
 * the unique `(gateway, gateway_ref)` on payments mean a retried request and a
 * redelivered webhook both land exactly once.
 */
class CreateCommerceTables extends Migration
{
    public function up(): void
    {
        // ── Currencies ───────────────────────────────────────────────────────
        // `decimals` is what stops "cents" being a lie: USD has two, LKR has
        // two, but JPY has none, and a site that hard-codes ÷100 cannot ever
        // add one. `minor_step` is the rounding granularity used when a
        // discount produces an ugly number — LKR prices are set to the nearest
        // 100 rupees, not the nearest cent.
        $this->forge->addField([
            'code'       => ['type' => 'CHAR', 'constraint' => 3],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 48],
            'symbol'     => ['type' => 'VARCHAR', 'constraint' => 8],
            'decimals'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 2],
            'minor_step' => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('code', true);
        $this->forge->createTable('currencies', true);

        // ── Price books ──────────────────────────────────────────────────────
        // A country resolves to a book, a book names a currency. Sri Lanka gets
        // the LKR book; everywhere else falls through to the default USD one.
        // Adding a GBP or AED book later is a row, not a deployment.
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code'               => ['type' => 'VARCHAR', 'constraint' => 32],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 64],
            'currency'           => ['type' => 'CHAR', 'constraint' => 3],
            'country_codes_json' => ['type' => 'TEXT', 'null' => true],
            'is_default'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('price_books', true);

        // ── Cart ─────────────────────────────────────────────────────────────
        // Guest-first: a cart is addressed by an unguessable token in a cookie
        // and only later attached to a user. Requiring an account before the
        // buyer knows the total is the most reliable way to lose the sale.
        //
        // currency and country are frozen onto the cart at creation. Resolving
        // them per request would let a visitor's price change between the
        // course page and the checkout because a CDN header wobbled.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'token'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'user_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'currency'   => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'USD'],
            'country'    => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'coupon_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('user_id');
        $this->forge->createTable('carts', true);

        // unit_price_cents is copied in at the moment the item is added, not
        // read through to the session at checkout: a price the site quoted is a
        // price the site honours for the life of the cart, and an admin editing
        // a price must not silently change what somebody is half way through
        // buying.
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'cart_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'item_type'        => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'session'], // session|bundle
            'item_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'qty'              => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'unit_price_cents' => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'discount_cents'   => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'meta_json'        => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['cart_id', 'item_type', 'item_id']);
        $this->forge->createTable('cart_items', true);

        // ── Coupons and rules ────────────────────────────────────────────────
        // A coupon is a code somebody types. A discount rule is something the
        // site works out for itself — early bird, three or more seats, alumni,
        // a corporate account's negotiated rate. They are separate tables
        // because they are administered by different people for different
        // reasons, and because a rule has to be able to apply without anybody
        // knowing it exists.
        //
        // A per-currency value needs a currency: "500 off" is generous in USD
        // and meaningless in LKR. A percentage coupon leaves it null.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code'            => ['type' => 'VARCHAR', 'constraint' => 48],
            'type'            => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'percent'], // percent|fixed
            'value'           => ['type' => 'INT', 'constraint' => 11, 'default' => 0],             // percent points, or minor units
            'currency'        => ['type' => 'CHAR', 'constraint' => 3, 'null' => true],
            'min_spend_cents' => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'max_uses'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'used_count'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'per_user_limit'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'applies_to_json' => ['type' => 'TEXT', 'null' => true],
            'starts_at'       => ['type' => 'DATETIME', 'null' => true],
            'ends_at'         => ['type' => 'DATETIME', 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('coupons', true);

        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 128],
            'kind'            => ['type' => 'VARCHAR', 'constraint' => 24], // early_bird|group|bundle|alumni|account
            'conditions_json' => ['type' => 'TEXT', 'null' => true],
            'value_json'      => ['type' => 'TEXT', 'null' => true],
            'priority'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'priority']);
        $this->forge->createTable('discount_rules', true);

        // Tax is stored per order item at the rate that applied on the day,
        // never derived at display time: an invoice reprinted next year must
        // show what was charged, not what would be charged now.
        // rate_bp is basis points — 1500 is 15% — so VAT arithmetic stays in
        // integers all the way to the total.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'country'         => ['type' => 'CHAR', 'constraint' => 2],
            'region'          => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'rate_bp'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'label'           => ['type' => 'VARCHAR', 'constraint' => 48],
            'applies_to_json' => ['type' => 'TEXT', 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['country', 'is_active']);
        $this->forge->createTable('tax_rules', true);

        // ── Corporate accounts ───────────────────────────────────────────────
        // The B2B side, and where the repeat revenue is. An account can carry
        // its own rate card, book seats for people who are not the buyer, and
        // see a completion dashboard for its own staff.
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 191],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 191],
            'type'           => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'corporate'], // corporate|partner
            'billing_json'   => ['type' => 'TEXT', 'null' => true],
            'rate_card_json' => ['type' => 'TEXT', 'null' => true],
            'currency'       => ['type' => 'CHAR', 'constraint' => 3, 'null' => true],
            'owner_user_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'active'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('corporate_accounts', true);

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'account_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'user_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'role'       => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'member'], // manager|member
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['account_id', 'user_id']);
        $this->forge->createTable('account_users', true);

        // ── Orders ───────────────────────────────────────────────────────────
        // status: pending_payment | paid | partially_refunded | refunded |
        //         cancelled | failed
        //
        // idempotency_key is unique and supplied by the checkout, so a double
        // submit — the impatient second click, the mobile browser retrying a
        // POST it thinks timed out — creates one order rather than two, with no
        // deduplication logic to get wrong later.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_no'        => ['type' => 'VARCHAR', 'constraint' => 32],
            'user_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'account_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'pending_payment'],
            'currency'        => ['type' => 'CHAR', 'constraint' => 3],
            'country'         => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'subtotal_cents'  => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'discount_cents'  => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'tax_cents'       => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'total_cents'     => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'coupon_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'billing_json'    => ['type' => 'TEXT', 'null' => true],
            'gateway'         => ['type' => 'VARCHAR', 'constraint' => 24, 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'due_at'          => ['type' => 'DATETIME', 'null' => true],
            'placed_at'       => ['type' => 'DATETIME', 'null' => true],
            'paid_at'         => ['type' => 'DATETIME', 'null' => true],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('order_no');
        $this->forge->addUniqueKey('idempotency_key');
        $this->forge->addKey(['status', 'placed_at']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('orders', true);

        // title_snapshot and meta_snapshot_json freeze what was bought. A course
        // renamed, rescheduled or withdrawn two years later must not rewrite the
        // history of somebody's receipt.
        //
        // attendee_json is the reason this site can sell to companies at all:
        // the buyer is routinely not the learner, and four seats on one order
        // are four different people with four different email addresses.
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'item_type'          => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'session'],
            'item_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'course_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'session_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'bundle_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'qty'                => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'title_snapshot'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_snapshot_json' => ['type' => 'TEXT', 'null' => true],
            'unit_price_cents'   => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'discount_cents'     => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'tax_cents'          => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'total_cents'        => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'attendee_json'      => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addKey(['session_id']);
        $this->forge->createTable('order_items', true);

        // ── Payments ─────────────────────────────────────────────────────────
        // A log of what the gateway told us, with the payload kept verbatim so
        // a dispute can be answered from the record rather than from memory.
        // The unique (gateway, gateway_ref) is the whole idempotency story for
        // webhooks: gateways redeliver, sometimes for days, and the second
        // delivery must be a no-op rather than a second enrolment.
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'gateway'          => ['type' => 'VARCHAR', 'constraint' => 24],
            'gateway_ref'      => ['type' => 'VARCHAR', 'constraint' => 191],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'pending'],
            'amount_cents'     => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'currency'         => ['type' => 'CHAR', 'constraint' => 3],
            'raw_payload_json' => ['type' => 'TEXT', 'null' => true],
            'received_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['gateway', 'gateway_ref']);
        $this->forge->addKey('order_id');
        $this->forge->createTable('payments', true);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'payment_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'order_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'amount_cents' => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'currency'     => ['type' => 'CHAR', 'constraint' => 3],
            'reason'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'pending'],
            'gateway_ref'  => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_id');
        $this->forge->createTable('refunds', true);

        // ── Invoices ─────────────────────────────────────────────────────────
        // Its own number series, separate from the order number, because
        // accounting needs a gapless sequence and orders can be abandoned.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'number'     => ['type' => 'VARCHAR', 'constraint' => 32],
            'issued_at'  => ['type' => 'DATETIME', 'null' => true],
            'due_at'     => ['type' => 'DATETIME', 'null' => true],
            'pdf_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'issued'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('number');
        $this->forge->addKey('order_id');
        $this->forge->createTable('invoices', true);

        // ── Leads ────────────────────────────────────────────────────────────
        // Its own table rather than the platform's generic contact messages: a
        // corporate enquiry carries team size, courses of interest, preferred
        // dates and a budget band, and it is worked by a person against an SLA.
        // Squeezing that into a `message` column loses the fields the sales
        // process is built on.
        //
        // utm_json is captured at submission. Cost per enrolment by channel is
        // one of the launch metrics, and attribution that is not recorded at
        // the moment of the enquiry cannot be reconstructed afterwards.
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'type'            => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'corporate'], // corporate|contact|newsletter|resource|waitlist|date_request
            'name'            => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'company'         => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'country'         => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'team_size'       => ['type' => 'VARCHAR', 'constraint' => 24, 'null' => true],
            'courses_json'    => ['type' => 'TEXT', 'null' => true],
            'preferred_dates' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'mode'            => ['type' => 'VARCHAR', 'constraint' => 24, 'null' => true],
            'location'        => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'budget_band'     => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'message'         => ['type' => 'TEXT', 'null' => true],
            'payload_json'    => ['type' => 'TEXT', 'null' => true],
            'source'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'utm_json'        => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'new'],
            'assigned_to'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['type', 'status', 'created_at']);
        $this->forge->addKey('email');
        $this->forge->createTable('training_leads', true);

        // ── Quotes ───────────────────────────────────────────────────────────
        // A corporate deal is not a checkout: it is a quote, then an approval,
        // then an invoice, and only then a private session with the attendees
        // bulk-loaded. The quote's line items come from the catalogue at the
        // account's rate, so a negotiated price is applied rather than typed.
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'lead_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'account_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'number'         => ['type' => 'VARCHAR', 'constraint' => 32],
            'currency'       => ['type' => 'CHAR', 'constraint' => 3],
            'subtotal_cents' => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'discount_cents' => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'tax_cents'      => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'total_cents'    => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'valid_until'    => ['type' => 'DATE', 'null' => true],
            'terms'          => ['type' => 'TEXT', 'null' => true],
            'pdf_path'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'draft'], // draft|sent|accepted|declined|expired
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('number');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->createTable('quotes', true);

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'quote_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'course_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'description'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'qty'              => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'unit_price_cents' => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'total_cents'      => ['type' => 'BIGINT', 'constraint' => 20, 'default' => 0],
            'sort_order'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('quote_id');
        $this->forge->createTable('quote_items', true);
    }

    public function down(): void
    {
        foreach ([
            'quote_items', 'quotes', 'training_leads', 'invoices', 'refunds',
            'payments', 'order_items', 'orders', 'account_users',
            'corporate_accounts', 'tax_rules', 'discount_rules', 'coupons',
            'cart_items', 'carts', 'price_books', 'currencies',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
