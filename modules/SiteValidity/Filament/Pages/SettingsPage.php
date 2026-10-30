<?php

namespace Modules\SiteValidity\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvSettings;
use Modules\SiteValidity\Support\SvStatus;

/** إعدادات الخدمة: نصوص صفحة المواطن، الرسوم، الدفع، الحالات. مسؤول الخدمة فقط. */
class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'settings';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'إعدادات الخدمة';

    protected static ?string $navigationLabel = 'إعدادات الخدمة والنصوص';

    protected static ?string $title = 'إعدادات خدمة صلاحية الموقع';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'sitevalidity::filament.settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $all = SvSettings::all();
        $labels = SvSettings::json('status_labels');
        $colors = SvSettings::json('status_colors');

        $this->form->fill([
            ...array_intersect_key($all, array_flip(['page_title', 'page_kicker', 'page_intro', 'instructions', 'required_documents', 'cost_note', 'success_message', 'default_fee'])),
            'payment_required' => SvSettings::bool('payment_required'),
            'enforce_appointment_time' => SvSettings::bool('enforce_appointment_time'),
            'notify_mail' => SvSettings::bool('notify_mail'),
            'statuses' => collect(SvStatus::all())->mapWithKeys(fn ($s) => [$s => [
                'label' => $labels[$s] ?? SvStatus::LABELS[$s],
                'color' => $colors[$s] ?? SvStatus::COLORS[$s],
            ]])->all(),
        ]);
    }

    public function form(Form $form): Form
    {
        $colors = ['primary' => 'أساسي (زيتوني)', 'success' => 'أخضر', 'warning' => 'ذهبي', 'danger' => 'أحمر', 'info' => 'أزرق', 'gray' => 'رمادي'];

        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('settings')->tabs([
                    Forms\Components\Tabs\Tab::make('صفحة المواطن')->icon('heroicon-o-document-text')->schema([
                        Forms\Components\TextInput::make('page_title')->label('عنوان الخدمة')->required()->maxLength(120),
                        Forms\Components\TextInput::make('page_kicker')->label('العنوان الصغير أعلى العنوان')->maxLength(120),
                        Forms\Components\Textarea::make('page_intro')->label('المقدمة')->rows(3)->maxLength(1500),
                        Forms\Components\Textarea::make('instructions')->label('خطوات التقديم (كل سطر خطوة)')->rows(7)->maxLength(4000),
                        Forms\Components\Textarea::make('required_documents')->label('المستندات المطلوبة (كل سطر مستند)')->rows(4)->maxLength(2000),
                        Forms\Components\Textarea::make('success_message')->label('رسالة نجاح التقديم')->rows(2)->maxLength(500),
                    ]),
                    Forms\Components\Tabs\Tab::make('الرسوم والدفع')->icon('heroicon-o-banknotes')->schema([
                        Forms\Components\TextInput::make('default_fee')->label('الرسوم الافتراضية (ج.م) عند عدم تحديد رسوم لنوع الخدمة')->numeric()->minValue(0)->maxValue(1000000)->required(),
                        Forms\Components\Textarea::make('cost_note')->label('نص توضيح الرسوم للمواطن')->rows(2)->maxLength(800),
                        Forms\Components\Toggle::make('payment_required')->label('السداد إلزامي قبل المعالجة')
                            ->helperText('عند التفعيل لا يستطيع المركز الجيومكاني تحديد موعد المعاينة قبل السداد. الافتراضي: معطل (يظهر للمواطن زر السداد فقط).'),
                    ]),
                    Forms\Components\Tabs\Tab::make('سير العمل والإشعارات')->icon('heroicon-o-bell')->schema([
                        Forms\Components\Toggle::make('enforce_appointment_time')->label('منع تسجيل «تمت المعاينة» قبل يوم الموعد')
                            ->helperText('مسؤول الخدمة مستثنى من هذا القيد.'),
                        Forms\Components\Toggle::make('notify_mail')->label('إرسال الإشعارات بالبريد الإلكتروني أيضاً (إشعارات اللوحة/البوابة تعمل دائماً)'),
                    ]),
                    Forms\Components\Tabs\Tab::make('تسميات الحالات وألوانها')->icon('heroicon-o-swatch')->schema(
                        collect(SvStatus::all())->map(fn ($s) => Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make("statuses.$s.label")->label('تسمية: ' . $s)->required()->maxLength(120),
                            Forms\Components\Select::make("statuses.$s.color")->label('اللون')->options($colors)->required(),
                        ]))->all()
                    ),
                ])->persistTabInQueryString(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('حفظ الإعدادات')->submit('save')];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();

        foreach (['page_title', 'page_kicker', 'page_intro', 'instructions', 'required_documents', 'cost_note', 'success_message'] as $key) {
            SvSettings::set($key, trim(strip_tags((string) ($data[$key] ?? ''))));
        }
        SvSettings::set('default_fee', number_format(max(0, (float) $data['default_fee']), 2, '.', ''));
        foreach (['payment_required', 'enforce_appointment_time', 'notify_mail'] as $key) {
            SvSettings::set($key, ! empty($data[$key]) ? '1' : '0');
        }

        $labels = [];
        $colors = [];
        foreach (SvStatus::all() as $s) {
            $label = trim(strip_tags((string) data_get($data, "statuses.$s.label", '')));
            if ($label !== '' && $label !== SvStatus::LABELS[$s]) {
                $labels[$s] = $label;
            }
            $color = (string) data_get($data, "statuses.$s.color", '');
            if ($color !== '' && $color !== SvStatus::COLORS[$s]) {
                $colors[$s] = $color;
            }
        }
        SvSettings::set('status_labels', $labels ?: '');
        SvSettings::set('status_colors', $colors ?: '');

        Notification::make()->success()->title('تم حفظ إعدادات الخدمة.')->send();
    }
}
