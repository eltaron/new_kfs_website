<?php

namespace App\Http\Controllers;

use App\Models\{User, SyndicateCertificate};
use Modules\Citizen\Models\{Complaint, Suggestion, EmergencyReport, ServiceSubmission};
use Modules\Content\Models\{ExamResult, Post};
use Modules\Gis\Models\GisSubmission;
use Modules\Investment\Models\{InvestmentMessage, CenterGuide, Advertisement};
use Modules\Payments\Models\Transaction;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Modules\Tourism\Models\{Booking, TourBooking};
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardMonitorController extends Controller
{

    public function index()
    {
        // Route is behind auth + can:view-dashboard-monitor; keep a defensive check.
        if (!\Illuminate\Support\Facades\Gate::allows('view-dashboard-monitor')) {
            abort(403, 'ليس لديك صلاحية الوصول لهذه الصفحة');
        }

        $data = [
            // ─── المستخدمين ───
            'totalUsers' => User::count(),
            'usersByCenter' => User::whereNotNull('center')
                ->select('center', DB::raw('count(*) as total'))
                ->groupBy('center')->orderByDesc('total')->get(),
            'usersByStatus' => User::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'recentUsers' => User::latest()->limit(10)->get(),

            // ─── الأدوار والصلاحيات ───
            'totalRoles' => Role::count(),
            'usersPerRole' => DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->select('roles.name as role_name', DB::raw('count(*) as total'))
                ->groupBy('roles.name')->orderByDesc('total')->get(),

            // ─── نظام الجيومكانية ───
            'totalGisSubmissions' => GisSubmission::count(),
            'gisByStatus' => GisSubmission::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'gisByPayment' => GisSubmission::select('payment_status', DB::raw('count(*) as total'))
                ->groupBy('payment_status')->get(),
            'gisByFulfillment' => GisSubmission::select('fulfillment_status', DB::raw('count(*) as total'))
                ->groupBy('fulfillment_status')->get(),
            'gisRevenue' => Transaction::query()->completed()->gis()->sum('amount'),
            'recentGis' => GisSubmission::latest()->limit(10)->get(),

            // ─── قرارات الإزالة ───
            'totalRemovalOrders' => RemovalOrder::count(),
            'removalsByStatus' => RemovalOrder::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'removalsByStage' => RemovalOrder::select('stage', DB::raw('count(*) as total'))
                ->groupBy('stage')->get(),
            'removalsByCenter' => RemovalOrder::select('center', DB::raw('count(*) as total'))
                ->groupBy('center')->orderByDesc('total')->get(),
            'removalsByType' => RemovalOrder::select('violation_type', DB::raw('count(*) as total'))
                ->groupBy('violation_type')->get(),
            'removalsCost' => RemovalOrder::sum('violation_cost'),

            // ─── شهادات النقابة ───
            'totalSyndicateCerts' => SyndicateCertificate::count(),
            'syndicateByType' => SyndicateCertificate::select('type', DB::raw('count(*) as total'))
                ->groupBy('type')->get(),
            // 'totalSyndicateEdits' => SyndicateCertificateEdit::count(),

            // ─── الخدمات ───
            'totalServiceSubmissions' => ServiceSubmission::count(),
            'servicesByStatus' => ServiceSubmission::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'servicesByFulfillment' => ServiceSubmission::select('fulfillment_status', DB::raw('count(*) as total'))
                ->groupBy('fulfillment_status')->get(),
            'servicesRevenue' => ServiceSubmission::where('status', 'completed')->sum('total_amount'),

            // ─── الشكاوي ───
            'totalComplaints' => Complaint::count(),
            'complaintsByStatus' => Complaint::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'recentComplaints' => Complaint::latest()->limit(10)->get(),

            // ─── الاقتراحات ───
            'totalSuggestions' => Suggestion::count(),

            // ─── البلاغات الطارئة ───
            'totalEmergencies' => EmergencyReport::count(),
            'emergenciesByStatus' => EmergencyReport::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'emergenciesByCenter' => EmergencyReport::select('center', DB::raw('count(*) as total'))
                ->groupBy('center')->orderByDesc('total')->get(),

            // ─── الحجوزات ───
            'totalBookings' => Booking::count(),
            'bookingsByStatus' => Booking::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'totalTourBookings' => TourBooking::count(),
            'tourBookingsByStatus' => TourBooking::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),

            // ─── المعاملات المالية ───
            'totalTransactions' => Transaction::count(),
            'transactionsByStatus' => Transaction::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),
            'totalRevenue' => Transaction::where('status', 'completed')->sum('amount'),

            // ─── تعديلات الخطط ───
            // 'totalPlanMods' => DetailedPlanModification::count(),
            // 'planModsByStatus' => DetailedPlanModification::select('status', DB::raw('count(*) as total'))
            //     ->groupBy('status')->get(),

            // ─── الأعلانات ───
            'totalAds' => Advertisement::count(),

            // ─── المنشورات ───
            'totalPosts' => Post::count(),
            'postsByStatus' => Post::select('is_published', DB::raw('count(*) as total'))
                ->groupBy('is_published')->get(),

            // ─── نتائج الامتحانات ───
            'totalExams' => ExamResult::count(),
            'examsByStatus' => ExamResult::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),

            // ─── رسائل الاستثمار ───
            'totalInvestmentMessages' => InvestmentMessage::count(),
            'investmentMsgsByStatus' => InvestmentMessage::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),

            // ─── دليل المراكز ───
            'totalCenterGuides' => CenterGuide::count(),
            'centerGuidesByStatus' => CenterGuide::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->get(),

            // ─── نشاط شهري (آخر 12 شهر) ───
            'monthlyActivity' => $this->getMonthlyActivity(),
        ];

        return view('admin.dashboard-monitor', $data);
    }

    private function getMonthlyActivity()
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'gis' => GisSubmission::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'removals' => RemovalOrder::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'services' => ServiceSubmission::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'complaints' => Complaint::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'emergencies' => EmergencyReport::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
            ]);
        }
        return $months;
    }
}
