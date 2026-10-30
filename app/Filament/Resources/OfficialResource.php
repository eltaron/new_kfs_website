<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficialResource\Pages;
use App\Filament\Resources\OfficialResource\RelationManagers;
use Modules\Content\Models\Official;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OfficialResource extends Resource
{
    protected static ?string $model = Official::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'عن المحافظة';
    protected static ?string $modelLabel = 'مسؤول';
    protected static ?string $pluralModelLabel = 'قيادات المحافظة';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المسؤول')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image')
                            ->label('صورة شخصية')
                            ->image()
                            ->directory('officials')
                            ->imageEditor()
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('bio')
                            ->label('السيرة الذاتية (المؤهلات، الخبرات...)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('صورة')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                // تعديل عرض الدور ليظهر باللغة العربية مع الألوان
                Tables\Columns\TextColumn::make('roles')
                    ->label('الدور الوظيفي')
                    ->formatStateUsing(function ($state) {
                        $currentRole = $state->firstWhere('is_current', true);
                        if (!$currentRole) return 'سابق';

                        // خريطة تحويل المسميات من الإنجليزية للعربية
                        return match ($currentRole->role_name) {
                            'governor' => 'محافظ كفر الشيخ',
                            'deputy_governor' => 'نائب المحافظ',
                            'secretary_general' => 'السكرتير العام',
                            'assistant_secretary_general' => 'السكرتير العام المساعد',
                            default => $currentRole->role_name,
                        };
                    })
                    ->badge()
                    ->color(function ($state) {
                        $currentRole = $state->firstWhere('is_current', true);
                        return match ($currentRole?->role_name) {
                            'governor' => 'success', // أخضر
                            'deputy_governor' => 'info', // أزرق سماوي
                            'secretary_general' => 'warning', // برتقالي
                            default => 'gray', // رمادي للسابقين
                        };
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // إضافة الفلتر بالأسماء العربية
                Tables\Filters\SelectFilter::make('role')
                    ->label('تصفية حسب المنصب')
                    ->options([
                        'governor' => 'المحافظين',
                        'deputy_governor' => 'نواب المحافظ',
                        'secretary_general' => 'سكرتير عام',
                        'assistant_secretary_general' => 'سكرتير عام مساعد',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        // الفلترة بناءً على العلاقة مع جدول الأدوار
                        return $query->whereHas('roles', function ($query) use ($data) {
                            $query->where('role_name', $data['value']);
                        });
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // To manage the roles of this official on their edit page
            RelationManagers\RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfficials::route('/'),
            'create' => Pages\CreateOfficial::route('/create'),
            'edit' => Pages\EditOfficial::route('/{record}/edit'),
        ];
    }
}
