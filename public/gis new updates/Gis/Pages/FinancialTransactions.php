<?php

namespace App\Filament\Gis\Pages;

use App\Models\GisSubmission;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

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
            ->query(
                GisSubmission::where('payment_status', 'paid')->with(['user', 'subService'])
            )
            ->columns([
                TextColumn::make('serial_number')->label('رقم الطلب')->searchable()->sortable(),
                TextColumn::make('user.name')->label('المواطن')->searchable(),
                TextColumn::make('subService.name')->label('الخدمة')->searchable(),
                TextColumn::make('total_amount')->label('المبلغ')->money('EGP')->sortable(),
                TextColumn::make('created_at')->label('تاريخ الدفع')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('date_from')->label('من تاريخ'),
                        DatePicker::make('date_to')->label('إلى تاريخ'),
                    ])
                    ->query(
                        fn(Builder $q, array $data) => $q
                            ->when($data['date_from'], fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['date_to'], fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ),
                SelectFilter::make('sub_service')
                    ->label('الخدمة')
                    ->relationship('subService', 'name'),
            ])
            ->actions([
                Action::make('print_invoice')
                    ->label('طباعة فاتورة')
                    ->icon('heroicon-o-printer')
                    ->url(fn(GisSubmission $record) => route('gis.invoice.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('print_bulk')
                        ->label('طباعة فواتير محددة')
                        ->icon('heroicon-o-printer')
                        ->url(fn(\Illuminate\Database\Eloquent\Collection $records) => route('gis.invoice.print-bulk', ['ids' => $records->pluck('id')->join(',')]), shouldOpenInNewTab: true),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
