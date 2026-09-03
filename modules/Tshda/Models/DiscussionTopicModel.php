<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class DiscussionTopicModel extends Model
{
    protected $table         = 'discussion_topics';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['slug', 'title', 'body', 'opens_at', 'closes_at', 'is_current', 'status'];

    /** The topic the home page is asking about, if there is one. */
    public function current(): ?array
    {
        return $this->where('status', 'published')->where('is_current', 1)
            ->orderBy('id', 'DESC')->first();
    }

    /** @return list<array> */
    public function live(): array
    {
        return $this->where('status', 'published')->orderBy('id', 'DESC')->findAll();
    }

    public function findLive(string $slug): ?array
    {
        return $this->where('status', 'published')->where('slug', $slug)->first();
    }

    /** Whether the topic is still accepting comments. */
    public static function isOpen(?array $topic): bool
    {
        if ($topic === null) {
            return false;
        }
        $now = date('Y-m-d H:i:s');

        return (empty($topic['opens_at']) || $topic['opens_at'] <= $now)
            && (empty($topic['closes_at']) || $topic['closes_at'] >= $now);
    }
}
