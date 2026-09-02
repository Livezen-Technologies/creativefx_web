<?php

namespace Modules\Crm\Models;

use CodeIgniter\Model;

class ContactModel extends Model
{
    protected $table         = 'contacts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'email', 'phone', 'subject', 'message', 'locale', 'source', 'status',
        // A room request is a contact message with dates attached; see
        // AddBookingFieldsToContacts. Null for every plain message.
        'room_type', 'check_in', 'check_out', 'adults', 'children'];
}
