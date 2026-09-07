<?php

namespace Modules\Learning\Models;

use CodeIgniter\Model;

/**
 * Certificates, and the public record that says whether one is real.
 *
 * Two identifiers on purpose. `serial` is ours, sequential, and printed on the
 * document. `verify_code` is random and is the only one that appears in a URL —
 * because if the public code were the serial, holding one certificate would let
 * anybody walk the whole series and read every learner's name.
 *
 * A withdrawn certificate is revoked, never deleted: the verification endpoint
 * has to keep answering, with "revoked" as the answer.
 */
class CertificateModel extends Model
{
    protected $table         = 'certificates';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'enrolment_id', 'user_id', 'course_id', 'bundle_id', 'learner_name',
        'title', 'mode', 'hours', 'serial', 'verify_code', 'issued_at',
        'pdf_path', 'revoked_at', 'revoke_reason',
    ];

    public function findByCode(string $code): ?array
    {
        if (! preg_match('/^[A-Za-z0-9]{8,32}$/', $code)) {
            return null;
        }

        return $this->select('certificates.*, c.slug AS course_slug, c.title AS course_title')
            ->join('courses c', 'c.id = certificates.course_id', 'left')
            ->where('certificates.verify_code', $code)
            ->first();
    }

    /** @return list<array> */
    public function forUser(int $userId): array
    {
        return $this->select('certificates.*, c.slug AS course_slug')
            ->join('courses c', 'c.id = certificates.course_id', 'left')
            ->where('certificates.user_id', $userId)
            ->orderBy('certificates.issued_at', 'DESC')
            ->findAll();
    }

    public function forEnrolment(int $enrolmentId): ?array
    {
        return $this->where('enrolment_id', $enrolmentId)->first();
    }
}
