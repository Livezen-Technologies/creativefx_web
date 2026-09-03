<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Tshda\Models\OfficeModel;

/**
 * The contact page (Clause 3.9 J): head office and divisional addresses, a map
 * of every office, the feedback route, and the contact details for the
 * Chairman, the Director General and the heads of divisions and regional
 * offices.
 */
class Contact extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        $offices = new OfficeModel();

        return view('Modules\Tshda\Views\contact', [
            'head'            => $offices->head(),
            'offices'         => $offices->live(),
            'mappable'        => $offices->mappable(),
            'title'           => lang('Site.contact.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.contact.meta'),
        ]);
    }
}
