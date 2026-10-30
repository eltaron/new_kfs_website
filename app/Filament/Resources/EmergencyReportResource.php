<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmergencyReportResource\Pages;
use Modules\Citizen\Models\EmergencyReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class EmergencyReportResource extends Resource
{
    protected static ?string $model = EmergencyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'التواصل والشكاوى';
    protected static ?string $modelLabel = 'بلاغ طارئ';
    protected static ?string $pluralModelLabel = 'بلاغات الطوارئ (سيطرة)';
    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false; // Reports come from the frontend
    }

    // A simplified form for Admins to primarily update the status
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('تغيير حالة البلاغ')
                    ->options([
                        'new' => 'جديد',
                        'dispatched' => 'تم توجيه جهة مختصة',
                        'resolved' => 'تم الحل',
                    ])
                    ->required()->native(false),
            ]);
    }

    // Using an Infolist for the View page for a clean display of submitted data
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('معلومات البلاغ')
                ->schema([
                    Infolists\Components\TextEntry::make('id')->label('رقم البلاغ'),
                    // Infolists\Components\BadgeEntry::make('status')->label('الحالة')
                    //     ->colors(['primary' => 'new', 'warning' => 'dispatched', 'success' => 'resolved']),
                    Infolists\Components\TextEntry::make('report_type')->label('نوع البلاغ'),
                    Infolists\Components\TextEntry::make('created_at')->label('تاريخ البلاغ')->dateTime(),
                ])->columns(2),

            Infolists\Components\Section::make('معلومات المُبلغ')
                ->schema([
                    Infolists\Components\TextEntry::make('reporter_name')->label('الاسم'),
                    Infolists\Components\TextEntry::make('reporter_national_id')->label('الرقم القومي')->copyable(),
                    Infolists\Components\TextEntry::make('reporter_phone')->label('رقم الهاتف')->copyable(),
                ])->columns(3),

            Infolists\Components\Section::make('تفاصيل وموقع الحادث')
                ->schema([
                    Infolists\Components\TextEntry::make('center')->label('المركز'),
                    Infolists\Components\TextEntry::make('area')->label('المدينة / القرية'),
                    Infolists\Components\TextEntry::make('location_description')->label('الوصف التفصيلي للمكان')->markdown()->columnSpanFull(),
                    Infolists\Components\TextEntry::make('google_maps_link')
                        ->label('الموقع على الخريطة')
                        ->html()
                        ->formatStateUsing(function ($record) {
                            if (!$record->latitude || !$record->longitude) return new HtmlString('<span class="text-gray-500">لم يتم إرفاق إحداثيات.</span>');
                            $url = "https://www.google.com/maps?q={$record->latitude},{$record->longitude}";
                            return new HtmlString("<a href='{$url}' target='_blank' class='text-primary-600 hover:underline'>فتح في خرائط Google <i class='fas fa-external-link-alt'></i></a>");
                        })->columnSpanFull(),
                ])->columns(2),

            Infolists\Components\Section::make('الرسالة والمرفقات')
                ->schema([
                    Infolists\Components\TextEntry::make('details')->label('نص البلاغ')->markdown()->columnSpanFull(),
                    Infolists\Components\ImageEntry::make('attachments')->label('المرفقات')
                        ->state(fn($record) => \App\Support\SecureUploads::url($record, 'attachments')) // private uploads via authorized route
                        ->columnSpanFull()->stacked(false), // ImageEntry has no grid() in Filament 3
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reporter_name')->label('المُبلغ')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('report_type')->label('نوع البلاغ')->searchable(),
                Tables\Columns\TextColumn::make('center')->label('المركز')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'primary',
                        'dispatched' => 'warning',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('وقت البلاغ')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['new' => 'جديد', 'dispatched' => 'تم التوجيه', 'resolved' => 'تم الحل']),
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
                Tables\Actions\EditAction::make()->label('تغيير الحالة'),
            ])
            ->bulkActions([/* ... */])
            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'new')->count();
        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmergencyReports::route('/'),
            'view' => Pages\ViewEmergencyReport::route('/{record}'),
            'edit' => Pages\EditEmergencyReport::route('/{record}/edit'),
        ];
    }
}
