<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class StaffModel extends Model
{
    protected $table         = 'staff';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'office_id', 'name', 'designation', 'division', 'subject_area', 'phone',
        'mobile', 'email', 'photo', 'is_senior', 'sort_order', 'status',
    ];

    /**
     * The directory, filtered the way the page filters it (Clause 3.9 E.d:
     * by division, district and subject area). Every filter is optional and
     * they compose, so "Extension officers in Ratnapura" is one call.
     *
     * @return list<array>
     */
    public function directory(array $filters = []): array
    {
        $this->select('staff.*, offices.name AS office_name, offices.district AS office_district, offices.slug AS office_slug')
            ->join('offices', 'offices.id = staff.office_id', 'left')
            ->where('staff.status', 'published');

        if (! empty($filters['office'])) {
            $this->where('offices.slug', $filters['office']);
        }
        if (! empty($filters['district'])) {
            $this->where('offices.district', $filters['district']);
        }
        if (! empty($filters['division'])) {
            $this->like('staff.division', $filters['division']);
        }
        if (! empty($filters['subject'])) {
            $this->like('staff.subject_area', $filters['subject']);
        }
        if (! empty($filters['q'])) {
            $this->groupStart()
                ->like('staff.name', $filters['q'])
                ->orLike('staff.designation', $filters['q'])
                ->orLike('staff.division', $filters['q'])
                ->orLike('staff.subject_area', $filters['q'])
              ->groupEnd();
        }

        return $this->orderBy('staff.is_senior', 'DESC')
            ->orderBy('staff.sort_order', 'ASC')
            ->orderBy('staff.name', 'ASC')
            ->findAll();
    }

    /** @return list<array> */
    public function senior(): array
    {
        return $this->where('status', 'published')->where('is_senior', 1)
            ->orderBy('sort_order', 'ASC')->findAll();
    }
}
