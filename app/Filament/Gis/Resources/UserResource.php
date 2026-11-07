<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\UserResource\Pages;
use App\Models\User;
use Modules\Gis\Models\GisMarkaz;
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

    /**
     * SECURITY: record resolution (edit/delete URLs, bulk actions) must use the
     * same scope as the table. Otherwise a GIS manager could open
     * /gis/users/{id}/edit for a super_admin/Admin account and reset its
     * password or e-mail (privilege escalation). Only super_admin is unscoped;
     * everyone else manages accounts whose roles are all GIS roles.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('super_admin')) {
            return $query;
        }

        return $query
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', static::GIS_ROLES))
            ->whereDoesntHave('roles', fn (Builder $q) => $q->whereNotIn('name', static::GIS_ROLES));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الشخصية والوظيفية')
                    ->description('الرقم القومي اختياري؛ إن أُدخل يجب أن يكون 14 رقماً صحيحاً.')
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
                            ->label('الرقم القومي (14 رقم) — اختياري')
                            ->nullable()
                            ->length(14)
                            ->numeric()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('job_title')
                            ->label('المسمى الوظيفي')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('address')
                            ->label('العنوان')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->helperText('اترك الحقل فارغاً في حالة عدم الرغبة في تغيير كلمة المرور الحالية.'),

                        Forms\Components\Select::make('roles')
                            ->label('الصفة الوظيفية (الدور)')
                            ->relationship(
                                'roles',
                                'name',
                                fn (Builder $query) => $query->whereIn('name', static::GIS_ROLES)
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
                    ->placeholder('بدون مركز')
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
                Tables\Filters\Filter::make('without_center')
                    ->label('موظفون بدون مركز')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where(fn (Builder $q) => $q->whereNull('center')->orWhere('center', ''))),
                Tables\Filters\SelectFilter::make('status')
                    ->label('تصفية بحالة الحساب')
                    ->options([
                        'approved' => 'مقبول (نشط)',
                        'pending' => 'قيد المراجعة',
                        'rejected' => 'مرفوض',
                    ])
                    ->default('approved'),
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
