<?php

namespace Modules\News\Models;

use CodeIgniter\Model;

class NewsPostModel extends Model
{
    protected $table         = 'news_posts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'category_id', 'slug', 'title', 'excerpt', 'body', 'image', 'tags',
        'author', 'meta_title', 'meta_description', 'status', 'published_at',
    ];

    /**
     * Scope: published posts whose publish date has arrived (scheduled posts
     * with a future published_at stay hidden), newest first.
     */
    public function live(?int $categoryId = null): self
    {
        $this->where('status', 'published')
            ->groupStart()
                ->where('published_at IS NULL')
                ->orWhere('published_at <=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC');

        if ($categoryId !== null) {
            $this->where('category_id', $categoryId);
        }

        return $this;
    }

    public function findLiveBySlug(string $slug): ?array
    {
        return $this->live()->where('slug', $slug)->first();
    }

    /** @return list<array> */
    public function latest(int $limit = 3): array
    {
        return $this->live()->findAll($limit);
    }

    /** Other live posts from the same category (or newest overall as filler). */
    public function related(array $post, int $limit = 3): array
    {
        $related = [];
        if (! empty($post['category_id'])) {
            $related = $this->live((int) $post['category_id'])
                ->where('id !=', (int) $post['id'])
                ->findAll($limit);
        }
        if (count($related) < $limit) {
            $exclude = array_merge([(int) $post['id']], array_column($related, 'id'));
            $filler  = $this->live()->whereNotIn('id', $exclude)->findAll($limit - count($related));
            $related = array_merge($related, $filler);
        }
        return $related;
    }
}
