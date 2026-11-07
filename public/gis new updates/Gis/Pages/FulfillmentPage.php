<?php

namespace App\Filament\Gis\Pages;

use App\Models\GisSubmission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FulfillmentPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'منظومة إدارة الخدمات';
    protected static ?string $title = 'الاستيفاء — طلبات تحتاج متابعة';
    protected static ?string $navigationLabel = 'الاستيفاء';
    protected static ?int $navigationSort = 14;

    protected static string $view = 'filament.pages.fulfillment-page';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام', 'مدير الادارة الهندسية']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(GisSubmission::whereIn('fulfillment_status', ['none', 'requested'])->with('subService', 'user'))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->limit(8)->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('المواطن')->searchable(),
                Tables\Columns\TextColumn::make('subService.name')->label('الخدمة')->badge(),
                Tables\Columns\BadgeColumn::make('fulfillment_status')
                    ->label('حالة الاستيفاء')
                    ->colors(['none' => 'gray', 'requested' => 'warning', 'completed' => 'success']),
                Tables\Columns\TextColumn::make('fulfillment_action')->label('الإجراء المطلوب'),
                Tables\Columns\TextColumn::make('fulfillment_reason')->label('السبب')->limit(30),
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
                    ])
                    ->action(function (GisSubmission $record, array $data) {
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
                    ->visible(fn(GisSubmission $r) => $r->fulfillment_status === 'requested')
                    ->action(function (GisSubmission $record) {
                        $record->update([
                            'fulfillment_status' => 'completed',
                            'fulfillment_completed_at' => now(),
                        ]);
                        Notification::make()->title('تم تأكيد إتمام الاستيفاء')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
