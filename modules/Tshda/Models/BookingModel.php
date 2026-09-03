<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table         = 'programme_bookings';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'programme_id', 'reference', 'name', 'nic', 'email', 'phone', 'address',
        'district', 'society', 'participants', 'residential', 'notes', 'locale',
        'status', 'handled_by', 'handled_at', 'officer_note', 'ip_hash',
    ];

    /** @return list<array> */
    public function queue(?string $status = null, ?int $programmeId = null): array
    {
        $this->select('programme_bookings.*, programmes.title AS programme_title, programmes.slug AS programme_slug')
            ->join('programmes', 'programmes.id = programme_bookings.programme_id', 'left')
            ->orderBy('programme_bookings.created_at', 'DESC');

        if ($status !== null && $status !== '') {
            $this->where('programme_bookings.status', $status);
        }
        if ($programmeId !== null) {
            $this->where('programme_bookings.programme_id', $programmeId);
        }

        return $this->findAll();
    }

    public function findByReference(string $reference): ?array
    {
        return $this->where('reference', $reference)->first();
    }
}
