<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use Modules\Citizen\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationGroup = 'الدعم الفني';
    protected static ?string $modelLabel = 'رسالة دعم فني';
    protected static ?string $pluralModelLabel = 'رسائل الدعم الفني';
    protected static ?int $navigationSort = 1;

    // Disable the "Create" button, as messages only come from the frontend.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        // A form for viewing/editing the message details.
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات مقدم الطلب')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('الاسم')->readOnly(),
                        Forms\Components\TextInput::make('email')->label('البريد الإلكتروني')->readOnly(),
                        Forms\Components\TextInput::make('phone')->label('رقم الهاتف')->readOnly(),
                    ])->columns(3),
                Forms\Components\Section::make('تفاصيل طلب الدعم')
                    ->schema([
                        Forms\Components\TextInput::make('subject')->label('عنوان الطلب')->readOnly(),
                        Forms\Components\Textarea::make('message')->label('شرح المشكلة')->rows(10)->readOnly(),
                        Forms\Components\Toggle::make('is_read')->label('تمت قراءتها'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('عنوان الطلب')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('حالة الطلب')
                    ->trueLabel('تمت المعالجة')
                    ->falseLabel('بانتظار المعالجة'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsRead')
                        ->label('تحديد كمعالج')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn($records) => $records->each->update(['is_read' => true])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // A badge in the navigation to show the count of unread messages
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_read', false)->count() ?: null;
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        // Customizing pages to remove 'Create' and add 'View'
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'), // Changed edit to view
        ];
    }
}
