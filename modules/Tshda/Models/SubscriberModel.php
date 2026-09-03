<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class SubscriberModel extends Model
{
    protected $table         = 'subscribers';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'email', 'name', 'locale', 'topics', 'status', 'token', 'confirmed_at',
        'unsubscribed_at', 'last_sent_at', 'bounce_count',
    ];

    /** The alert categories a subscriber can choose between (Clause 3.13). */
    public const TOPICS = ['tenders', 'vacancies', 'subsidies', 'training', 'news'];

    public function findByToken(string $token): ?array
    {
        return $token === '' ? null : $this->where('token', $token)->first();
    }

    /**
     * Everyone who should receive an alert on this topic: confirmed addresses
     * only. A pending row has never proved it belongs to the person who typed
     * it, and mailing it would be the thing double opt-in exists to prevent.
     *
     * @return list<array>
     */
    public function audience(string $topic): array
    {
        return $this->where('status', 'active')
            ->groupStart()
                ->where('topics IS NULL')
                ->orWhere('topics', '')
                ->orLike('topics', $topic)
            ->groupEnd()
            ->findAll();
    }

    public function stats(): array
    {
        $rows = $this->select('status, COUNT(*) AS n')->groupBy('status')->findAll();
        $out  = ['pending' => 0, 'active' => 0, 'unsubscribed' => 0, 'bounced' => 0];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['n'];
        }

        return $out;
    }
}
