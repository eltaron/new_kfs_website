<?php

namespace App\Filament\Gis\Pages;

use App\Models\GisSubmission;
use App\Models\RemovalOrder;
use App\Models\GisMarkaz;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class GisGeneralReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'منظومة إدارة الخدمات';
    protected static ?string $navigationLabel = 'تقارير إحصائية عامة';
    protected static ?string $title = 'مركز تقارير الإنجاز الجيومكاني';
    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.gis.pages.gis-general-reports';

    // تعريف البيانات التي ستظهر في الصفحة
    public array $stats = [];
    public $recentSubmissions;
    public $removalsByCenter;

    public static function canAccess(): bool
    {
        // يمكنك هنا وضع أسماء الأدوار التي يحق لها دخول هذه الصفحة تحديداً
        return auth()->user()->hasAnyRole([
           'super_admin', 
            "مدير المركز",
            "رؤوساء الاقسام",
        ]);
    }

    public function mount()
    {
        // 1. إحصائيات سريعة
        $this->stats = [
            'total_submissions' => GisSubmission::count(),
            'paid_amount' => GisSubmission::where('payment_status', 'paid')->sum('total_amount'),
            'pending_tasks' => GisSubmission::where('status', 'processing')->count(),
            'removal_orders' => RemovalOrder::count(),
        ];

        // 2. آخر 5 طلبات مقدمة
        $this->recentSubmissions = GisSubmission::with('subService', 'user')
            ->latest()
            ->take(5)
            ->get();

        // 3. توزيع قرارات الإزالة حسب المراكز
        $this->removalsByCenter = RemovalOrder::select('center', DB::raw('count(*) as total'))
            ->groupBy('center')
            ->orderBy('total', 'desc')
            ->get();
    }
}
