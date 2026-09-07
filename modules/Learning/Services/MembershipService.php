<?php

namespace Modules\Learning\Services;

use CodeIgniter\Database\BaseConnection;
use Modules\Commerce\Models\MembershipModel;

/**
 * Turning a pass into access.
 *
 * **A membership writes an enrolment; it is not a second kind of access.**
 *
 * That is the whole design, and the alternative is worth stating because it is
 * the obvious one: teach `hasAccess()` about memberships and let a member into
 * the player with no enrolment row at all. It fails immediately downstream.
 * Lesson progress is keyed to an enrolment. So is a quiz attempt. So is a
 * certificate, and `CertificateService` decides eligibility by reading the
 * enrolment's session. The account dashboard lists enrolments. Every one of
 * those would need a second code path, and the first one anybody forgot would
 * fail silently and in the member's favour — a learner who finishes a course
 * and is told they have no record of it.
 *
 * So the pass is the *entitlement* and an enrolment is the *record*, written
 * the first time a member opens a course. Everything downstream carries on
 * reading enrolments and does not know memberships exist.
 *
 * Two consequences follow, and both are deliberate:
 *
 *   The enrolment carries `source = 'membership'` and expires with the pass,
 *   so access lapses when the pass does without anything having to sweep. A
 *   purchased self-paced licence has `source = 'purchase'` and its own twelve
 *   months, and renewing a membership must never shorten one of those — see
 *   extendAccess().
 *
 *   Only self-paced courses are covered. A taught seat is finite inventory at
 *   twenty to sixty thousand rupees; a pass at four and a half thousand a month
 *   cannot include one, and a member holding seats would be consuming the stock
 *   that paying bookings depend on.
 */
class MembershipService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** Does this learner hold a live pass? */
    public function isMember(int $userId): bool
    {
        return (new MembershipModel())->activeFor($userId) !== null;
    }

    /**
     * The self-paced session a membership would admit somebody to, or null.
     *
     * Null means the course is not in the library — it is taught only — and a
     * member has no more right to it than anybody else.
     */
    public function selfPacedSession(int $courseId): ?array
    {
        return $this->db->table('course_sessions')
            ->where('course_id', $courseId)
            ->where('mode', 'SELF_PACED')
            ->where('status', 'open')
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getRowArray();
    }

    /**
     * Make sure a member has an enrolment on this course, and return its id.
     *
     * Returns 0 when they hold no pass, or when the course is not in the
     * library. Idempotent: called on every page load of the player, and a
     * learner who already has any enrolment on the course — bought, corporate
     * or from an earlier pass — keeps it untouched rather than gaining a second.
     */
    public function admit(int $userId, int $courseId): int
    {
        $pass = (new MembershipModel())->activeFor($userId);
        if ($pass === null) {
            return 0;
        }

        $session = $this->selfPacedSession($courseId);
        if ($session === null) {
            return 0;
        }

        $existing = $this->db->table('enrolments')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            // Already entitled by some other route. If that entitlement is a
            // membership one that has fallen behind the current pass — the
            // learner let it lapse and came back — move it forward rather than
            // writing a duplicate.
            if (($existing['source'] ?? '') === 'membership'
                && (string) $existing['expires_at'] < (string) $pass['expires_at']) {
                $this->db->table('enrolments')->where('id', (int) $existing['id'])->update([
                    'expires_at' => $pass['expires_at'],
                    'status'     => $existing['status'] === 'completed' ? 'completed' : 'active',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return (int) $existing['id'];
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('enrolments')->insert([
            'user_id'       => $userId,
            'order_item_id' => null,
            'course_id'     => $courseId,
            'session_id'    => (int) $session['id'],
            'bundle_id'     => null,
            'account_id'    => null,
            'mode'          => 'SELF_PACED',
            'status'        => 'active',
            'source'        => 'membership',
            'enrolled_at'   => $now,
            // The pass's end, not twelve months. A month's membership that
            // granted a year of a course would be a pricing hole wide enough
            // to drive the whole catalogue through.
            'expires_at'    => $pass['expires_at'],
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Carry every membership-granted enrolment forward to a new expiry.
     *
     * Called when a pass is bought or renewed. Without it, a member who renews
     * keeps their pass but loses the courses they had already opened, because
     * those enrolments still end on the old date — the failure would look like
     * "I paid again and my half-finished course disappeared".
     *
     * Only ever moves an expiry *forward*, and only on membership-sourced rows.
     * A purchased licence with eleven months left must not be cut down to the
     * end of a one-month pass.
     */
    public function extendAccess(int $userId, string $expiresAt): int
    {
        $rows = $this->db->table('enrolments')
            ->where('user_id', $userId)
            ->where('source', 'membership')
            ->where('expires_at <', $expiresAt)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $this->db->table('enrolments')->where('id', (int) $row['id'])->update([
                'expires_at' => $expiresAt,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return count($rows);
    }

    /**
     * Record a bought pass, and return its id.
     *
     * A renewal bought while one is still running is **added to the end of it**,
     * not started today — somebody who renews a week early has paid for a
     * month and must get a month, not three weeks. `order_item_id` is unique in
     * the schema, so a webhook delivered twice cannot sell two passes; the
     * second delivery finds the row and returns it.
     */
    public function record(int $userId, array $plan, ?int $orderItemId = null): int
    {
        $memberships = new MembershipModel();

        if ($orderItemId !== null) {
            $already = $memberships->where('order_item_id', $orderItemId)->first();
            if ($already !== null) {
                return (int) $already['id'];
            }
        }

        $current = $memberships->activeFor($userId);
        $startsAt = $current !== null ? (string) $current['expires_at'] : date('Y-m-d H:i:s');

        $expiresAt = date(
            'Y-m-d H:i:s',
            strtotime($startsAt . ' +' . max(1, (int) $plan['months']) . ' months')
        );

        $id = (int) $memberships->insert([
            'user_id'       => $userId,
            'plan_id'       => (int) $plan['id'],
            'order_item_id' => $orderItemId,
            'status'        => 'active',
            'starts_at'     => $startsAt,
            'expires_at'    => $expiresAt,
        ], true);

        $this->extendAccess($userId, $expiresAt);

        return $id;
    }
}
