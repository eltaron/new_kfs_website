<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\RemovalOrdersAdminResource\Pages;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RemovalOrdersAdminResource extends Resource
{
    protected static ?string $model = RemovalOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?string $navigationLabel = 'استعلام عن القرارات';
    protected static ?string $modelLabel = 'قرار';
    protected static ?string $pluralModelLabel = 'القرارات';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('م'),
                Tables\Columns\TextColumn::make('stop_order_number')->label('رقم القرار')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('owner_name')->label('اسم المالك')->searchable()->sortable()
                    ->limit(20)
                    ->tooltip(fn(RemovalOrder $record) => $record->owner_name),
                Tables\Columns\TextColumn::make('owner_national_id')->label('الرقم القومي للمالك')->searchable(),
                Tables\Columns\TextColumn::make('center')->label('المركز')->badge()->color('info')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('violation_type')->label('النوع')->badge()
                    ->formatStateUsing(fn($state) => $state === 'new_violation' ? 'بدون ترخيص' : 'مخالفة ترخيص')
                    ->color(fn($state) => $state === 'new_violation' ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('stage')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => RemovalOrder::stages()[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        RemovalOrder::STAGE_CREATED => 'gray',
                        RemovalOrder::STAGE_ENGINEERING_REVIEW => 'warning',
                        RemovalOrder::STAGE_SPATIAL_PENDING => 'info',
                        RemovalOrder::STAGE_SPATIAL_DIMENSIONED => 'primary',
                        RemovalOrder::STAGE_PDF_READY => 'secondary',
                        RemovalOrder::STAGE_SYSTEMS_PROCESSING => 'purple',
                        RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL => 'teal',
                        RemovalOrder::STAGE_GIS_PENDING => 'purple',
                        RemovalOrder::STAGE_VISA_PENDING => 'indigo',
                        RemovalOrder::STAGE_COMPLETED => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('systemsSpecialist.name')->label('أخصائي النظم')->searchable()->placeholder('غير معيّن'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime('Y-m-d')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('تفاصيل القرار')
                    ->icon('heroicon-o-eye'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('بيانات القرار')
                    ->schema([
                        Infolists\Components\TextEntry::make('stop_order_number')->label('رقم القرار')->weight('bold'),
                        Infolists\Components\TextEntry::make('stop_order_date')->label('تاريخ قرار الإيقاف')->date(),
                        Infolists\Components\TextEntry::make('announcement_date')->label('تاريخ الإعلان')->date(),
                        Infolists\Components\TextEntry::make('stage')->label('الحالة / المرحلة')->badge()
                            ->formatStateUsing(fn($state) => RemovalOrder::stages()[$state] ?? $state)
                            ->color(fn($state) => $state === RemovalOrder::STAGE_COMPLETED ? 'success' : 'info'),
                        Infolists\Components\TextEntry::make('status')->label('الحالة الحالية')->badge()->color('gray'),
                        Infolists\Components\TextEntry::make('violation_type')->label('النوع')
                            ->formatStateUsing(fn($state) => $state === 'new_violation' ? 'بدون ترخيص' : 'مخالفة ترخيص'),
                        Infolists\Components\TextEntry::make('created_at')->label('تاريخ الإنشاء')->dateTime(),
                    ])->columns(4),

                Infolists\Components\Section::make('بيانات الموقع')
                    ->schema([
                        Infolists\Components\TextEntry::make('center')->label('المركز'),
                        Infolists\Components\TextEntry::make('local_unit')->label('الوحدة المحلية'),
                        Infolists\Components\TextEntry::make('street')->label('الشارع'),
                        Infolists\Components\TextEntry::make('district')->label('الحي'),
                    ])->columns(4),

                Infolists\Components\Section::make('بيانات المالك')
                    ->schema([
                        Infolists\Components\TextEntry::make('owner_name')->label('الاسم'),
                        Infolists\Components\TextEntry::make('owner_national_id')->label('الرقم القومي'),
                        Infolists\Components\TextEntry::make('owner_center')->label('مركز المالك'),
                        Infolists\Components\TextEntry::make('owner_unit')->label('الوحدة المحلية للمالك'),
                        Infolists\Components\TextEntry::make('owner_street')->label('شارع المالك'),
                        Infolists\Components\TextEntry::make('owner_district')->label('حي المالك'),
                        Infolists\Components\TextEntry::make('owner_governorate')->label('المحافظة'),
                    ])->columns(4),

                Infolists\Components\Section::make('بيانات المخالفة')
                    ->schema([
                        Infolists\Components\TextEntry::make('violation_plot')->label('رقم القطعة'),
                        Infolists\Components\TextEntry::make('violation_area')->label('مساحة المخالفة'),
                        Infolists\Components\TextEntry::make('violation_cost')->label('التكلفة المقدرة')->formatStateUsing(fn($state) => $state !== null ? number_format($state) . ' ج.م' : '—'),
                        Infolists\Components\TextEntry::make('violation_report_number')->label('رقم المحضر'),
                        Infolists\Components\TextEntry::make('report_date')->label('تاريخ المحضر')->date(),
                        Infolists\Components\TextEntry::make('violation_works')->label('الأعمال المخالفة')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('licensed_works')->label('الأعمال المرخصة')->columnSpanFull(),
                    ])->columns(4),

                Infolists\Components\Section::make('الحدود والأبعاد الهندسية')
                    ->schema([
                        Infolists\Components\TextEntry::make('border_east')->label('الحد الشرقي'),
                        Infolists\Components\TextEntry::make('dimension_east')->label('البعد الشرقي'),
                        Infolists\Components\TextEntry::make('border_west')->label('الحد الغربي'),
                        Infolists\Components\TextEntry::make('dimension_west')->label('البعد الغربي'),
                        Infolists\Components\TextEntry::make('border_north')->label('الحد الشمالي'),
                        Infolists\Components\TextEntry::make('dimension_north')->label('البعد الشمالي'),
                        Infolists\Components\TextEntry::make('border_south')->label('الحد الجنوبي'),
                        Infolists\Components\TextEntry::make('dimension_south')->label('البعد الجنوبي'),
                        Infolists\Components\TextEntry::make('area')->label('المساحة')->formatStateUsing(fn($state) => $state !== null ? $state . ' م²' : '—'),
                        Infolists\Components\TextEntry::make('engineer_description')->label('توصيف المهندس')->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('المسؤولون عن القرار')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')->label('المنشئ'),
                        Infolists\Components\TextEntry::make('engineeringEngineer.name')->label('مهندس التنظيم'),
                        Infolists\Components\TextEntry::make('spatialMember.name')->label('فني المتغيرات (العضو)'),
                        Infolists\Components\TextEntry::make('systemsSpecialist.name')->label('أخصائي النظم')->placeholder('غير معيّن'),
                        Infolists\Components\TextEntry::make('engineer_name')->label('المهندس المسؤول (المقاول)'),
                        Infolists\Components\TextEntry::make('contractor_name')->label('اسم المقاول'),
                        Infolists\Components\TextEntry::make('review_notes')->label('ملاحظات المراجعة')->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('التصديق النهائي والإخطارات')
                    ->schema([
                        Infolists\Components\TextEntry::make('governor_decision_number')->label('رقم قرار المحافظ'),
                        Infolists\Components\TextEntry::make('governor_decision_date')->label('تاريخ قرار المحافظ')->date(),
                        Infolists\Components\TextEntry::make('citizen_notification_time')->label('وقت إخطار المواطن')->dateTime(),
                        Infolists\Components\TextEntry::make('police_notification_time')->label('وقت إخطار الشرطة')->dateTime(),
                    ])->columns(4),

                Infolists\Components\Section::make('المرفقات')
                    ->schema([
                        Infolists\Components\ImageEntry::make('sketch_file')->label('الكروكي')->width(120),
                        Infolists\Components\ImageEntry::make('photo_file')->label('صورة المخالفة')->width(120),
                        Infolists\Components\TextEntry::make('pdf_file')->label('ملف القرار PDF')
                            ->formatStateUsing(fn($state) => $state ? 'اضغط للعرض' : '—')
                            ->url(fn(RemovalOrder $record) => $record->pdf_file ? \Illuminate\Support\Facades\Storage::url($record->pdf_file) : '#')
                            ->openUrlInNewTab(),
                        Infolists\Components\TextEntry::make('visa_file')->label('ملف التصديق النهائي')
                            ->formatStateUsing(fn($state) => $state ? 'اضغط للعرض' : '—')
                            ->url(fn(RemovalOrder $record) => $record->visa_file ? \Illuminate\Support\Facades\Storage::url($record->visa_file) : '#')
                            ->openUrlInNewTab(),
                    ])->columns(4),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRemovalOrdersAdmin::route('/'),
            'view' => Pages\ViewRemovalOrderAdmin::route('/{record}'),
        ];
    }
}