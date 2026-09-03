<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class ProgrammeModel extends Model
{
    protected $table         = 'programmes';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'title', 'summary', 'description', 'audience', 'starts_on',
        'ends_on', 'closes_on', 'capacity', 'booked', 'residential', 'fee',
        'venue', 'image', 'sort_order', 'status',
    ];

    /**
     * The public calendar: published programmes that have not already run,
     * soonest first.
     *
     * @return list<array>
     */
    public function calendar(int $limit = 0): array
    {
        $today = date('Y-m-d');

        return $this->where('status', 'published')
            ->groupStart()->where('starts_on IS NULL')->orWhere('starts_on >=', $today)->groupEnd()
            ->orderBy('starts_on', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->findAll($limit ?: null);
    }

    public function findLive(string $slug): ?array
    {
        return $this->where('status', 'published')->where('slug', $slug)->first();
    }

    /**
     * Whether a programme is still taking applications. Capacity control is a
     * clause requirement — "a programme closes automatically when full" — so
     * three separate things can close it and all three are checked in one
     * place rather than re-derived in the view and again in the controller.
     */
    public static function isOpen(array $programme): bool
    {
        if (($programme['status'] ?? '') !== 'published') {
            return false;
        }
        if (! empty($programme['closes_on']) && $programme['closes_on'] < date('Y-m-d')) {
            return false;
        }
        $capacity = (int) ($programme['capacity'] ?? 0);

        return $capacity === 0 || (int) ($programme['booked'] ?? 0) < $capacity;
    }

    /** Seats left, or null when the programme has no stated capacity. */
    public static function seatsLeft(array $programme): ?int
    {
        $capacity = (int) ($programme['capacity'] ?? 0);

        return $capacity === 0 ? null : max(0, $capacity - (int) ($programme['booked'] ?? 0));
    }
}
