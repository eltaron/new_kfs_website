<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\GisMarkaz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'إعدادات النظام والأمان';
    protected static ?string $navigationLabel = 'إدارة الموظفين';
    protected static ?string $modelLabel = 'موظف / مستخدم';
    protected static ?string $pluralModelLabel = 'سجل الموظفين والصلاحيات';
    protected static ?int $navigationSort = 101;

    const GIS_ROLES = [
        'مدير المركز',
        'مدير الادارة الهندسية',
        'مهندس التنظيم',
        'مدير التنظيم',
        'فني التنظيم',
        'مدير الوحدة الفرعية',
        'العضو الميداني',
        'مدخل البيانات بالوحدة الفرعية',
        'محللي النظم',
        'الدعم الاداري',
        'رؤوساء الاقسام',
        'مدير المتغيرات',
        'عضو المتغيرات',
        'محلل نظم',
        'مكتب المحافظ',
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الشخصية والوظيفية')
                    ->description('تأكد من إدخال الرقم القومي الصحيح لربط الموظف بسجل شؤون العاملين.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني المهني')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('national_id')
                            ->label('الرقم القومي (14 رقم)')
                            ->required()
                            ->length(14)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->helperText('اترك الحقل فارغاً في حالة عدم الرغبة في تغيير كلمة المرور الحالية.'),

                        Forms\Components\Select::make('roles')
    ->label('الصفة الوظيفية (الدور)')
    ->options(
        \Spatie\Permission\Models\Role::query()
            ->whereIn('name', static::GIS_ROLES)
            ->pluck('name', 'id')
            ->toArray()
    )
    ->multiple()
    ->preload()
    ->required()
    ->native(false),

                        Forms\Components\Select::make('center')
                            ->label('المركز')
                            ->options(GisMarkaz::cachedOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText('حدد المركز الذي ينتمي إليه هذا الموظف.'),

                        Forms\Components\Select::make('status')
                            ->label('حالة الحساب')
                            ->options([
                                'approved' => 'مقبول (نشط)',
                                'active' => 'نشط',
                                'pending' => 'قيد المراجعة',
                                'rejected' => 'مرفوض',
                            ])
                            ->default('approved')
                            ->required()
                            ->native(false)
                            ->helperText('مقبول (نشط): يمكنه الدخول على اللوحة.'),

                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->whereHas(
                    'roles',
                    fn(Builder $roleQuery) =>
                    $roleQuery->whereIn('name', static::GIS_ROLES)
                )->latest('created_at');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الموظف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('national_id')
                    ->label('الرقم القومي')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الصفة الوظيفية')
                    ->badge(),

                Tables\Columns\TextColumn::make('center')
                    ->label('المركز')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('حالة الحساب')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'approved', 'active' => 'مقبول (نشط)',
                        'pending' => 'قيد المراجعة',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'approved', 'active' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('تصفية بالصفة الوظيفية')
                    ->relationship(
                        'roles',
                        'name',
                        fn(Builder $query) =>
                        $query->whereIn('name', static::GIS_ROLES)
                    ),
                Tables\Filters\SelectFilter::make('center')
                    ->label('تصفية بالمركز')
                    ->options(GisMarkaz::cachedOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('تصفية بحالة الحساب')
                    ->options([
                        'approved' => 'مقبول (نشط)',
                        'pending' => 'قيد المراجعة',
                        'rejected' => 'مرفوض',
                    ])
                    ,
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
