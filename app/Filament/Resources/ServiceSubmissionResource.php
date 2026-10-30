<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceSubmissionResource\Pages;
use Modules\Citizen\Models\ServiceSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceSubmissionResource extends Resource
{
    protected static ?string $model = ServiceSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'الخدمات الإلكترونية';
    protected static ?string $modelLabel = 'طلب خدمة';
    protected static ?string $pluralModelLabel = 'طلبات الخدمات';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false; // Submissions come from the frontend only.
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('تحديث الحالة والرد')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('تغيير حالة الطلب')
                                ->options([
                                    'pending'   => 'قيد المراجعة',
                                    'requires_action' => 'يتطلب إجراء من المواطن',
                                    'in_progress' => 'قيد التنفيذ',
                                    'completed' => 'مكتمل',
                                    'rejected'  => 'مرفوض',
                                ])
                                ->required(),

                            Forms\Components\RichEditor::make('admin_notes')
                                ->label('إضافة رد أو ملاحظات للمواطن')
                                ->helperText('هذا الرد سيظهر في لوحة تحكم المواطن.')
                                ->columnSpanFull(),
                        ]),
                    Forms\Components\Wizard\Step::make('تفاصيل الطلب (للقراءة)')
                        ->schema([
                            Forms\Components\ViewField::make('submitted_data_view')->dehydrated(false)
                                ->view('filament.submissions.submitted-data')
                                ->columnSpanFull(),
                        ]),

                ])->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(self::getInfolistSchema());
    }

    private static function getInfolistSchema(bool $inForm = false): array
    {
        // Reusable schema for both Infolist and Form
        $fields = [
            Infolists\Components\Section::make('معلومات الطلب')
                ->schema([
                    Infolists\Components\TextEntry::make('service.title')->label('الخدمة المطلوبة'),
                    Infolists\Components\TextEntry::make('user.name')->label('اسم مقدم الطلب'),
                    Infolists\Components\TextEntry::make('user.national_id')->label('الرقم القومي')->copyable(),
                ])->columns(3),

            Infolists\Components\Section::make('البيانات المُقدمة')
                ->description('هذه هي البيانات التي أدخلها المواطن في نموذج الطلب.')
                ->schema(
                    fn(?ServiceSubmission $record) =>
                    collect($record?->submitted_data ?? [])->map(function ($value, $key) {
                        return Infolists\Components\TextEntry::make("submitted_data.$key")
                            ->label($key)
                            ->placeholder('—');
                    })->values()->all()
                )
                ->columns(3),


            Infolists\Components\Section::make('الردود السابقة')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_notes')->label('ملاحظات الموظف')->markdown()->placeholder('لا يوجد'),
                ])
        ];

        return $inForm ? array_map(fn($item) => $item->inlineLabel(false), $fields) : $fields;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('اسم المواطن')->searchable(),
                Tables\Columns\TextColumn::make('service.title')->label('الخدمة المطلوبة')->badge()->searchable(),
                Tables\Columns\BadgeColumn::make('status')->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'in_progress',
                        'primary' => 'requires_action',
                        'success' => 'completed',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التقديم')->since(),
            ])
            ->filters([ /* ... Filters ... */])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([ /* ... Bulk Actions ... */]);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['pending', 'requires_action'])->count();
        return $count > 0 ? $count : null;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceSubmissions::route('/'),
            'edit' => Pages\EditServiceSubmission::route('/{record}/edit'),
        ];
    }
}
