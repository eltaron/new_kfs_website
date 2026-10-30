<?php

namespace App\Filament\Pages;

use App\Filament\Exports\TransactionExporter;
use Modules\Payments\Models\Transaction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class FinancialReport extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithHeaderActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'الشؤون الإدارية';
    protected static ?string $navigationLabel = 'التقرير المالي';
    protected static ?string $title = 'التقرير المالي الشامل';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.financial-report';

    public ?array $filterData = [];

    public function mount(): void
    {
        $this->form->fill();
    }
     public static function canAccess(): bool
    {
        // يمكنك هنا وضع أسماء الأدوار التي يحق لها دخول هذه الصفحة تحديداً
        return auth()->user()->hasAnyRole([
           'super_admin', 
            "الإدارة المالية",
        ]);
    }
    /**
     * 🔹 Filters Form (Top of Page)
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('date_from')->label('من تاريخ'),
                        DatePicker::make('date_to')->label('إلى تاريخ'),
                        Select::make('status')
                            ->label('حالة المعاملات')
                            ->options(Transaction::STATUSES),
                    ]),
                ])
                ->statePath('filterData')
                ->live(),
        ];
    }

    /**
     * 🔹 Header Actions (Export)
     */
    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make('export')
                ->label('تصدير التقرير (Excel)')
                ->exporter(TransactionExporter::class),
        ];
    }

    /**
     * 🔹 Header Stats
     */
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\FinancialReportStats::class,
        ];
    }

    /** Pass the page filters to the stats widget. */
    public function getWidgetData(): array
    {
        return ['filters' => $this->filterData ?? []];
    }

    /**
     * 🔹 Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilteredTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('sender_request_number')->label('رقم العملية')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('user.name')->label('المستخدم')->placeholder('—'),
                Tables\Columns\TextColumn::make('transactionable_type')->label('النوع')->badge()
                    ->formatStateUsing(fn (?string $state) => \App\Filament\Resources\TransactionResource::TYPE_OPTIONS[$state] ?? $state),
                Tables\Columns\TextColumn::make('description')->label('البيان')->limit(40)->toggleable(),
                Tables\Columns\TextColumn::make('amount')->label('المبلغ')->money('EGP')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => Transaction::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success', 'failed' => 'danger', 'expired' => 'gray', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('completed_at')->label('تاريخ الإتمام')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('إيصال')
                    ->icon('heroicon-o-printer')
                    ->url(fn($record) => route('receipt.print', $record))
                    ->visible(fn($record) => $record->status === Transaction::STATUS_COMPLETED)
                    ->openUrlInNewTab(),
            ]);
    }

    /**
     * 🔹 Filtered Query
     */
    public function getFilteredTableQuery(): Builder
    {
        return Transaction::query()
            ->with('user')
            ->when(
                $this->filterData['date_from'] ?? null,
                fn($q, $date) => $q->whereDate('completed_at', '>=', $date)
            )
            ->when(
                $this->filterData['date_to'] ?? null,
                fn($q, $date) => $q->whereDate('completed_at', '<=', $date)
            )
            ->when(
                $this->filterData['status'] ?? null,
                fn($q, $status) => $q->where('status', $status)
            );
    }
}
