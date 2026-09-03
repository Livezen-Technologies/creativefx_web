<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class NoticeModel extends Model
{
    protected $table         = 'notices';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['title', 'body', 'severity', 'url', 'starts_at', 'ends_at', 'sort_order', 'status'];

    /**
     * The notices showing right now: published, started, not yet ended.
     *
     * A blank window means "from now until somebody takes it down", which is
     * how a notice gets published in seconds — the officer types the text and
     * saves, and does not have to reason about dates to make it appear.
     *
     * @return list<array>
     */
    public function current(int $limit = 5): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->where('status', 'published')
            ->groupStart()->where('starts_at IS NULL')->orWhere('starts_at <=', $now)->groupEnd()
            ->groupStart()->where('ends_at IS NULL')->orWhere('ends_at >=', $now)->groupEnd()
            ->orderBy('severity = \'urgent\'', 'DESC', false)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }
}
