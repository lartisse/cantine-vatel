<?php

namespace App\Http\Controllers\ParentArea;

use App\Http\Controllers\Controller;

class ParentController extends Controller
{
    public function home()
    {
        abort(501, 'En cours');
    }
}
