<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table         = 'documents';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'category_id', 'slug', 'title', 'description', 'file_path', 'file_size',
        'file_type', 'language', 'extracted_text', 'download_count',
        'published_at', 'expires_at', 'status',
    ];

    /**
     * Published, in date, newest first.
     *
     * expires_at is how a tender closes itself: the closing date is typed once
     * when the document is uploaded and the listing drops it on the day,
     * without anybody remembering to go back and unpublish it.
     */
    public function live(?int $categoryId = null): self
    {
        $now = date('Y-m-d H:i:s');

        $this->where('status', 'published')
            ->groupStart()->where('published_at IS NULL')->orWhere('published_at <=', $now)->groupEnd()
            ->groupStart()->where('expires_at IS NULL')->orWhere('expires_at >=', $now)->groupEnd()
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC');

        if ($categoryId !== null) {
            $this->where('category_id', $categoryId);
        }

        return $this;
    }

    /**
     * Full-text-ish search across the title, description and the text pulled
     * out of the file itself at upload — which is what makes a phrase inside a
     * circular findable, rather than only its file name (Clause 3.12).
     *
     * @return list<array>
     */
    public function search(string $term, ?int $categoryId = null, int $limit = 50): array
    {
        $q = $this->live($categoryId);

        if ($term !== '') {
            $q->groupStart()
                ->like('title', $term)
                ->orLike('description', $term)
                ->orLike('extracted_text', $term)
              ->groupEnd();
        }

        return $q->findAll($limit);
    }

    public function recordDownload(int $id): void
    {
        $this->db->table($this->table)->where('id', $id)->set('download_count', 'download_count + 1', false)->update();
    }
}
