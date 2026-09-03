<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Tshda\Models\OfficeModel;
use Modules\Tshda\Models\StaffModel;

/**
 * The staff and contact directory (Clause 3.9 E.d and J.b): searchable and
 * filterable by division, district and subject area.
 *
 * The filters are query parameters and the form submits normally, so a filtered
 * view has its own address a smallholder can bookmark or a regional office can
 * send to somebody — which is the difference between a directory and a widget.
 */
class Directory extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        $offices = new OfficeModel();
        $staff   = new StaffModel();

        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'office'   => trim((string) $this->request->getGet('office')),
            'district' => trim((string) $this->request->getGet('district')),
            'division' => trim((string) $this->request->getGet('division')),
            'subject'  => trim((string) $this->request->getGet('subject')),
        ];

        $officeRows = $offices->live();
        $people     = $staff->directory($filters);

        // The division list is built from the records themselves rather than
        // hard-coded, so a division added in the console appears in the filter
        // without anybody remembering to add it here too.
        $divisions = [];
        foreach ($staff->where('status', 'published')->findAll() as $row) {
            $name = t_field($row['division']);
            if ($name !== '') {
                $divisions[$name] = true;
            }
        }
        $divisions = array_keys($divisions);
        sort($divisions);

        $districts = array_values(array_unique(array_filter(array_column($officeRows, 'district'))));
        sort($districts);

        return view('Modules\Tshda\Views\directory', [
            'offices'         => $officeRows,
            'people'          => $people,
            'divisions'       => $divisions,
            'districts'       => $districts,
            'filters'         => $filters,
            'title'           => lang('Site.directory.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.directory.meta'),
        ]);
    }
}
