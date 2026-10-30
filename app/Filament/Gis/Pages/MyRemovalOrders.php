<?php

namespace App\Filament\Gis\Pages;

use Modules\RemovalGovernance\Models\RemovalOrder;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class MyRemovalOrders extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?string $navigationLabel = 'قرارات الإزالة الخاصة بي';
    protected static ?string $title = 'قرارات الإزالة الخاصة بي';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.my-removal-orders';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            'super_admin',
            'فني التنظيم',
            'العضو الميداني',
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([
            'super_admin',
            'فني التنظيم',
            'العضو الميداني',
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RemovalOrder::where('created_by', auth()->id())->where('violation_report_file',  null)
            )
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('owner_name')->label('اسم المخالف')->searchable()->limit(20),
                TextColumn::make('street')->label('العنوان')->searchable()->limit(30),
                TextColumn::make('center')->label('المركز'),
                TextColumn::make('violation_type')->label('نوع المخالفة')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'new_violation' ? 'بدون ترخيص' : 'مرخص')
                    ->color(fn($state) => $state === 'new_violation' ? 'danger' : 'warning'),
                TextColumn::make('stage')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => RemovalOrder::stages()[$state] ?? $state),
                TextColumn::make('created_at')->label('تاريخ الإضافة')->dateTime()->sortable(),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('upload_report')
                    ->label('رفع المحضر')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('violation_report_file')
                            ->label('ملف المحضر (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                        Forms\Components\TextInput::make('violation_report_number')
                            ->label('رقم المحضر')
                            ->required(),
                        Forms\Components\DatePicker::make('report_date')
                            ->label('تاريخ المحضر')
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'violation_report_file' => $data['violation_report_file'],
                            'violation_report_number' => $data['violation_report_number'],
                            'report_date' => $data['report_date'],
                        ]);
                        Notification::make()->success()->title('تم رفع المحضر بنجاح')->send();
                    })
                    ->visible(fn(RemovalOrder $record) => $record->created_by === auth()->id()
                        && blank($record->violation_report_file)),
                Action::make('view_report')
                    ->label('عرض المحضر')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn(RemovalOrder $record) => asset('storage/' . $record->violation_report_file))
                    ->openUrlInNewTab()
                    ->visible(fn(RemovalOrder $record) => filled($record->violation_report_file)),
            ]);
    }
}
