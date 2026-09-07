<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

/**
 * The cart.
 *
 * Guest-first: a cart is addressed by an unguessable token in a cookie and only
 * later attached to a user. Making somebody create an account before they know
 * the total is the most reliable way to lose the sale, and for a corporate
 * buyer it is often the wrong account anyway — the person paying is frequently
 * not the person learning.
 *
 * `currency` and `country` are frozen onto the cart at creation rather than
 * resolved per request. A visitor whose currency is recomputed on every page
 * load is a visitor whose price changes because a CDN header wobbled between
 * the course page and the checkout.
 */
class CartModel extends Model
{
    protected $table         = 'carts';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['token', 'user_id', 'currency', 'country', 'coupon_id', 'expires_at'];

    /** How long an untouched cart survives. Long enough to sleep on it. */
    public const LIFETIME_DAYS = 14;

    public function findByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        return $this->where('token', $token)
            ->groupStart()->where('expires_at IS NULL')->orWhere('expires_at >', date('Y-m-d H:i:s'))->groupEnd()
            ->first();
    }

    public function start(string $currency, ?string $country, ?int $userId = null): array
    {
        // 32 bytes of randomness in the cookie. The token is the only thing
        // standing between a guest cart and anybody who can guess it, and a
        // cart holds an email address and a list of attendees.
        $token = bin2hex(random_bytes(16));

        $id = $this->insert([
            'token'      => $token,
            'user_id'    => $userId,
            'currency'   => $currency,
            'country'    => $country,
            'expires_at' => date('Y-m-d H:i:s', time() + self::LIFETIME_DAYS * 86400),
        ], true);

        return $this->find($id);
    }

    /** @return list<array> */
    public function items(int $cartId): array
    {
        return $this->db->table('cart_items')->where('cart_id', $cartId)
            ->orderBy('id', 'ASC')->get()->getResultArray();
    }

    /**
     * The cart, priced, with each line's session and course joined on.
     *
     * Everything is read back from `cart_items` rather than re-priced from the
     * catalogue: a price the site quoted when the item went in is a price the
     * site honours for the life of the cart, and an administrator editing a
     * price must not silently change what somebody is half way through buying.
     *
     * @return array{items:list<array>, subtotal_cents:int, discount_cents:int, count:int}
     */
    public function summary(int $cartId): array
    {
        $items = $this->db->table('cart_items ci')
            ->select('ci.*, cs.start_date, cs.end_date, cs.mode AS session_mode, cs.timezone, cs.daily_start, cs.daily_end, cs.seats_total, cs.seats_sold, cs.seats_reserved, cs.status AS session_status, c.slug AS course_slug, c.title AS course_title, c.hero_image, c.duration_days, v.name AS venue_name, v.city AS venue_city')
            ->join('course_sessions cs', "cs.id = ci.item_id AND ci.item_type = 'session'", 'left', false)
            ->join('courses c', 'c.id = cs.course_id', 'left')
            ->join('venues v', 'v.id = cs.venue_id', 'left')
            ->where('ci.cart_id', $cartId)
            ->orderBy('ci.id', 'ASC')
            ->get()->getResultArray();

        // A bundle line has no session to join, so its title comes from the
        // bundle. Done as a second pass over the few bundle rows rather than a
        // second LEFT JOIN, which would double the width of every row for the
        // sake of the minority case.
        $bundleIds = array_column(array_filter($items, static fn ($i) => $i['item_type'] === 'bundle'), 'item_id');
        if ($bundleIds !== []) {
            $bundles = array_column(
                $this->db->table('bundles')->whereIn('id', $bundleIds)->get()->getResultArray(),
                null,
                'id'
            );
            foreach ($items as &$item) {
                if ($item['item_type'] === 'bundle' && isset($bundles[$item['item_id']])) {
                    $item['course_title'] = $bundles[$item['item_id']]['title'];
                    $item['course_slug']  = $bundles[$item['item_id']]['slug'];
                    $item['hero_image']   = $bundles[$item['item_id']]['hero_image'];
                }
            }
            unset($item);
        }

        // The same again for a membership line, which has neither a session nor
        // a bundle behind it. Without this the basket showed an amount with no
        // name against it — the one line in an order somebody is least likely
        // to recognise from the figure alone.
        $planIds = array_column(array_filter($items, static fn ($i) => $i['item_type'] === 'membership'), 'item_id');
        if ($planIds !== []) {
            $plans = array_column(
                $this->db->table('membership_plans')->whereIn('id', $planIds)->get()->getResultArray(),
                null,
                'id'
            );
            foreach ($items as &$item) {
                if ($item['item_type'] === 'membership' && isset($plans[$item['item_id']])) {
                    $item['course_title'] = $plans[$item['item_id']]['name'];
                    $item['months']       = (int) $plans[$item['item_id']]['months'];
                }
            }
            unset($item);
        }

        $subtotal = 0;
        $discount = 0;
        $count    = 0;
        foreach ($items as $item) {
            $subtotal += (int) $item['unit_price_cents'] * (int) $item['qty'];
            $discount += (int) $item['discount_cents'];
            $count    += (int) $item['qty'];
        }

        return [
            'items'          => $items,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'count'          => $count,
        ];
    }

    /**
     * Attach a guest cart to a user who has just signed in.
     *
     * If they already had a cart, the guest one wins and the older is dropped:
     * what somebody put in the basket two minutes ago is what they came to buy,
     * and silently replacing it with a fortnight-old cart is the behaviour
     * everybody hates.
     */
    public function attachTo(int $cartId, int $userId): void
    {
        $this->db->transStart();

        $this->db->table('carts')
            ->where('user_id', $userId)->where('id !=', $cartId)
            ->update(['user_id' => null, 'expires_at' => date('Y-m-d H:i:s')]);

        $this->update($cartId, ['user_id' => $userId]);

        $this->db->transComplete();
    }
}
