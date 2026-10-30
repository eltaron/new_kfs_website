<?php

namespace App\Filament\Pages;

use Modules\Content\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'الشؤون الإدارية';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'الإعدادات العامة';
    protected static ?string $title = 'إعدادات الموقع العامة';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];
    public static function canAccess(): bool
    {
        // يمكنك هنا وضع أسماء الأدوار التي يحق لها دخول هذه الصفحة تحديداً
        return auth()->user()->hasAnyRole([
           'super_admin', 
        ]);
    }
    public function mount(): void
    {
        // Load settings from DB. The keys must match the `make()` names in the form schema.
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('معلومات الموقع الأساسية')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')->label('اسم الموقع')->required(),
                                Forms\Components\Textarea::make('site_description')->label('وصف الموقع (لمحركات البحث)')->rows(3),
                                Forms\Components\FileUpload::make('site_logo_header')->label('شعار الهيدر')->image()->directory('settings'),
                                Forms\Components\FileUpload::make('site_logo_footer')->label('شعار الفوتر')->image()->directory('settings'),
                                Forms\Components\FileUpload::make('site_favicon')->label('أيقونة الموقع (Favicon)')->image()->directory('settings'),
                            ]),
                        Forms\Components\Tabs\Tab::make('روابط التواصل الاجتماعي')
                            ->schema([
                                Forms\Components\TextInput::make('social_facebook')->label('رابط فيسبوك')->url(),
                                Forms\Components\TextInput::make('social_twitter')->label('رابط تويتر (X)')->url(),
                                Forms\Components\TextInput::make('social_instagram')->label('رابط انستغرام')->url(),
                                Forms\Components\TextInput::make('social_youtube')->label('رابط يوتيوب')->url(),
                                Forms\Components\TextInput::make('social_tiktok')->label('رابط تيك توك')->url(),
                            ]),
                        Forms\Components\Tabs\Tab::make('نصوص الصفحة الرئيسية')
                            ->schema([
                                Forms\Components\Fieldset::make('قسم الهيرو (Hero Section)')
                                    ->schema([
                                        Forms\Components\TextInput::make('hero_main_title')->label('العنوان الرئيسي'),
                                        Forms\Components\RichEditor::make('hero_subtitle')->label('العنوان الفرعي (يدعم التنسيق)'),
                                        Forms\Components\TextInput::make('hero_search_placeholder')->label('نص مربع البحث'),
                                    ]),
                                Forms\Components\Fieldset::make('قسم الأخبار')
                                    ->schema([
                                        Forms\Components\TextInput::make('news_title')->label('عنوان القسم'),
                                        Forms\Components\TextInput::make('news_subtitle')->label('العنوان الفرعي للقسم'),
                                    ]),
                                // ... أضف Fieldset لكل قسم آخر بنفس الطريقة ...
                                Forms\Components\Fieldset::make('قسم السياحة')
                                    ->schema([
                                        Forms\Components\RichEditor::make('tourism_title')->label('عنوان القسم (يدعم التنسيق)'),
                                        Forms\Components\Textarea::make('tourism_subtitle')->label('العنوان الفرعي'),
                                        Forms\Components\FileUpload::make('tourism_tagline_image')->label('صورة الشعار (كل مكان له حكاية)')->image()->directory('settings'),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('نصوص الفوتر')
                            ->schema([
                                Forms\Components\Textarea::make('footer_description')->label('وصف الفوتر المختصر'),
                                Forms\Components\TextInput::make('copyright_text')->label('نص حقوق النشر'),
                            ]),
                        Forms\Components\Tabs\Tab::make('صفحات عن المحافظة')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Forms\Components\FileUpload::make('governor_page_image')
                                    ->label('صورة صفحة كلمة المحافظ')
                                    ->image()->directory('pages'),

                                Forms\Components\RichEditor::make('governor_page_content')
                                    ->label('محتوى صفحة كلمة المحافظ')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->persistTabInQueryString(), // Remembers the active tab in the URL
            ])
            ->statePath('data');
    }

    // This method defines the buttons at the bottom of the form
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ التغييرات')
                ->submit('save'),
        ];
    }

    // This method is called when the 'save' action is submitted
    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Loop through all form data and update the database
            foreach ($data as $key => $value) {
                // If a FileUpload field is empty, don't overwrite the existing value
                if ($value === null && $this->form->getComponent($key) instanceof Forms\Components\FileUpload) {
                    continue;
                }
                Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
            }

            // Clear the cache to make sure the new settings are loaded on the frontend
            Cache::forget('site_settings');

            Notification::make()
                ->title('تم تحديث الإعدادات بنجاح!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء حفظ الإعدادات.')
                ->body($e->getMessage()) // Optional: show error details for debugging
                ->danger()
                ->send();
        }
    }
}
