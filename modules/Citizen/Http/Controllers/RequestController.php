<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
class RequestController extends Controller
{
    public function index()
    {
        return view('requests.index');
    }
}
