<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DirectoryEntryResource\Pages;
use Modules\Content\Models\DirectoryEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class DirectoryEntryResource extends Resource
{
    protected static ?string $model = DirectoryEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'دليل المدينة';
    protected static ?string $modelLabel = 'رقم هام';
    protected static ?string $pluralModelLabel = 'دليل الهاتف';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الجهة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('رقم الهاتف / الرقم المختصر')
                            ->tel() // Provides a better experience on mobile
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('category')
                            ->label('الفئة (مجموعة العرض)')
                            ->helperText('مثال: أرقام تهمك، شكاوى، أرقام الوزارات')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(0)
                            ->helperText('يستخدم لترتيب الأرقام داخل كل فئة.'),

                        Forms\Components\TextInput::make('icon_class')
                            ->label('اسم أيقونة Font Awesome')
                            ->live(debounce: 300)
                            ->required()
                            ->helperText('مثال: ambulance, fire-extinguisher, bolt'),

                        // Forms\Components\Placeholder::make('icon_preview')
                        //     ->label('معاينة الأيقونة')
                        //     ->content(function ($get) {
                        //         $iconClass = $get('icon_class');
                        //         if (empty($iconClass)) return 'أدخل اسم أيقونة لرؤية المعاينة.';
                        //         try {
                        //             return blade("components.icon", ['name' => 'fas-' . $iconClass, 'class' => 'h-8 w-8 text-primary-500']);
                        //         } catch (\Exception $e) {
                        //             return 'أيقونة غير صالحة.';
                        //         }
                        //     }),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('icon_class')
                //     ->label('الأيقونة')
                //     ->html()
                //     ->formatStateUsing(fn(string $state): HtmlString => new HtmlString('<span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700"><i class="' . e($state) . ' fa-lg"></i></span>')),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الجهة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable() // Add a copy button
                    ->copyMessage('تم نسخ الرقم!'),

                Tables\Columns\TextColumn::make('category')
                    ->label('الفئة')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')->label('الترتيب')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn() => DirectoryEntry::pluck('category', 'category')->unique())
                    ->label('تصفية حسب الفئة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order') // Allow reordering by drag-and-drop
            ->defaultSort('category', 'asc')->defaultSort('order', 'asc');
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
            'index' => Pages\ListDirectoryEntries::route('/'),
            'create' => Pages\CreateDirectoryEntry::route('/create'),
            'edit' => Pages\EditDirectoryEntry::route('/{record}/edit'),
        ];
    }
}
