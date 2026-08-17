<?php

namespace Modules\Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The quote request form is the site's primary conversion path, and it asks a
 * lot more than the old showroom inquiry did: which of the six services, what
 * kind of project, when and where it shoots, the budget band, and an optional
 * brief or reference file.
 *
 * Those all land on `leads` next to the existing columns rather than in a
 * second table, so the team still works one inbox in Admin -> Quote Requests.
 *
 * `attachment` stores a WRITEPATH-relative path ('uploads/quotes/<random>.pdf').
 * The file itself is never written under public/ — admins download it through
 * the authenticated panel, exactly as CVs work in Careers.
 *
 * `locale` records the language the request came in on, so whoever replies
 * knows to write back in Sinhala or Tamil.
 *
 * Nothing here rewrites an existing row. The status vocabulary widens with this
 * release to new | contacted | qualified | proposal_sent | negotiation | won |
 * lost — every one of those fits the existing VARCHAR(16) `status` column, so
 * the column is left alone and leads already filed under the old wording
 * ('converted') keep it until someone re-files them by hand.
 */
class AddQuoteFieldsToLeads extends Migration
{
    public function up(): void
    {
        // Anchored after `quantity` so the quote block sits together, between
        // the old lead fields and the free-text message.
        $this->forge->addColumn('leads', [
            'service'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'quantity'],
            'project_type'   => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true, 'after' => 'service'],
            'budget'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'project_type'],
            'preferred_date' => ['type' => 'DATE', 'null' => true, 'after' => 'budget'],
            'location'       => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'preferred_date'],
            'attachment'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'location'],
            'locale'         => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'after' => 'source'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('leads', [
            'service',
            'project_type',
            'budget',
            'preferred_date',
            'location',
            'attachment',
            'locale',
        ]);
    }
}
