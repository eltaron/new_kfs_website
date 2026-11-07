<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\FamousPeople;
use Modules\Content\Models\GovernorateDetail;
use Modules\Content\Models\Markaz;
use Modules\Tourism\Models\CityVillage;
use Modules\Tourism\Models\Landmark;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    public function about()
    {
        $info = GovernorateDetail::all()->keyBy('key');

        $famousByCat = FamousPeople::orderBy('sort_order')
            ->get()
            ->groupBy('category');

        // Key figures strip — counted from live data so they never go stale.
        $figures = array_filter([
            ['value' => Markaz::count(), 'label' => 'مركزًا ومدينة', 'icon' => 'fa-solid fa-city'],
            ['value' => CityVillage::count(), 'label' => 'قرية وتجمعًا سكنيًا', 'icon' => 'fa-solid fa-house-chimney'],
            ['value' => Landmark::count(), 'label' => 'معلمًا سياحيًا وتاريخيًا', 'icon' => 'fa-solid fa-monument'],
            ['value' => $famousByCat->flatten()->count(), 'label' => 'من رموز المحافظة', 'icon' => 'fa-solid fa-user-graduate'],
        ], fn ($f) => $f['value'] > 0);

        return view('governorate.about', compact('info', 'famousByCat', 'figures'));
    }
}
