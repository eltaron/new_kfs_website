<?php

namespace App\Filament\Gis\Pages;

use App\Models\GisSubmission;
use App\Models\GisMarkaz;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FinancialReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'منظومة إدارة الخدمات';
    protected static ?string $navigationLabel = 'التقرير المالي الشامل';
    protected static ?string $title = 'التقرير المالي الشامل';
    protected static ?int $navigationSort = 13;

    protected static string $view = 'filament.gis.pages.financial-report';

    public static function shouldRegisterNavigation(): bool { return false; }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $all = GisSubmission::where('payment_status', 'paid')->get();
        $byCenter = $all->groupBy(fn($s) => $s->address_info['center'] ?? 'غير محدد')
            ->map(fn($items, $center) => [
                'center' => $center,
                'count' => $items->count(),
                'total' => $items->sum('total_amount'),
            ])->sortByDesc('total')->values();

        $this->stats = [
            'total_paid' => $all->sum('total_amount'),
            'total_count' => $all->count(),
            'pending_count' => GisSubmission::where('payment_status', 'pending')->count(),
            'by_center' => $byCenter,
            'today' => GisSubmission::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total_amount'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GisSubmission::where('payment_status', 'paid')->with(['user', 'subService'])
            )
            ->columns([
                TextColumn::make('serial_number')->label('رقم الطلب')->searchable(),
                TextColumn::make('subService.name')->label('الخدمة'),
                TextColumn::make('total_amount')->label('المبلغ')->money('EGP')->sortable(),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('date_from')->label('من'),
                        DatePicker::make('date_to')->label('إلى'),
                    ])
                    ->query(
                        fn(Builder $q, array $data) => $q
                            ->when($data['date_from'], fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['date_to'], fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
