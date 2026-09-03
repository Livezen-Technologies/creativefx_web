<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class DiscussionCommentModel extends Model
{
    protected $table         = 'discussion_comments';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'topic_id', 'author', 'email', 'district', 'body', 'locale', 'status',
        'moderated_by', 'moderated_at', 'ip_hash',
    ];

    /**
     * Comments a visitor may see: approved only, oldest first so a thread
     * reads in the order it was written.
     *
     * @return list<array>
     */
    public function approved(int $topicId, int $limit = 0): array
    {
        return $this->where('topic_id', $topicId)->where('status', 'approved')
            ->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')
            ->findAll($limit ?: null);
    }

    public function pendingCount(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }

    /** @return list<array> */
    public function moderationQueue(?string $status = 'pending'): array
    {
        $this->select('discussion_comments.*, discussion_topics.title AS topic_title')
            ->join('discussion_topics', 'discussion_topics.id = discussion_comments.topic_id', 'left')
            ->orderBy('discussion_comments.created_at', 'DESC');

        if ($status !== null && $status !== '') {
            $this->where('discussion_comments.status', $status);
        }

        return $this->findAll();
    }
}
