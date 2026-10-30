<?php

namespace App\Filament\Gis\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile;
use Modules\Gis\Models\GisMarkaz;

class GisEditProfile extends EditProfile
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $title = 'الملف الشخصي';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('center')
                    ->label('المركز')
                    ->options(GisMarkaz::cachedOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->helperText('حدد المركز الذي تنتمي إليه.'),

                Forms\Components\Section::make('تغيير كلمة المرور')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور الجديدة')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->helperText('اترك فارغاً لعد التغيير.'),

                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->label('تأكيد كلمة المرور')
                            ->password()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }
}
