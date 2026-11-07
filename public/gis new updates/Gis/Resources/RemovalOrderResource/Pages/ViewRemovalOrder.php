<?php

namespace App\Filament\Gis\Resources\RemovalOrderResource\Pages;

use App\Filament\Gis\Resources\RemovalOrderResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewRemovalOrder extends ViewRecord
{
    protected static string $resource = RemovalOrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('بيانات الموقع 📍')
                    ->schema([
                        Infolists\Components\TextEntry::make('center')->label('المركز'),
                        Infolists\Components\TextEntry::make('local_unit')->label('الوحدة المحلية'),
                        Infolists\Components\TextEntry::make('street')->label('الشارع'),
                        Infolists\Components\TextEntry::make('district')->label('الحي'),
                    ])->columns(4),

                Infolists\Components\Section::make('بيانات المالك 👤')
                    ->schema([
                        Infolists\Components\TextEntry::make('owner_name')->label('الاسم'),
                        Infolists\Components\TextEntry::make('owner_national_id')->label('الرقم القومي'),
                        Infolists\Components\TextEntry::make('owner_governorate')->label('المحافظة'),
                    ])->columns(3),

                Infolists\Components\Section::make('بيانات المخالفة ⚠️')
                    ->schema([
                        Infolists\Components\TextEntry::make('violation_plot')->label('رقم القطعة'),
                        Infolists\Components\TextEntry::make('violation_cost')->label('التكلفة المقدرة')->money('EGP'),
                        Infolists\Components\TextEntry::make('violation_works')->label('الأعمال المخالفة')->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('الحدود والأبعاد الهندسية 📐')
                    ->schema([
                        Infolists\Components\TextEntry::make('border_east')->label('الحد الشرقي'),
                        Infolists\Components\TextEntry::make('dimension_east')->label('البعد الشرقي'),
                    ])->columns(2),
                Infolists\Components\Section::make('')
                    ->schema([
                        Infolists\Components\TextEntry::make('border_west')->label('الحد الغربي'),
                        Infolists\Components\TextEntry::make('dimension_west')->label('البعد الغربي'),
                    ])->columns(2),
                Infolists\Components\Section::make('')
                    ->schema([
                        Infolists\Components\TextEntry::make('border_north')->label('الحد الشمالي'),
                        Infolists\Components\TextEntry::make('dimension_north')->label('البعد الشمالي'),
                    ])->columns(2),
                Infolists\Components\Section::make('')
                    ->schema([
                        Infolists\Components\TextEntry::make('border_south')->label('الحد الجنوبي'),
                        Infolists\Components\TextEntry::make('dimension_south')->label('البعد الجنوبي'),
                    ])->columns(2),
                Infolists\Components\Section::make('بيانات المهندس')
                    ->schema([
                        Infolists\Components\TextEntry::make('area')->label('المساحة')->numeric(decimalPlaces: 2)->suffix(' م²'),
                        Infolists\Components\TextEntry::make('engineer_description')->label('توصيف المهندس')->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('تعيين العضو الميداني / الحالة')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة الحالية')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\ImageEntry::make('photo_file')->label('صورة المخالفة المرفقة'),
                    ])->columns(2)
            ]);
    }
}
