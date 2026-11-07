<?php

namespace App\Filament\Gis\Pages;

use Modules\Payments\Models\FulfillmentFee;
use Modules\Gis\Models\GisMarkaz;
use Modules\Gis\Models\GisSubmission;
use Modules\Payments\Models\Transaction;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

/**
 * GIS revenue report. All figures come from gateway-confirmed (completed)
 * Transactions of GIS submissions and GIS fulfillment fees.
 */
class FinancialReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'منظومة إدارة الخدمات';
    protected static ?string $navigationLabel = 'التقرير المالي الشامل';
    protected static ?string $title = 'التقرير المالي الشامل';
    protected static ?int $navigationSort = 13;

    protected static string $view = 'filament.gis.pages.financial-report';

    public array $stats = [];

    public static function shouldRegisterNavigation(): bool { return false; }

    public static function canAccess(): bool
    {
        // خاصة بطلبات الخدمات المكانية: تظهر فقط عند «إتاحة التقديم على الخدمات المكانية» (الإعدادات العامة)
        if (! \App\Support\EServices::gisServicesOpen()) {
            return false;
        }
        return auth()->user()->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $paid = Transaction::query()->completed()->gis()->with('transactionable')->get();
        $markazNames = GisMarkaz::pluck('name', 'id');

        $byCenter = $paid
            ->groupBy(function (Transaction $t) use ($markazNames) {
                $submission = $t->transactionable instanceof FulfillmentFee ? $t->transactionable->feeable : $t->transactionable;
                $markazId = $submission instanceof GisSubmission ? ($submission->address_info['markaz_id'] ?? null) : null;

                return $markazNames[$markazId] ?? 'غير محدد';
            })
            ->map(fn ($items, $center) => [
                'center' => $center,
                'count' => $items->count(),
                'total' => (float) $items->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $this->stats = [
            'total_paid' => (float) $paid->sum('amount'),
            'total_count' => $paid->count(),
            'pending_count' => GisSubmission::where('payment_status', '!=', 'paid')->where('status', '!=', 'rejected')->count(),
            'by_center' => $byCenter,
            'today' => (float) Transaction::query()->completed()->gis()->whereDate('completed_at', today())->sum('amount'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query()->completed()->gis()->with('user'))
            ->columns([
                TextColumn::make('sender_request_number')->label('رقم العملية')->searchable(),
                TextColumn::make('description')->label('البيان')->limit(40),
                TextColumn::make('user.name')->label('المواطن')->placeholder('—'),
                TextColumn::make('amount')->label('المبلغ')->money('EGP')->sortable(),
                TextColumn::make('completed_at')->label('تاريخ السداد')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('date_from')->label('من'),
                        DatePicker::make('date_to')->label('إلى'),
                    ])
                    ->query(
                        fn(Builder $q, array $data) => $q
                            ->when($data['date_from'] ?? null, fn($q, $d) => $q->whereDate('completed_at', '>=', $d))
                            ->when($data['date_to'] ?? null, fn($q, $d) => $q->whereDate('completed_at', '<=', $d))
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('receipt')
                    ->label('إيصال')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Transaction $record) => route('receipt.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('completed_at', 'desc');
    }
}
