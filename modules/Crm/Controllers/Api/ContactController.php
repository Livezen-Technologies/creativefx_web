<?php

namespace Modules\Crm\Controllers\Api;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Modules\Crm\Models\ContactModel;

/**
 * Public contact-form endpoint. Validates and stores submissions in the
 * `contacts` table (CRM module). Returns JSON so the front-end can submit
 * without a page reload.
 */
class ContactController extends ResourceController
{
    use ResponseTrait;

    public function submit()
    {
        // Honeypot: bots fill the hidden "website" field. Pretend success.
        if (trim((string) $this->request->getVar('website')) !== '') {
            return $this->respond(['status' => 'success']);
        }

        $rules = [
            'name'    => 'required|min_length[2]|max_length[128]',
            'email'   => 'required|valid_email|max_length[191]',
            'message' => 'required|min_length[5]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model = new ContactModel();
        $model->insert([
            'name'    => $this->request->getVar('name'),
            'email'   => $this->request->getVar('email'),
            'phone'   => $this->request->getVar('phone'),
            'subject' => $this->request->getVar('subject'),
            'message' => $this->request->getVar('message'),
            'locale'  => substr((string) $this->request->getVar('locale'), 0, 5) ?: 'en',
            'source'  => 'contact_form',
            'status'  => 'new',
        ]);

        return $this->respondCreated(['status' => 'success']);
    }
}
