<?php

namespace App\Filament\Gis\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\RemovalGovernance\Models\RemovalOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemovalReportsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?string $navigationLabel = 'تقارير نشاط القرارات';
    protected static ?string $title = 'تقرير نشاط قرارات الإزالة';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.gis.pages.removal-reports';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin', 'مدير المركز']);
    }

    public function getStats(): array
    {
        $user = auth()->user();
        $query = RemovalOrder::query();

        // مدير المركز يشوف بس مركزه
        if ($user->hasRole('مدير المركز') && $user->center) {
            $query->where('center', $user->center);
        }

        $total = $query->count();
        $newViolations = (clone $query)->where('violation_type', 'new_violation')->count();
        $licensedViolations = (clone $query)->where('violation_type', 'licensed_violation')->count();
        $completed = (clone $query)->where('status', 'تم التنفيذ')->count();
        $inReview = (clone $query)->where('status', 'قيد المراجعة')->count();
        $preparing = (clone $query)->where('status', 'قيد الإعداد')->count();

        return [
            'total' => $total,
            'new_violations' => $newViolations,
            'licensed_violations' => $licensedViolations,
            'completed' => $completed,
            'in_review' => $inReview,
            'preparing' => $preparing,
        ];
    }

    public function getActivityData(): array
    {
        $user = auth()->user();
        $query = RemovalOrder::query();

        if ($user->hasRole('مدير المركز') && $user->center) {
            $query->where('center', $user->center);
        }

        // من أنشأ القرارات
        $createdByUser = (clone $query)
            ->select('created_by', DB::raw('count(*) as total'))
            ->whereNotNull('created_by')
            ->groupBy('created_by')
            ->with('creator:id,name')
            ->get()
            ->map(fn($item) => [
                'name' => $item->creator?->name ?? 'غير محدد',
                'total' => $item->total,
                'action' => 'إنشاء قرارات',
            ]);

        // المهندسين المسؤولين
        $byEngineer = (clone $query)
            ->select('engineering_engineer_id', DB::raw('count(*) as total'))
            ->whereNotNull('engineering_engineer_id')
            ->groupBy('engineering_engineer_id')
            ->with('engineeringEngineer:id,name')
            ->get()
            ->map(fn($item) => [
                'name' => $item->engineeringEngineer?->name ?? 'غير محدد',
                'total' => $item->total,
                'action' => 'مراجعة هندسية',
            ]);

        // فريق المساحة
        $bySpatialManager = (clone $query)
            ->select('spatial_manager_id', DB::raw('count(*) as total'))
            ->whereNotNull('spatial_manager_id')
            ->groupBy('spatial_manager_id')
            ->with('spatialManager:id,name')
            ->get()
            ->map(fn($item) => [
                'name' => $item->spatialManager?->name ?? 'غير محدد',
                'total' => $item->total,
                'action' => 'إدارة مساحية',
            ]);

        $bySpatialMember = (clone $query)
            ->select('spatial_member_id', DB::raw('count(*) as total'))
            ->whereNotNull('spatial_member_id')
            ->groupBy('spatial_member_id')
            ->with('spatialMember:id,name')
            ->get()
            ->map(fn($item) => [
                'name' => $item->spatialMember?->name ?? 'غير محدد',
                'total' => $item->total,
                'action' => 'كشف مساحي',
            ]);

        // محللي النظم
        $bySystems = (clone $query)
            ->select('systems_specialist_id', DB::raw('count(*) as total'))
            ->whereNotNull('systems_specialist_id')
            ->groupBy('systems_specialist_id')
            ->with('systemsSpecialist:id,name')
            ->get()
            ->map(fn($item) => [
                'name' => $item->systemsSpecialist?->name ?? 'غير محدد',
                'total' => $item->total,
                'action' => 'إدخال بيانات',
            ]);

        return [
            'created' => $createdByUser->values(),
            'engineers' => $byEngineer->values(),
            'spatial_managers' => $bySpatialManager->values(),
            'spatial_members' => $bySpatialMember->values(),
            'systems' => $bySystems->values(),
        ];
    }

    public function getStageBreakdown(): array
    {
        $user = auth()->user();
        $query = RemovalOrder::query();

        if ($user->hasRole('مدير المركز') && $user->center) {
            $query->where('center', $user->center);
        }

        return (clone $query)
            ->select('stage', DB::raw('count(*) as total'))
            ->groupBy('stage')
            ->get()
            ->pluck('total', 'stage')
            ->toArray();
    }

    public function getCenterBreakdown(): array
    {
        $user = auth()->user();
        $query = RemovalOrder::query();

        if ($user->hasRole('مدير المركز') && $user->center) {
            $query->where('center', $user->center);
        }

        return (clone $query)
            ->select('center', DB::raw('count(*) as total'))
            ->groupBy('center')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'center')
            ->toArray();
    }

    public function getRecentOrders(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $query = RemovalOrder::query();

        if ($user->hasRole('مدير المركز') && $user->center) {
            $query->where('center', $user->center);
        }

        return $query
            ->with(['creator:id,name', 'engineeringEngineer:id,name'])
            ->latest()
            ->limit(10)
            ->get();
    }
}
