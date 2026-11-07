<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\GisSubmissionResource\Pages;
use Modules\Gis\Models\GisSubmission;
use Modules\Gis\Models\GisMarkaz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Colors\Color;

class GisSubmissionResource extends Resource
{
    protected static ?string $model = GisSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'جملة الطلبات ';
    protected static ?string $modelLabel = 'طلب خدمة';
    protected static ?string $pluralModelLabel = 'سجل المعاملات الجيومكانية';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationGroup = 'الخدمات المكانية والمساحية';

    public static function canAccess(): bool
    {
        // صفحات طلبات الخدمات المكانية تظهر فقط عند «إتاحة التقديم على الخدمات المكانية» (الإعدادات العامة)
        if (! \App\Support\EServices::gisServicesOpen()) {
            return false;
        }
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام', 'مدير الادارة الهندسية']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المعاملة والحالة')
                    ->schema([
                        Forms\Components\TextInput::make('id')->label('رقم المعاملة (UUID)')->disabled()->columnSpan(1),
                        Forms\Components\Select::make('status')
                            ->label('الحالة الإدارية للطلب')
                            ->options([
                                'received' => 'تم الاستلام (جديد)',
                                'processing' => 'قيد المراجعة الفنية',
                                'completed' => 'تم التنفيذ والإغلاق',
                                'rejected' => 'طلب مرفوض',
                            ])->required()->native(false)->columnSpan(1),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة للمواطن')
                            ->placeholder('اكتب هنا أي ملاحظات تظهر للمواطن في صفحة تتبع الطلب...')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Tabs::make('تفاصيل البيانات')->tabs([
                    Forms\Components\Tabs\Tab::make('هوية المتقدم والعنوان')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\KeyValue::make('applicant_info')->label('بيانات صاحب الطلب')->disabled(),
                            Forms\Components\KeyValue::make('address_info')->label('الموقع الجغرافي')->disabled(),
                        ]),
                    Forms\Components\Tabs\Tab::make('البيانات الفنية والمرفقات')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\KeyValue::make('form_data')->label('إجابات الحقول الفنية')->disabled(),
                            Forms\Components\Section::make('روابط المرفقات')
                                ->schema([
                                    // عرض المرفقات بشكل مبسط (يفضل استخدام Infolist للعرض الأفضل)
                                    Forms\Components\Placeholder::make('attachments_list')
                                        ->label('المرفقات المرفوعة')
                                        ->visible(fn ($record) => $record !== null)
                                        ->content(fn($record) => view('filament.gis.components.attachments-viewer', ['record' => $record])),
                                ])
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('كود المعاملة')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->limit(8),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('المواطن')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subService.name')
                    ->label('الخدمة')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('request_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'new' => 'جديد',
                        'restudy' => 'إعادة دراسة',
                        'duplicate' => 'بدل فاقد',
                        default => $state
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'success',
                        'restudy' => 'warning',
                        'duplicate' => 'info',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة الإدارية')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'received' => 'تم الاستلام',
                        'processing' => 'قيد المعالجة',
                        'completed' => 'مكتمل',
                        'rejected' => 'مرفوض',
                        default => $state
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'received' => 'gray',
                        'processing' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('الدفع')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'paid' => 'مسدد',
                        'pending' => 'منتظر',
                        'failed' => 'فشل',
                        default => $state
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // 1. فلتر المراكز (يتم استخلاصها من جدول المراكز)
                Tables\Filters\SelectFilter::make('markaz')
                    ->label('تصفية حسب المركز')
                    // هذا الفلتر يبحث بداخل حقل الـ JSON address_info
                    ->query(
                        fn(Builder $query, array $data) =>
                        $query->when($data['value'], fn($q) => $q->where('address_info->markaz_id', $data['value']))
                    )
                    ->options(GisMarkaz::cachedIdOptions()),

                // 2. فلتر حالة الدفع
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('حالة السداد')
                    ->options([
                        'paid' => 'طلبات مسددة',
                        'pending' => 'بانتظار السداد',
                    ]),

                // 3. فلتر تاريخ التقديم
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(
                        fn(Builder $query, array $data) =>
                        $query->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']))
                    )
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('مراجعة'),
                Tables\Actions\EditAction::make()->label('تحديث'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGisSubmissions::route('/'),
            'create' => Pages\CreateGisSubmission::route('/create'),
            'edit' => Pages\EditGisSubmission::route('/{record}/edit'),
        ];
    }
}
