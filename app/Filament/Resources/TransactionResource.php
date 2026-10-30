<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use Modules\Payments\Models\Transaction;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'الشؤون الإدارية';
    protected static ?string $modelLabel = 'معاملة مالية';
    protected static ?string $pluralModelLabel = 'سجل المعاملات المالية';
    protected static ?int $navigationSort = 3;

    public const TYPE_OPTIONS = [
        'service_submission' => 'طلب خدمة إلكترونية',
        'gis_submission' => 'خدمة مكانية',
        'fulfillment_fee' => 'رسوم استيفاء',
        'landmark_booking' => 'حجز معلم سياحي',
        'tour_booking' => 'حجز شركة سياحية',
        'removal_order' => 'غرامة مخالفة',
    ];

    // Transactions are written only by the payment gateway callback.
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $record): bool
    {
        return false;
    }
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('تفاصيل المعاملة')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')->label('رقم المعاملة (UUID)')->copyable(),
                        Infolists\Components\TextEntry::make('description')->label('البيان')->placeholder('—'),
                        Infolists\Components\TextEntry::make('amount')->label('المبلغ')->money('EGP'),
                        Infolists\Components\TextEntry::make('status')->label('الحالة')->badge()
                            ->formatStateUsing(fn (string $state) => Transaction::STATUSES[$state] ?? $state)
                            ->color(fn (string $state) => match ($state) {
                                'completed' => 'success', 'failed' => 'danger', 'expired' => 'gray', default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('failure_reason')->label('سبب الفشل')->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')->label('تاريخ البدء')->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')->label('تاريخ الإتمام')->dateTime()->placeholder('—'),
                        Infolists\Components\TextEntry::make('expires_at')->label('تنتهي صلاحيتها')->dateTime()->placeholder('—'),
                    ])->columns(2),
                Infolists\Components\Section::make('معلومات بوابة الدفع (e-finance)')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_gateway')->label('بوابة الدفع'),
                        Infolists\Components\TextEntry::make('sender_request_number')->label('رقم الطلب لدينا (SenderRequestNumber)')->copyable(),
                        Infolists\Components\TextEntry::make('payment_request_number')->label('رقم المرجع (PaymentRequestNumber)')->copyable()->placeholder('—'),
                        Infolists\Components\TextEntry::make('authorization_code')->label('كود التفويض')->copyable()->placeholder('—'),
                        Infolists\Components\TextEntry::make('gateway_transaction_id')->label('رقم العملية في البوابة')->copyable()->placeholder('—'),
                        Infolists\Components\TextEntry::make('gateway_amount')->label('المبلغ المبلغ عنه من البوابة')->money('EGP')->placeholder('—'),
                        Infolists\Components\TextEntry::make('collection_fees')->label('رسوم التحصيل')->money('EGP')->placeholder('—'),
                    ])->columns(2),
                Infolists\Components\Section::make('معلومات المستفيد')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')->label('اسم المستخدم')->placeholder('— (حجز بدون حساب)'),
                        Infolists\Components\TextEntry::make('user.national_id')->label('الرقم القومي')->placeholder('—'),
                    ])->columns(2),
                Infolists\Components\Section::make('تفاصيل مرتبطة')
                    ->schema([
                        Infolists\Components\TextEntry::make('transactionable_type')
                            ->label('نوع الطلب')
                            ->formatStateUsing(fn (string $state): string => self::TYPE_OPTIONS[$state] ?? class_basename($state)),
                        Infolists\Components\TextEntry::make('transactionable_id')->label('معرف الطلب'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('sender_request_number')
                    ->label('رقم العملية')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transactionable_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => self::TYPE_OPTIONS[$state] ?? class_basename((string) $state)),
                Tables\Columns\TextColumn::make('description')
                    ->label('البيان')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Transaction::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success', 'failed' => 'danger', 'expired' => 'gray', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('payment_request_number')
                    ->label('رقم المرجع')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('تاريخ الدفع')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ البدء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Transaction::STATUSES),
                Tables\Filters\SelectFilter::make('transactionable_type')
                    ->label('النوع')
                    ->options(self::TYPE_OPTIONS),
                Tables\Filters\Filter::make('completed_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('to')->label('إلى تاريخ'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('completed_at', '>=', $d))
                        ->when($data['to'] ?? null, fn ($q, $d) => $q->whereDate('completed_at', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print_receipt')
                    ->label('طباعة إيصال')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Transaction $record): string => route('receipt.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Transaction $record) => $record->status === Transaction::STATUS_COMPLETED),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
