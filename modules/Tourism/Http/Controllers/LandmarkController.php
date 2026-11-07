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

        // «معالم أخرى» — the next landmarks in the admin-defined order, wrapping around.
        $others = Landmark::whereKeyNot($landmark->getKey())->orderBy('order')->get(['id', 'name', 'thumbnail', 'address', 'order']);
        $after = $others->filter(fn ($l) => $l->order >= $landmark->order);
        $related = $after->concat($others->diff($after))->take(3)->values();

        return view('landmarks.show', compact('landmark', 'related'));
    }
}
