<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;

class AccountController extends Controller
{
    public function index()
    {
        abort(501, 'En cours');
    }
}
