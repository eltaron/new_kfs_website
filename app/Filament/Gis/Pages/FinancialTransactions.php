<?php

namespace App\Filament\Gis\Pages;

use Modules\Payments\Models\FulfillmentFee;
use Modules\Gis\Models\GisSubmission;
use Modules\Payments\Models\Transaction;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

/**
 * GIS payment ledger: every gateway attempt for GIS submissions / GIS
 * fulfillment fees (completed ones count as revenue).
 */
class FinancialTransactions extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'منظومة إدارة الخدمات';
    protected static ?string $navigationLabel = 'سجل المعاملات المالية';
    protected static ?string $title = 'سجل المعاملات المالية';
    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.gis.pages.financial-transactions';

    public static function shouldRegisterNavigation(): bool { return false; }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query()->gis()->with(['user', 'transactionable']))
            ->columns([
                TextColumn::make('sender_request_number')->label('رقم العملية')->searchable()->copyable(),
                TextColumn::make('user.name')->label('المواطن')->searchable()->placeholder('—'),
                TextColumn::make('description')->label('البيان')->limit(40)->searchable(),
                TextColumn::make('amount')->label('المبلغ')->money('EGP')->sortable(),
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => Transaction::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success', 'failed' => 'danger', 'expired' => 'gray', default => 'warning',
                    }),
                TextColumn::make('completed_at')->label('تاريخ الدفع')->dateTime('Y-m-d')->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Transaction::STATUSES)
                    ->default(Transaction::STATUS_COMPLETED),
                Filter::make('completed_at')
                    ->form([
                        DatePicker::make('date_from')->label('من تاريخ'),
                        DatePicker::make('date_to')->label('إلى تاريخ'),
                    ])
                    ->query(
                        fn(Builder $q, array $data) => $q
                            ->when($data['date_from'] ?? null, fn($q, $d) => $q->whereDate('completed_at', '>=', $d))
                            ->when($data['date_to'] ?? null, fn($q, $d) => $q->whereDate('completed_at', '<=', $d))
                    ),
            ])
            ->actions([
                Action::make('print_receipt')
                    ->label('طباعة إيصال')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (Transaction $record) => $record->isCompleted())
                    ->url(fn (Transaction $record) => route('receipt.print', $record))
                    ->openUrlInNewTab(),
                Action::make('print_invoice')
                    ->label('طباعة فاتورة')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (Transaction $record) => $record->isCompleted() && $this->gisSubmissionId($record) !== null)
                    ->url(fn (Transaction $record) => route('gis.invoice.print', $this->gisSubmissionId($record)))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('print_bulk')
                        ->label('طباعة فواتير محددة')
                        ->icon('heroicon-o-printer')
                        // A bulk action's url() is evaluated at render time with no selection ($records = null),
                        // so open the print page from the action itself once records are selected.
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $url = route('gis.invoice.print-bulk', [
                                'ids' => $records->map(fn (Transaction $t) => $this->gisSubmissionId($t))->filter()->unique()->join(','),
                            ]);
                            $this->js('window.open(' . json_encode($url) . ', "_blank")');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function gisSubmissionId(Transaction $transaction): ?string
    {
        $payable = $transaction->transactionable;
        if ($payable instanceof FulfillmentFee) {
            $payable = $payable->feeable;
        }

        return $payable instanceof GisSubmission ? (string) $payable->id : null;
    }
}
