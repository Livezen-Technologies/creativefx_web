<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

class Auth extends BaseController
{
    public function login()
    {
        return view('Modules\Admin\Views\login');
    }
}
