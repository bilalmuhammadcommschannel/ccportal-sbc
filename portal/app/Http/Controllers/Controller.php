<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    // Laravel 12 ships a bare base controller; opt back into the traits the
    // app relies on. AuthorizesRequests gives $this->authorize() (default-deny
    // policy enforcement); ValidatesRequests gives $this->validate().
    use AuthorizesRequests, ValidatesRequests;
}
