<?php

namespace App\Filament\Widgets;

use Modules\Payments\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Concerns\Widgets\SuperAdminOnly;
use Illuminate\Database\Eloquent\Builder;

class FinancialReportStats extends StatsOverviewWidget
{
    use SuperAdminOnly;

    public ?array $filters = [];

    /** Revenue comes only from gateway-confirmed (completed) transactions. */
    protected function getStats(): array
    {
        $base = fn (string $dateColumn = 'created_at'): Builder => Transaction::query()
            ->when($this->filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate($dateColumn, '>=', $d))
            ->when($this->filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate($dateColumn, '<=', $d));

        $completedRevenue = (float) $base('completed_at')->where('status', Transaction::STATUS_COMPLETED)->sum('amount');
        $completedCount = $base('completed_at')->where('status', Transaction::STATUS_COMPLETED)->count();
        $transactionsCount = $base()
            ->when($this->filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->count();
        $failedTransactions = $base()->whereIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_EXPIRED])->count();

        return [
            Stat::make('إجمالي الإيرادات المحصلة', number_format($completedRevenue, 2) . ' جنيه')
                ->description($completedCount . ' عملية سداد مؤكدة')
                ->color('success'),

            Stat::make('عدد المعاملات', $transactionsCount),

            Stat::make('المعاملات الفاشلة / المنتهية', $failedTransactions)->color('danger'),
        ];
    }
}
