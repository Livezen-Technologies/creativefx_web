<?php

namespace Modules\Learning\Services;

use CodeIgniter\Database\BaseConnection;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Modules\Learning\Models\AttendanceModel;
use Modules\Learning\Models\CertificateModel;

/**
 * Issuing a certificate, and being able to prove one is real.
 *
 * The verification endpoint is the point of the whole feature. A PDF is a
 * picture: anybody can make one that says anything. What makes a certificate
 * worth issuing is that a corporate buyer, an HR department or a visa officer
 * can check it at a public URL without trusting the document in front of them —
 * which is why `/verify/{code}` is a first-class page and not an afterthought.
 *
 * Two identifiers, for a reason worth restating where somebody will read it:
 * the **serial** is ours, sequential, and printed on the document; the **verify
 * code** is random and is the only one that ever appears in a URL. If the
 * public code were the serial, holding one certificate would let anybody count
 * up and read every learner's name and course.
 *
 * Eligibility is a rule, not a judgement. A certificate is a statement that
 * somebody was there and did the work: attendance for a taught class, the final
 * quiz for self-paced study. Issuing one to a name that never appeared devalues
 * every certificate the school has ever issued.
 */
class CertificateService
{
    /** Proportion of a taught course a learner must attend. */
    private const ATTENDANCE_THRESHOLD = 80;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Whether this enrolment has earned a certificate.
     *
     * @return array{ok:bool, reason:string}
     *         reason ∈ ok | not_finished | attendance | quiz | already
     */
    public function eligible(int $enrolmentId): array
    {
        $enrolment = $this->db->table('enrolments')->where('id', $enrolmentId)->get()->getRowArray();
        if ($enrolment === null) {
            return ['ok' => false, 'reason' => 'not_finished'];
        }

        if ((new CertificateModel())->forEnrolment($enrolmentId) !== null) {
            return ['ok' => false, 'reason' => 'already'];
        }

        // Self-paced: the final quiz decides. No final quiz on the course means
        // completing every lesson is the bar.
        if (empty($enrolment['session_id']) || $this->isSelfPaced((int) $enrolment['session_id'])) {
            return $this->selfPacedEligible($enrolment);
        }

        $session = $this->db->table('course_sessions')->where('id', (int) $enrolment['session_id'])->get()->getRowArray();
        if ($session === null) {
            return ['ok' => false, 'reason' => 'not_finished'];
        }

        // A class that has not happened yet cannot have been attended. Obvious,
        // and exactly the check that gets left out of an admin bulk action.
        if (! empty($session['end_date']) && $session['end_date'] > date('Y-m-d')) {
            return ['ok' => false, 'reason' => 'not_finished'];
        }

        if (! (new AttendanceModel())->qualifies($enrolmentId, (int) $session['id'], self::ATTENDANCE_THRESHOLD)) {
            return ['ok' => false, 'reason' => 'attendance'];
        }

        return ['ok' => true, 'reason' => 'ok'];
    }

