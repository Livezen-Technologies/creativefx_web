<?php

namespace Modules\Catalog\Libraries;

use CodeIgniter\Database\BaseBuilder;
use Modules\Catalog\Models\CourseSessionModel;

/**
 * The three numbers a course card carries: the cheapest taught seat, the
 * self-paced price if there is one, and the next date.
 *
 * This existed five times — in Courses, Pillars, Locations, Bundles and the
 * home page — as five copies of the same three queries. They had already begun
 * to disagree: one excluded private sessions, another did not, and only two of
 * them had learned that self-paced must be kept out of the "from" figure. Five
 * copies of a rule is five chances to fix a pricing bug in four places.
 *
 * The rule, in one place:
 *
 *   The "from" price EXCLUDES self-paced. Self-paced is a different product at
 *   roughly a tenth of the price, so counting it made every card in the
 *   catalogue read "From $49" — and a two-day taught course advertised at $49
 *   and costing $795 at the checkout is a bait-and-switch, whatever the word
 *   "from" is doing. The card shows what a taught seat costs and names the
 *   self-paced price separately beside it.
 *
 *   Private sessions are excluded too. A bespoke corporate booking is priced
 *   for one client and is not on sale; letting it set the public "from" quotes
 *   a price nobody else can buy.
 *
 * Three queries for any number of cards, never one query per row: this runs on
 * the home page and on a 24-card catalogue index.
 */
final class CardPricer
{
    /**
     * Attach from_cents, ondemand_cents, next_date and currency to each row.
     *
     * @param list<array>                              $rows  course rows, each with an `id`
     * @param (callable(BaseBuilder): BaseBuilder)|null $scope narrows which sessions
     *                                                        count — the location pages
     *                                                        use it to keep a Kandy card
     *                                                        from quoting a Colombo date
     * @return list<array>
     */
    public function decorate(array $rows, string $currency, ?callable $scope = null): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));

        $from = $this->minPrice($ids, $currency, false, $scope);
        // Self-paced has no venue and no date, so a venue scope would exclude
        // all of it. Asked unscoped: the price of the recording is the same
        // whichever city page you found the course on.
        $onDemand = $this->minPrice($ids, $currency, true, null);
        $next     = $this->nextDate($ids, $scope);

        foreach ($rows as &$row) {
            $id = (int) $row['id'];

            $row['from_cents']     = isset($from[$id]) ? (int) $from[$id] : null;
            $row['ondemand_cents'] = isset($onDemand[$id]) ? (int) $onDemand[$id] : null;
            $row['next_date']      = $next[$id] ?? null;
            $row['currency']       = $currency;
        }

        return $rows;
    }

    /**
     * Cheapest published price per course, in the asked-for currency.
     *
     * Prices are published per currency and never converted, so a course with
     * no row in this price book returns nothing rather than a converted guess.
     *
     * @param list<int> $ids
     * @return array<int, string>
     */
    private function minPrice(array $ids, string $currency, bool $selfPaced, ?callable $scope): array
    {
        $builder = db_connect()->table('course_sessions cs')
            ->select('cs.course_id, MIN(sp.price_cents) AS amount', false)
            ->join('session_prices sp', 'sp.session_id = cs.id');

        if ($scope !== null) {
            $builder = $scope($builder);
        }

        $builder
            ->whereIn('cs.course_id', $ids)
            ->where('sp.currency', $currency)
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE);

        $selfPaced
            ? $builder->where('cs.mode', 'SELF_PACED')
            : $builder->where('cs.mode !=', 'SELF_PACED');

        return array_column($builder->groupBy('cs.course_id')->get()->getResultArray(), 'amount', 'course_id');
    }

    /**
     * The next start date per course, today included.
     *
     * Self-paced carries a start date it does not mean — you begin when you
     * buy — so it is kept out of here as well; a card reading "Next date 1 Jan"
     * for something available this minute is noise.
     *
     * @param list<int> $ids
     * @return array<int, string>
     */
    private function nextDate(array $ids, ?callable $scope): array
    {
        $builder = db_connect()->table('course_sessions cs')
            ->select('cs.course_id, MIN(cs.start_date) AS next_date', false);

        if ($scope !== null) {
            $builder = $scope($builder);
        }

        $builder
            ->whereIn('cs.course_id', $ids)
            ->where('cs.is_private', 0)
            ->where('cs.mode !=', 'SELF_PACED')
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
            ->where('cs.start_date >=', date('Y-m-d'));

        return array_column($builder->groupBy('cs.course_id')->get()->getResultArray(), 'next_date', 'course_id');
    }
}
