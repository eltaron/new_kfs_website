<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuggestionResource\Pages;
use Modules\Citizen\Models\Suggestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuggestionResource extends Resource
{
    protected static ?string $model = Suggestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'التواصل والشكاوى';
    protected static ?string $modelLabel = 'مقترح';
    protected static ?string $pluralModelLabel = 'المقترحات';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        // Suggestions only come from the public-facing form.
        return false;
    }

    public static function form(Form $form): Form
    {
        // Simplified form for Admin to update the status
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('تغيير حالة المقترح')
                    ->options([
                        'new' => 'جديد',
                        'under_review' => 'قيد المراجعة',
                        'implemented' => 'تم التنفيذ',
                    ])
                    ->required()
                    ->native(false),
            ]);
    }

    // Using an Infolist for the view page to display details cleanly
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('معلومات المُقدِّم')
                ->schema([
                    Infolists\Components\TextEntry::make('name')->label('الاسم'),
                    Infolists\Components\TextEntry::make('email')->label('البريد الإلكتروني')->copyable(),
                ])->columns(2),
            Infolists\Components\Section::make('تفاصيل المقترح')
                ->schema([
                    Infolists\Components\TextEntry::make('subject')->label('عنوان المقترح'),
                    Infolists\Components\TextEntry::make('message')->label('نص المقترح')->markdown()->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('الحالة')
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')->label('تاريخ التقديم')->dateTime(),
                    // Infolists\Components\BadgeEntry::make('status')
                    //     ->label('الحالة الحالية')
                    //     ->colors([
                    //         'gray'    => 'new',
                    //         'warning' => 'under_review',
                    //         'success' => 'implemented',
                    //     ]),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('اسم المُقدِّم')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('subject')->label('عنوان المقترح')->searchable()->limit(50),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray'    => 'new',
                        'warning' => 'under_review',
                        'success' => 'implemented',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التقديم')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['new' => 'جديد', 'under_review' => 'قيد المراجعة', 'implemented' => 'تم التنفيذ']),
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // Notification badge for new suggestions
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'new')->count();
        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success'; // Green badge for new ideas
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuggestions::route('/'),
            'view'  => Pages\ViewSuggestion::route('/{record}'), // View details page
            'edit'  => Pages\EditSuggestion::route('/{record}/edit'), // Edit status page
        ];
    }
}
