<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class FeedbackModel extends Model
{
    protected $table         = 'feedback';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'reference', 'kind', 'name', 'email', 'phone', 'district', 'subject',
        'message', 'page_url', 'attachment', 'locale', 'status', 'assigned_to',
        'division', 'due_at', 'answered_at', 'response', 'ip_hash',
    ];

    public function findByReference(string $reference): ?array
    {
        return $reference === '' ? null : $this->where('reference', $reference)->first();
    }

    /** @return list<array> */
    public function queue(array $filters = []): array
    {
        if (! empty($filters['status'])) {
            $this->where('status', $filters['status']);
        }
        if (! empty($filters['kind'])) {
            $this->where('kind', $filters['kind']);
        }
        if (! empty($filters['q'])) {
            $this->groupStart()
                ->like('reference', $filters['q'])
                ->orLike('name', $filters['q'])
                ->orLike('subject', $filters['q'])
              ->groupEnd();
        }

        return $this->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * The closed-loop counters the CMT watches (Clause 3.14): how many public
     * queries are open, answered and overdue. Overdue is measured against the
     * due date the submission was stamped with, not against a guess.
     */
    public function loopStats(): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            'open'     => $this->where('status', 'open')->countAllResults(),
            'assigned' => $this->where('status', 'assigned')->countAllResults(),
            'answered' => $this->where('status', 'answered')->countAllResults(),
            'closed'   => $this->where('status', 'closed')->countAllResults(),
            'overdue'  => $this->whereIn('status', ['open', 'assigned'])
                ->where('due_at IS NOT NULL')->where('due_at <', $now)->countAllResults(),
        ];
    }
}
