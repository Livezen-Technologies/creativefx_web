<?php

namespace Modules\Crm\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    /**
     * The pipeline a quote request moves through, in order, as value => label.
     * Admin reads this for its status picker so the wording cannot drift from
     * what the public form writes.
     *
     * Leads captured before the CreativeFX rebuild may still carry the retired
     * 'converted' status; nothing rewrites them, they are simply re-filed by
     * hand the next time someone opens them.
     */
    public const STATUSES = [
        'new'           => 'New',
        'contacted'     => 'Contacted',
        'qualified'     => 'Qualified',
        'proposal_sent' => 'Proposal sent',
        'negotiation'   => 'Negotiation',
        'won'           => 'Won',
        'lost'          => 'Lost',
    ];

    protected $table         = 'leads';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name', 'email', 'company', 'country', 'phone', 'interest', 'quantity', 'message', 'status', 'source',
        // Quote request fields (see 2026-08-17-000130_AddQuoteFieldsToLeads).
        'service', 'project_type', 'budget', 'preferred_date', 'location', 'attachment', 'locale',
    ];
}
