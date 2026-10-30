<?php

namespace Modules\Tourism\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tourism\Models\Landmark;

class LandmarkController extends Controller
{
    public function index()
    {
        $landmarks = Landmark::orderBy('order')->paginate(9);
        return view('landmarks.index', compact('landmarks'));
    }

    public function show(Landmark $landmark)
    {
        $landmark->load('images');
        return view('landmarks.show', compact('landmark'));
    }
}
