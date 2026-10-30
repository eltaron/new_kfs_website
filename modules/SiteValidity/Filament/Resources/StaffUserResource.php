<?php

namespace Modules\SiteValidity\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\SiteValidity\Filament\Resources\StaffUserResource\Pages;
use Modules\SiteValidity\Support\SvAccess;

/**
 * حسابات المستخدمين (المركز الجيومكاني، مهندسو التنظيم، مسؤولو الخدمة) — مسؤول الخدمة فقط.
 * لا يعرض إلا المستخدمين الذين يملكون أحد أدوار الخدمة، ولا يمس أدوارهم الأخرى.
 */
class StaffUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'staff';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'إعدادات الخدمة';

    protected static ?string $navigationLabel = 'حسابات المستخدمين';

    protected static ?string $modelLabel = 'حساب';

    protected static ?string $pluralModelLabel = 'حسابات المستخدمين';

    protected static ?int $navigationSort = 13;

    public static function canViewAny(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    public static function canCreate(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    public static function canEdit(Model $record): bool
    {
        return SvAccess::isAdmin(auth()->user()) && ! $record->hasRole(SvAccess::SUPER);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('roles', fn (Builder $q) => $q->whereIn('name', SvAccess::serviceRoles()));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('الاسم')->required()->maxLength(120),
            Forms\Components\TextInput::make('email')->label('البريد الإلكتروني (اسم الدخول)')->email()->required()->maxLength(190)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('phone')->label('الهاتف')->maxLength(20),
            Forms\Components\TextInput::make('national_id')->label('الرقم القومي')->length(14)->regex('/^\d{14}$/')->unique(ignoreRecord: true),
            Forms\Components\Select::make('sv_role')->label('الدور في الخدمة')->options(SvAccess::ROLE_LABELS)->required()
                ->afterStateHydrated(function (Forms\Components\Select $component, ?User $record) {
                    $component->state($record?->roles->pluck('name')->first(fn ($r) => in_array($r, SvAccess::serviceRoles(), true)));
                })
                ->dehydrated(false),
            Forms\Components\Select::make('status')->label('حالة الحساب')->options(['approved' => 'مفعل', 'inactive' => 'موقوف'])->required()->default('approved'),
            Forms\Components\TextInput::make('password')->label('كلمة المرور')->password()->revealable()
                ->rule(\Illuminate\Validation\Rules\Password::min(10)->letters()->numbers()->mixedCase())
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('اتركها فارغة للإبقاء على كلمة المرور الحالية.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('email')->label('البريد')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label('الدور')->badge()
                    ->formatStateUsing(fn ($state) => SvAccess::ROLE_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->color(fn ($state) => $state === 'approved' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')->label('أُنشئ')->date('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->label('الدور')->options(SvAccess::ROLE_LABELS)
                    ->query(fn (Builder $q, array $data) => filled($data['value'] ?? null) ? $q->whereHas('roles', fn ($r) => $r->where('name', $data['value'])) : $q),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffUsers::route('/'),
            'create' => Pages\CreateStaffUser::route('/create'),
            'edit' => Pages\EditStaffUser::route('/{record}/edit'),
        ];
    }
}
