<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Investment\Models\InvestmentPlan; // تأكد من وجود الموديل بهذا الاسم

class InvestmentPlanController extends Controller
{
    public function index()
    {
        // جلب كافة الخطط الاستثمارية مرتبة من الأحدث (حسب نطاق السنة)
        $investmentPlans = InvestmentPlan::orderBy('year_range', 'desc')->get();

        // تمرير البيانات للفيو الذي صممناه في الخطوة السابقة
        return view('investment_plans.index', compact('investmentPlans'));
    }
}
