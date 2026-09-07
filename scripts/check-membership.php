<?php

/**
 * Does a membership do the two things it is sold as doing?
 *
 * It is sold as "pay once, then open any self-paced course". Both halves are
 * asserted here against the real services, because both have a failure mode
 * that looks like success from the other side: a pass can be recorded without
 * granting anything, and access can be granted to somebody holding no pass.
 *
 * The taught-course assertions are the ones that matter commercially. A pass
 * costs LKR 4,500 a month; a classroom seat costs up to LKR 60,000 and there
 * are a fixed number of them. If a membership ever admits somebody to one of
 * those, the school is giving away inventory it cannot restock.
 *
 * Run it with:  php scripts/check-membership.php
 *
 * It writes to the database and removes what it wrote, so point it at a
 * development database.
 */

define('FCPATH', dirname(__DIR__) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
define('ENVIRONMENT', 'development');
CodeIgniter\Boot::bootConsole($paths);

use Modules\Commerce\Models\MembershipModel;
use Modules\Commerce\Models\MembershipPlanModel;
use Modules\Learning\Models\EnrolmentModel;
use Modules\Learning\Services\MembershipService;

$db  = db_connect();
$now = date('Y-m-d H:i:s');

$fail  = 0;
$check = static function (string $what, $got, $want) use (&$fail): void {
    $ok = $got === $want;
    printf("  %-56s %s (got %s, want %s)\n", $what, $ok ? 'ok' : 'FAIL', var_export($got, true), var_export($want, true));
    $fail += $ok ? 0 : 1;
};

// ── The plans, in every currency the site publishes ──────────────────────────
echo "prices\n";
$plans = new MembershipPlanModel();
$expected = [
    'LKR' => [450000, 1150000, 1750000, 3000000],
    'USD' => [1500, 3500, 5500, 8900],
    'INR' => [125000, 295000, 465000, 750000],
    'AED' => [5500, 12900, 19900, 32900],
];
foreach ($expected as $currency => $amounts) {
    $rows = $plans->published($currency);
    $check("four plans priced in {$currency}", count($rows), 4);
    $check("  and the figures are the published ones",
        array_map(static fn ($r) => (int) $r['price_cents'], $rows), $amounts);
}

// Every plan must be priced in every currency, or the page silently drops one.
$missing = $db->query(
    'SELECT p.code, c.code AS currency FROM membership_plans p
     CROSS JOIN currencies c
     LEFT JOIN membership_plan_prices mp ON mp.plan_id = p.id AND mp.currency = c.code
     WHERE c.is_active = 1 AND mp.id IS NULL'
)->getResultArray();
$check('no plan is missing a price in any active currency', $missing, []);

// ── A learner, a pass, and what it opens ────────────────────────────────────
$email = 'membership-check@example.invalid';
$db->table('users')->where('email', $email)->delete();
$db->table('users')->insert([
    'email' => $email, 'password_hash' => password_hash('x', PASSWORD_DEFAULT),
    'first_name' => 'Member', 'last_name' => 'Check', 'status' => 'active',
    'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now,
]);
$userId = (int) $db->insertID();

$selfPaced = $db->table('course_sessions')->where('mode', 'SELF_PACED')->where('status', 'open')->get(1)->getRowArray();
$taught    = $db->table('course_sessions')->where('mode', 'CLASSROOM')->where('status', 'open')->get(1)->getRowArray();

$service    = new MembershipService();
$enrolments = new EnrolmentModel();

try {
    echo "\nbefore buying anything\n";
    $check('not a member', $service->isMember($userId), false);
    $check('no access to a self-paced course',
        $enrolments->hasAccess($userId, (int) $selfPaced['course_id']), false);
    $check('and admit() refuses', $service->admit($userId, (int) $selfPaced['course_id']), 0);

    // ── Buy a month ─────────────────────────────────────────────────────────
    $monthly = $plans->published('LKR')[0];
    $service->record($userId, $monthly, null);

    echo "\nwith a one-month pass\n";
    $check('is a member', $service->isMember($userId), true);

    $pass = (new MembershipModel())->activeFor($userId);
    $days = (int) round((strtotime((string) $pass['expires_at']) - time()) / 86400);
    $check('the pass runs about a month', $days >= 27 && $days <= 32, true);

    // Access is granted on opening, not in advance.
    $check('still no enrolment until the course is opened',
        $enrolments->hasAccess($userId, (int) $selfPaced['course_id']), false);

    $id = $service->admit($userId, (int) $selfPaced['course_id']);
    $check('opening one writes an enrolment', $id > 0, true);
    $check('and now there is access',
        $enrolments->hasAccess($userId, (int) $selfPaced['course_id']), true);

    $row = $db->table('enrolments')->where('id', $id)->get()->getRowArray();
    $check('marked as coming from the membership', $row['source'], 'membership');
    $check('and expiring with it', $row['expires_at'], $pass['expires_at']);

    $check('opening it again does not write a second',
        $service->admit($userId, (int) $selfPaced['course_id']), $id);

    // ── The commercial boundary ─────────────────────────────────────────────
    echo "\nthe boundary that protects the taught classes\n";
    $check('a taught course has no self-paced route',
        $service->selfPacedSession((int) $taught['course_id']) === null
            || (int) $taught['course_id'] === (int) $selfPaced['course_id'], true);

    $taughtOnly = $db->query(
        "SELECT cs.course_id FROM course_sessions cs
         WHERE cs.mode <> 'SELF_PACED'
           AND cs.course_id NOT IN (SELECT course_id FROM course_sessions WHERE mode = 'SELF_PACED')
         LIMIT 1"
    )->getRowArray();

    if ($taughtOnly !== null) {
        $check('a membership does not admit anybody to one',
            $service->admit($userId, (int) $taughtOnly['course_id']), 0);
        $check('and there is no access to it',
            $enrolments->hasAccess($userId, (int) $taughtOnly['course_id']), false);
    } else {
        echo "  (every course has a self-paced route; nothing taught-only to test)\n";
    }

    // ── Renewing ────────────────────────────────────────────────────────────
    echo "\nrenewing before it lapses\n";
    $before = (string) $pass['expires_at'];
    $annual = $plans->published('LKR')[3];
    $service->record($userId, $annual, null);
    $after = (new MembershipModel())->activeFor($userId);

    $check('the new term starts where the old one ended', $after['starts_at'], $before);
    $check('and the pass now runs longer', $after['expires_at'] > $before, true);

    $moved = $db->table('enrolments')->where('id', $id)->get()->getRowArray();
    $check('the course already opened moves with it',
        $moved['expires_at'], $after['expires_at']);

    // A purchased licence must not be dragged back to a membership's date.
    $db->table('enrolments')->insert([
        'user_id' => $userId, 'course_id' => (int) $selfPaced['course_id'] + 100000,
        'session_id' => null, 'mode' => 'SELF_PACED', 'status' => 'active', 'source' => 'purchase',
        'enrolled_at' => $now, 'expires_at' => '2099-01-01 00:00:00',
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $boughtId = (int) $db->insertID();
    $service->extendAccess($userId, '2027-01-01 00:00:00');
    $bought = $db->table('enrolments')->where('id', $boughtId)->get()->getRowArray();
    $check('a bought licence is left alone', $bought['expires_at'], '2099-01-01 00:00:00');

    // ── The money path ──────────────────────────────────────────────────────
    //
    // Asserted against the real CheckoutService and EnrolmentService rather
    // than by writing a memberships row directly: the whole risk in this
    // feature is that a paid order does not turn into a pass, and a test that
    // writes the pass itself cannot see that.
    echo "\nbuying one\n";
    $cartId = (int) $db->table('carts')->insert([
        'user_id' => $userId, 'token' => bin2hex(random_bytes(8)), 'currency' => 'LKR',
        'country' => 'LK', 'created_at' => $now, 'updated_at' => $now,
    ], true) ? (int) $db->insertID() : 0;
    $cart = $db->table('carts')->where('id', $cartId)->get()->getRowArray();

    $checkout = new \Modules\Commerce\Services\CheckoutService();
    $added    = $checkout->addMembership($cart, (int) $annual['id'], 'LKR');
    $check('the annual plan goes in the basket', $added['ok'], true);

    $line = $db->table('cart_items')->where('cart_id', $cartId)->get()->getRowArray();
    $check('at the published LKR price', (int) $line['unit_price_cents'], 3000000);
    $check('quantity one', (int) $line['qty'], 1);

    // Choosing a second plan replaces the first rather than adding to it.
    $checkout->addMembership($cart, (int) $monthly['id'], 'LKR');
    $check('choosing another plan replaces it',
        $db->table('cart_items')->where('cart_id', $cartId)->countAllResults(), 1);

    $totals = $checkout->totals($cart);
    $check('and the basket names it', trim((string) ($totals['items'][0]['course_title'] ?? '')) !== '', true);

    // An order carrying a paid membership line must produce exactly one pass.
    $db->table('memberships')->where('user_id', $userId)->delete();
    $orderId = (int) $db->table('orders')->insert([
        'order_no' => 'CHK-' . random_int(10000, 99999), 'user_id' => $userId,
        'status' => 'pending', 'currency' => 'LKR', 'country' => 'LK',
        'subtotal_cents' => 3000000, 'discount_cents' => 0, 'tax_cents' => 0,
        'total_cents' => 3000000, 'placed_at' => $now,
        'created_at' => $now, 'updated_at' => $now,
    ], true) ? (int) $db->insertID() : 0;
    $db->table('order_items')->insert([
        'order_id' => $orderId, 'item_type' => 'membership', 'item_id' => (int) $annual['id'],
        'qty' => 1, 'title_snapshot' => 'Annual',
        'meta_snapshot_json' => json_encode(['months' => 12]),
        'unit_price_cents' => 3000000, 'discount_cents' => 0, 'tax_cents' => 0,
        'total_cents' => 3000000, 'created_at' => $now,
    ]);
    $itemId = (int) $db->insertID();

    $result = (new \Modules\Learning\Services\EnrolmentService())->fulfil($orderId);
    $check('the order fulfils', $result['ok'], true);
    $check('a pass was written',
        $db->table('memberships')->where('order_item_id', $itemId)->countAllResults(), 1);
    $check('and no enrolment was invented for it',
        $db->table('enrolments')->where('user_id', $userId)->where('order_item_id', $itemId)->countAllResults(), 0);

    // A gateway that delivers twice must not sell two.
    (new \Modules\Learning\Services\EnrolmentService())->fulfil($orderId);
    $check('a repeated webhook does not sell a second',
        $db->table('memberships')->where('order_item_id', $itemId)->countAllResults(), 1);

    $db->table('order_items')->where('order_id', $orderId)->delete();
    $db->table('orders')->where('id', $orderId)->delete();
    $db->table('cart_items')->where('cart_id', $cartId)->delete();
    $db->table('carts')->where('id', $cartId)->delete();

    // ── Lapsing ─────────────────────────────────────────────────────────────
    echo "\nwhen it lapses\n";
    $db->table('memberships')->where('user_id', $userId)->update(['expires_at' => '2020-01-01 00:00:00']);
    $db->table('enrolments')->where('id', $id)->update(['expires_at' => '2020-01-01 00:00:00']);
    $check('no longer a member', $service->isMember($userId), false);
    $check('and the course closes again',
        $enrolments->hasAccess($userId, (int) $selfPaced['course_id']), false);
} finally {
    $db->table('enrolments')->where('user_id', $userId)->delete();
    $db->table('memberships')->where('user_id', $userId)->delete();
    $db->table('users')->where('id', $userId)->delete();
}

echo "\n", $fail === 0 ? "all checks passed\n" : "$fail check(s) FAILED\n";
exit($fail === 0 ? 0 : 1);