    /**
     * Issue the certificate, once.
     *
     * `$force` exists for the case a school genuinely has: somebody attended,
     * the register was not marked, and an administrator knows they were there.
     * It is deliberately a separate argument rather than a softer rule, so the
     * exception is a decision somebody makes rather than a threshold nobody
     * notices has been lowered.
     */
    public function issue(int $enrolmentId, bool $force = false): ?array
    {
        $check = $this->eligible($enrolmentId);
        if ($check['reason'] === 'already') {
            return (new CertificateModel())->forEnrolment($enrolmentId);
        }
        if (! $check['ok'] && ! $force) {
            return null;
        }

        helper('norlanka');

        $enrolment = $this->db->table('enrolments')->where('id', $enrolmentId)->get()->getRowArray();
        $user      = $this->db->table('users')->where('id', (int) $enrolment['user_id'])->get()->getRowArray();
        $course    = $this->db->table('courses')->where('id', (int) $enrolment['course_id'])->get()->getRowArray();

        if ($user === null || $course === null) {
            return null;
        }

        $certificates = new CertificateModel();

        $row = [
            'enrolment_id' => $enrolmentId,
            'user_id'      => (int) $enrolment['user_id'],
            'course_id'    => (int) $enrolment['course_id'],
            'bundle_id'    => $enrolment['bundle_id'] ? (int) $enrolment['bundle_id'] : null,
            // The name is frozen onto the certificate. A learner who later
            // changes their surname in their profile has not retrospectively
            // been somebody else in a classroom.
            'learner_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: (string) $user['email'],
            'title'        => mb_substr((string) t_field($course['title']), 0, 191),
            'mode'         => (string) $enrolment['mode'],
            'hours'        => (int) $course['duration_hours'],
            'serial'       => $this->nextSerial(),
            'verify_code'  => $this->nextVerifyCode(),
            'issued_at'    => date('Y-m-d H:i:s'),
        ];

        $id          = $certificates->insert($row, true);
        $certificate = $certificates->find($id);

        // The PDF is a rendering of the row, not the record itself. If writing
        // it fails the certificate still exists and still verifies; the file is
        // regenerated on download.
        try {
            $certificate['pdf_path'] = $this->writePdf($certificate);
            $certificates->update($id, ['pdf_path' => $certificate['pdf_path']]);
        } catch (\Throwable $e) {
            log_message('error', 'Certificate PDF failed for {serial}: {msg}', [
                'serial' => $certificate['serial'],
                'msg'    => $e->getMessage(),
            ]);
        }

        $this->db->table('enrolments')->where('id', $enrolmentId)->update([
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return $certificate;
    }

    /**
     * Withdraw a certificate without deleting it.
     *
     * The verification endpoint must keep answering — with "revoked" as the
     * answer. A certificate that simply stops existing looks, to whoever is
     * checking it, exactly like one that was never issued, and the difference
     * matters to the person holding it.
     */
    public function revoke(int $certificateId, string $reason): void
    {
        (new CertificateModel())->update($certificateId, [
            'revoked_at'    => date('Y-m-d H:i:s'),
            'revoke_reason' => mb_substr($reason, 0, 255),
        ]);
    }

    /** The PDF bytes, rendering them again if the stored file has gone. */
    public function pdf(array $certificate): string
    {
        $path = $this->pathFor($certificate);
        if (is_file($path)) {
            return (string) file_get_contents($path);
        }

        $this->writePdf($certificate);

        return (string) file_get_contents($path);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    private function writePdf(array $certificate): string
    {
        helper(['norlanka', 'url']);

        $options = new Options();
        // The certificate template embeds its images as data URIs, so nothing
        // needs fetching. Leaving remote loading off means a document cannot be
        // made to reach out to a URL somebody put in a course title.
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml(view('Modules\Learning\Views\certificate\document', [
            'certificate' => $certificate,
            'qr'          => $this->qrDataUri($certificate),
            'verifyUrl'   => $this->verifyUrl($certificate),
        ], ['saveData' => false]), 'UTF-8');
        $dompdf->render();

        $path = $this->pathFor($certificate);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, (string) $dompdf->output());

        return 'certificates/' . basename($path);
    }

    /**
     * Under `writable/`, never under `public/`.
     *
     * A certificate carries somebody's full name and what they studied. Served
     * from the web root it would be one guessed filename away from anybody, and
     * indexable by a crawler that found one link. Downloads go through a
     * controller that checks who is asking.
     */
    private function pathFor(array $certificate): string
    {
        return WRITEPATH . 'certificates/' . $certificate['serial'] . '.pdf';
    }

    private function verifyUrl(array $certificate): string
    {
        return rtrim(base_url(), '/') . '/verify/' . $certificate['verify_code'];
    }

    private function qrDataUri(array $certificate): string
    {
        // High error correction: this square gets printed, photocopied and
        // photographed off a screen at an angle, and a code that stops scanning
        // when a corner is smudged has failed at the only job it has.
        return (new Builder(
            writer: new PngWriter(),
            data: $this->verifyUrl($certificate),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 420,
            margin: 8,
        ))->build()->getDataUri();
    }

    /** MLP-CERT-2026-00147. Sequential, and printed on the document. */
    private function nextSerial(): string
    {
        $year = date('Y');
        $last = $this->db->table('certificates')
            ->like('serial', 'MLP-CERT-' . $year . '-', 'after')
            ->orderBy('id', 'DESC')->get()->getRowArray();

        $next = $last === null ? 1 : ((int) substr((string) $last['serial'], -5)) + 1;

        return sprintf('MLP-CERT-%s-%05d', $year, $next);
    }

    /** Random, unguessable, and the only identifier that appears in a URL. */
    private function nextVerifyCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(6)));
        } while ($this->db->table('certificates')->where('verify_code', $code)->countAllResults() > 0);

        return $code;
    }

    private function isSelfPaced(int $sessionId): bool
    {
        $session = $this->db->table('course_sessions')->select('mode')->where('id', $sessionId)->get()->getRowArray();

        return ($session['mode'] ?? '') === 'SELF_PACED';
    }

    /** @return array{ok:bool, reason:string} */
    private function selfPacedEligible(array $enrolment): array
    {
        $courseId = (int) $enrolment['course_id'];

        $final = $this->db->table('quizzes q')
            ->select('q.id')
            ->join('lessons l', 'l.id = q.lesson_id')
            ->where('l.course_id', $courseId)->where('q.is_final', 1)
            ->get()->getRowArray();

        if ($final !== null) {
            $passed = $this->db->table('quiz_attempts')
                ->where('user_id', (int) $enrolment['user_id'])
                ->where('quiz_id', (int) $final['id'])
                ->where('passed', 1)
                ->countAllResults() > 0;

            return $passed ? ['ok' => true, 'reason' => 'ok'] : ['ok' => false, 'reason' => 'quiz'];
        }

        // No final assessment: completing every published lesson is the bar.
        $total = $this->db->table('lessons')->where('course_id', $courseId)->where('status', 'published')->countAllResults();
        if ($total === 0) {
            return ['ok' => false, 'reason' => 'not_finished'];
        }

        $done = $this->db->table('lesson_progress')
            ->where('user_id', (int) $enrolment['user_id'])->where('course_id', $courseId)
            ->where('status', 'completed')->countAllResults();

        return $done >= $total ? ['ok' => true, 'reason' => 'ok'] : ['ok' => false, 'reason' => 'not_finished'];
    }
}
