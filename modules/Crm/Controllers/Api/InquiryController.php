<?php

namespace Modules\Crm\Controllers\Api;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Modules\Crm\Models\LeadModel;

/**
 * Product / showroom inquiry endpoint. Stores a lead (source = showroom) from
 * the virtual showroom product panel.
 */
class InquiryController extends ResourceController
{
    use ResponseTrait;

    public function submit()
    {
        if (trim((string) $this->request->getVar('website')) !== '') {
            return $this->respond(['status' => 'success']); // honeypot
        }

        if (! $this->validate([
            'name'  => 'required|min_length[2]|max_length[128]',
            'email' => 'required|valid_email|max_length[191]',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        (new LeadModel())->insert([
            'name'     => $this->request->getVar('name'),
            'email'    => $this->request->getVar('email'),
            'company'  => $this->request->getVar('company'),
            'country'  => $this->request->getVar('country'),
            'phone'    => $this->request->getVar('phone'),
            'interest' => $this->request->getVar('interest'),
            'quantity' => $this->request->getVar('quantity'),
            'message'  => $this->request->getVar('message'),
            'status'   => 'new',
            'source'   => 'showroom',
        ]);

        return $this->respondCreated(['status' => 'success']);
    }
}
