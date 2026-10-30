<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use Modules\Tourism\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'الخدمات الإلكترونية';
    protected static ?string $modelLabel = 'حجز رحلة';
    protected static ?string $pluralModelLabel = 'الحجوزات';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الحجز')
                    ->schema([
                        Forms\Components\Select::make('landmark_id')
                            ->relationship('landmark', 'name')
                            ->label('المعلم السياحي')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('اسم العميل')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('customer_email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('customer_phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('number_of_people')
                            ->label('عدد الأشخاص')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\DatePicker::make('visit_date')
                            ->label('تاريخ الزيارة')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d'),

                        Forms\Components\TimePicker::make('visit_time')
                            ->label('وقت الزيارة')
                            ->native(false)
                            ->displayFormat('H:i'),

                        Forms\Components\Textarea::make('special_requests')
                            ->label('طلبات خاصة')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('حالة الحجز والدفع')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الحجز')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'confirmed' => 'مؤكد',
                                'cancelled' => 'ملغي',
                                'completed' => 'مكتمل',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\TextInput::make('total_price')
                            ->label('السعر الإجمالي')
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0),

                        Forms\Components\Textarea::make('notes')
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

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('landmark.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('visit_date')
                    ->label('تاريخ الزيارة')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('number_of_people')
                    ->label('عدد الأشخاص')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn($record) => $record->statusLabel)
                    ->color(fn($record) => $record->statusBadgeColor),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('السعر')
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
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('date_to')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
}