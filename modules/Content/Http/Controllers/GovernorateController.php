<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\FamousPeople;
use Modules\Content\Models\GovernorateDetail;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    public function about()
    {
        $info = GovernorateDetail::all()->keyBy('key');

        $famousByCat = FamousPeople::orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('governorate.about', compact('info', 'famousByCat'));
    }
}
