<?php

namespace Modules\SiteValidity\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\SiteValidity\Models\CentreSchedule;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Support\SvAccess;

/**
 * أيام نزول المعاينة لكل مركز — يحددها المركز الجيومكاني (ومسؤول الخدمة).
 * منها تُعرض المواعيد المتاحة للمواطن/المهندس عند رفض موعد المعاينة.
 */
class InspectionDaysPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'inspection-days';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'الطلبات';

    protected static ?string $navigationLabel = 'أيام المعاينة للمراكز';

    protected static ?string $title = 'أيام نزول المعاينة لكل مركز';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'sitevalidity::filament.settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return SvAccess::isAdmin($user) || SvAccess::isGeo($user);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $schedules = CentreSchedule::query()->get()->keyBy('center_id');

        $this->form->fill(['centres' => Centre::query()->active()->get(['id', 'name'])->mapWithKeys(fn (Centre $c) => ['c' . $c->id => [
            'weekdays' => array_map('strval', $schedules[$c->id]?->days() ?? []),
            'start_time' => substr((string) ($schedules[$c->id]->start_time ?? '10:00'), 0, 5),
            'daily_capacity' => $schedules[$c->id]->daily_capacity ?? 10,
            'notes' => $schedules[$c->id]->notes ?? null,
        ]])->all()]);
    }

    public function form(Form $form): Form
    {
        $days = array_combine(array_map('strval', array_keys(CentreSchedule::WEEKDAYS)), CentreSchedule::WEEKDAYS);

        return $form
            ->statePath('data')
            ->schema(Centre::query()->active()->get(['id', 'name'])->map(fn (Centre $c) => Forms\Components\Section::make('مركز ' . $c->name)
                ->icon('heroicon-o-map-pin')
                ->collapsible()
                ->columns(4)
                ->schema([
                    Forms\Components\CheckboxList::make("centres.c{$c->id}.weekdays")->label('أيام المعاينة')
                        ->options($days)->columns(7)->columnSpanFull(),
                    Forms\Components\TimePicker::make("centres.c{$c->id}.start_time")->label('ساعة بدء المعاينات')->seconds(false)->required(),
                    Forms\Components\TextInput::make("centres.c{$c->id}.daily_capacity")->label('أقصى عدد معاينات في اليوم')
                        ->numeric()->integer()->minValue(1)->maxValue(200)->required(),
                    Forms\Components\TextInput::make("centres.c{$c->id}.notes")->label('ملاحظات')->maxLength(500)->columnSpan(2),
                ]))->all());
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('حفظ أيام المعاينة')->submit('save')];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = (array) ($this->form->getState()['centres'] ?? []);
        $valid = Centre::query()->active()->pluck('id')->all();

        foreach ($data as $key => $row) {
            $centreId = (int) ltrim((string) $key, 'c');
            if (! in_array($centreId, $valid, true)) {
                continue;
            }
            $days = array_values(array_unique(array_filter(array_map('intval', (array) ($row['weekdays'] ?? [])), fn ($d) => $d >= 0 && $d <= 6)));
            sort($days);
            CentreSchedule::query()->updateOrCreate(['center_id' => $centreId], [
                'weekdays' => $days,
                'start_time' => (string) ($row['start_time'] ?? '10:00'),
                'daily_capacity' => max(1, min(200, (int) ($row['daily_capacity'] ?? 10))),
                'notes' => filled($row['notes'] ?? null) ? trim(strip_tags((string) $row['notes'])) : null,
            ]);
        }

        Notification::make()->success()->title('تم حفظ أيام المعاينة')->send();
    }
}
