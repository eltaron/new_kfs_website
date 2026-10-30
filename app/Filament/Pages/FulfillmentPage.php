<?php

namespace App\Filament\Pages;

use Modules\Gis\Models\GisSubmission;
use Modules\Citizen\Models\ServiceSubmission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FulfillmentPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'الخدمات الإلكترونية';
    protected static ?string $title = 'الاستيفاء — طلبات تحتاج متابعة';
    protected static ?string $navigationLabel = 'الاستيفاء';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.fulfillment-page';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'Admin', 'الإدارة المالية']);
    }

    public function table(Table $table): Table
    {
        $serviceItems = $this->getServiceFulfillments();

        return $table
            ->query(ServiceSubmission::query()->whereIn('id', $serviceItems->pluck('id')))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('المواطن')->searchable(),
                Tables\Columns\TextColumn::make('service.title')->label('الخدمة')->badge(),
                Tables\Columns\BadgeColumn::make('fulfillment_status')
                    ->label('حالة الاستيفاء')
                    ->colors(['none' => 'gray', 'requested' => 'warning', 'completed' => 'success']),
                Tables\Columns\TextColumn::make('fulfillment_action')->label('الإجراء المطلوب'),
                Tables\Columns\TextColumn::make('fulfillment_reason')->label('السبب')->limit(30),
                Tables\Columns\TextColumn::make('fulfillment_fee_amount')->label('رسوم الاستيفاء')->money('EGP')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التقديم')->since(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('request_fulfillment')
                    ->label('طلب استيفاء')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Select::make('fulfillment_action')
                            ->label('الإجراء المطلوب من المواطن')
                            ->options(['payment' => 'دفع رسوم', 'data_correction' => 'تعديل بيانات'])
                            ->required(),
                        Textarea::make('fulfillment_reason')
                            ->label('سبب الاستيفاء')
                            ->required(),
                        TextInput::make('fulfillment_data_fields')
                            ->label('الحقول المطلوب تعديلها (أسماء الحصول مفصولة بفاصلة)')
                            ->visible(fn($get) => $get('fulfillment_action') === 'data_correction'),
                        TextInput::make('fulfillment_fee_amount')
                            ->label('قيمة الرسوم المطلوبة (ج.م)')
                            ->helperText('يسددها المواطن إلكترونياً عبر بوابة e-finance من لوحة التحكم الخاصة به.')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(10000000)
                            ->required(fn($get) => $get('fulfillment_action') === 'payment')
                            ->visible(fn($get) => $get('fulfillment_action') === 'payment'),
                    ])
                    ->action(function (ServiceSubmission $record, array $data) {
                        if ($data['fulfillment_action'] === 'payment') {
                            \Modules\Payments\Models\FulfillmentFee::requestFor($record, $data['fulfillment_fee_amount'], $data['fulfillment_reason'] ?? null, auth()->id());
                        } else {
                            \Modules\Payments\Models\FulfillmentFee::query()
                                ->where('feeable_type', $record->getMorphClass())
                                ->where('feeable_id', (string) $record->getKey())
                                ->where('status', \Modules\Payments\Models\FulfillmentFee::STATUS_PENDING)
                                ->update(['status' => \Modules\Payments\Models\FulfillmentFee::STATUS_CANCELLED]);
                        }
                        $record->update([
                            'fulfillment_status' => 'requested',
                            'fulfillment_action' => $data['fulfillment_action'],
                            'fulfillment_reason' => $data['fulfillment_reason'],
                            'fulfillment_data_fields' => $data['fulfillment_action'] === 'data_correction'
                                ? array_map('trim', explode(',', $data['fulfillment_data_fields'] ?? ''))
                                : null,
                            'fulfillment_requested_by' => auth()->id(),
                            'fulfillment_requested_at' => now(),
                        ]);
                        Notification::make()->title('تم طلب الاستيفاء بنجاح')->success()->send();
                    }),
                Tables\Actions\Action::make('mark_completed')
                    ->label('إتمام الاستيفاء')
                    ->color('success')
                    ->visible(fn(ServiceSubmission $r) => $r->fulfillment_status === 'requested')
                    ->action(function (ServiceSubmission $record) {
                        $record->update([
                            'fulfillment_status' => 'completed',
                            'fulfillment_completed_at' => now(),
                        ]);
                        Notification::make()->title('تم تأكيد إتمام الاستيفاء')->success()->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    private function getServiceFulfillments()
    {
        return ServiceSubmission::whereIn('fulfillment_status', ['none', 'requested'])
            ->with('service', 'user')
            ->latest()
            ->get();
    }
}
