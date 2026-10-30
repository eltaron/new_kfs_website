<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\Investment;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function index()
    {
        // Fetch all investments, order by the 'order' column, then paginate.
        $investments = Investment::orderBy('order', 'asc')
            ->paginate(6); // Show 6 investments per page

        return view('investments.index', [
            'investments' => $investments
        ]);
    }

    public function show(Investment $investment)
    {
        return view('investments.show', ['investment' => $investment]);
    }
}
