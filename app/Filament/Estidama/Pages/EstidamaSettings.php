<?php

namespace App\Filament\Estidama\Pages;

use Modules\Content\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class EstidamaSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.estidama.pages.estidama-settings'; // A view for this specific page
    protected static ?string $navigationGroup = 'إدارة المحتوى';
    protected static ?string $navigationLabel = 'إعدادات صفحة استدامة';
    protected static ?string $title = 'إعدادات صفحة استدامة';

    public static function canAccess(): bool
    {
        return \App\Services\Lms\LmsAccess::isManager(auth()->user());
    }

    public ?array $data = [];

    public function mount(): void
    {
        // Load only settings that start with 'estidama_'
        $settings = Setting::where('key', 'like', 'estidama_%')->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('SettingsTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('عن المركز والرؤية')
                            ->schema([
                                Forms\Components\RichEditor::make('estidama_about')
                                    ->label('نص "عن المركز"')
                                    ->required()->columnSpanFull(),

                                Forms\Components\FileUpload::make('estidama_about_video_poster')
                                    ->label('الصورة المصغرة للفيديو (Poster)')
                                    ->image()->directory('settings'),

                                Forms\Components\TextInput::make('estidama_about_video_url')
                                    ->label('رابط الفيديو (.mp4)')
                                    ->helperText('ارفع الفيديو في public/storage وأدخل المسار هنا.')
                                    ->url(),

                                Forms\Components\RichEditor::make('estidama_vision')
                                    ->label('نص الرؤية')->required(),

                                Forms\Components\RichEditor::make('estidama_mission')
                                    ->label('نص الرسالة')->required(),
                            ]),

                        Forms\Components\Tabs\Tab::make('الأهداف والإحصائيات')
                            ->schema([
                                Forms\Components\RichEditor::make('estidama_strategic_goals')
                                    ->label('الأهداف الاستراتيجية (نص منسق)')
                                    ->required()->columnSpanFull(),

                                Forms\Components\TextInput::make('estidama_capacity')
                                    ->label('الطاقة الاستيعابية (رقم)')
                                    ->numeric()->required(),
                            ]),

                        Forms\Components\Tabs\Tab::make('البنية التحتية')
                            ->schema([
                                Forms\Components\RichEditor::make('estidama_infrastructure')
                                    ->label('نص "البنية التحتية والقدرات الرقمية"')
                                    ->required()->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }
        Cache::forget('site_settings');
        Notification::make()->title('تم تحديث إعدادات استدامة بنجاح!')->success()->send();
    }
}
