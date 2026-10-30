<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourBookingResource\Pages;
use Modules\Tourism\Models\TourBooking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TourBookingResource extends Resource
{
    protected static ?string $model = TourBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'الخدمات الإلكترونية';
    protected static ?string $modelLabel = 'حجز شركة سياحية';
    protected static ?string $pluralModelLabel = 'حجوزات الشركات السياحية';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الحجز')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('اسم الشركة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tour_name')
                            ->label('الرحلة السياحية')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('trip_date')
                            ->label('تاريخ الرحلة')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d'),

                        Forms\Components\Select::make('trip_types')
                            ->label('نوع الرحلة')
                            ->multiple()
                            ->options([
                                'أثرى' => 'أثرى',
                                'شاطئ' => 'شاطئ',
                                'ترفيهي' => 'ترفيهي',
                                'تاريخي' => 'تاريخي',
                            ]),

                        Forms\Components\TextInput::make('people_count')
                            ->label('عدد الأفراد')
                            ->numeric()
                            ->minValue(1),

                        Forms\Components\TextInput::make('meals_booking')
                            ->label('حجز وجبات')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('desouk_to_fouh_details')
                            ->label('رحلة نيلية دسوق → فوه')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('edfina_to_desouk_details')
                            ->label('رحلة نيلية إدفينا → دسوق')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('total_cost')
                            ->label('إجمالي التكلفة')
                            ->numeric()
                            ->prefix('ج.م'),
                    ])->columns(2),

                Forms\Components\Section::make('حالة الحجز')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'confirmed' => 'مؤكد',
                                'cancelled' => 'ملغي',
                                'completed' => 'مكتمل',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات إدارية')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('الشركة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tour_name')
                    ->label('الرحلة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trip_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tripTypesList')
                    ->label('نوع الرحلة'),

                Tables\Columns\TextColumn::make('people_count')
                    ->label('عدد الأفراد')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn($record) => $record->statusLabel)
                    ->color(fn($record) => $record->statusBadgeColor),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('التكلفة')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الحجز')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'confirmed' => 'مؤكد',
                        'cancelled' => 'ملغي',
                        'completed' => 'مكتمل',
                    ]),
                Tables\Filters\Filter::make('trip_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('date_to')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('trip_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('trip_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // رابط السداد الإلكتروني الموحد (موقع) — يُرسل للعميل بعد تحديد التكلفة
                Tables\Actions\Action::make('paymentLink')
                    ->label('رابط السداد')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->url(fn ($record): string => $record->checkoutUrl(), shouldOpenInNewTab: true)
                    ->visible(fn ($record): bool => $record->canBePaid()),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTourBookings::route('/'),
            'create' => Pages\CreateTourBooking::route('/create'),
            'edit' => Pages\EditTourBooking::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
}
