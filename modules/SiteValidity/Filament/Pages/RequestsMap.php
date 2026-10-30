<?php

namespace Modules\SiteValidity\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Modules\SiteValidity\Filament\Resources\RequestResource;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Models\Geo\Village;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvRequestFilters;
use Modules\SiteValidity\Support\SvStatus;

/**
 * خريطة طلبات صلاحية الموقع على مراكز محافظة كفر الشيخ — لمسؤول الخدمة:
 * فلتر متقدم + بحث (يحدّث الخريطة والمؤشرات والجدول معاً) + تقرير مطبوع بنفس الفلاتر.
 */
class RequestsMap extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $view = 'sitevalidity::filament.requests-map';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'الطلبات';

    protected static ?string $navigationLabel = 'خريطة الطلبات';

    protected static ?string $title = 'خريطة طلبات صلاحية الموقع';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'requests-map';

    /** @var array<string, mixed> */
    public array $filters = [];

    public static function canAccess(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill(SvRequestFilters::normalize([]));
    }

    public function form(Form $form): Form
    {
        return $form->statePath('filters')->schema([
            Forms\Components\Section::make('بحث وفلتر متقدم')
                ->icon('heroicon-o-funnel')
                ->collapsible()
                ->compact()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                ->schema([
                    Forms\Components\TextInput::make('search')->label('بحث')
                        ->placeholder('رقم الطلب، الاسم، الرقم القومي، الهاتف، العنوان…')
                        ->prefixIcon('heroicon-m-magnifying-glass')
                        ->live(debounce: 500)
                        ->columnSpan(['sm' => 2]),
                    Forms\Components\Select::make('statuses')->label('الحالة')->multiple()->native(false)
                        ->options(SvRequestFilters::STATUS_SHORT)->live(),
                    Forms\Components\Select::make('service_types')->label('نوع الخدمة')->multiple()->native(false)
                        ->options(fn () => ServiceType::query()->orderBy('sort_order')->pluck('name', 'id'))->live(),
                    Forms\Components\Select::make('center_id')->label('المركز')->searchable()->native(false)
                        ->options(fn () => Centre::query()->active()->orderBy('sort_order')->pluck('name', 'id'))
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('village_id', null)),
                    Forms\Components\Select::make('village_id')->label('المدينة / القرية')->searchable()->native(false)
                        ->options(fn (Forms\Get $get) => $get('center_id') ? Village::query()->where('center_id', $get('center_id'))->orderBy('sort_order')->orderBy('name')->pluck('name', 'id') : [])
                        ->disabled(fn (Forms\Get $get) => ! $get('center_id'))
                        ->placeholder(fn (Forms\Get $get) => $get('center_id') ? 'الكل' : 'اختر المركز أولاً')
                        ->live(),
                    Forms\Components\DatePicker::make('from')->label('من تاريخ')->native(false)->displayFormat('Y-m-d')->live(),
                    Forms\Components\DatePicker::make('to')->label('إلى تاريخ')->native(false)->displayFormat('Y-m-d')->live(),
                    Forms\Components\Select::make('paid')->label('السداد')->native(false)->placeholder('الكل')
                        ->options(['paid' => 'مدفوع', 'unpaid' => 'غير مدفوع'])->live(),
                    Forms\Components\Select::make('source')->label('مصدر الطلب')->native(false)->placeholder('الكل')
                        ->options(['portal' => 'البوابة (المواطن)', 'tech' => 'المركز التكنولوجي'])->live(),
                    Forms\Components\Toggle::make('overdue')->label('المواعيد المتأخرة فقط')->inline(false)->live(),
                ]),
        ]);
    }

    public function updated(string $name): void
    {
        if (str_starts_with($name, 'filters')) {
            $this->resetTable();
            $this->dispatch('sv-map-data', data: $this->mapData());
        }
    }

    public function toggleStatus(string $status): void
    {
        $statuses = (array) ($this->filters['statuses'] ?? []);
        $this->filters['statuses'] = in_array($status, $statuses, true)
            ? array_values(array_diff($statuses, [$status]))
            : [...$statuses, $status];
        $this->updated('filters.statuses');
    }

    public function setCentre(?int $id): void
    {
        $this->filters['center_id'] = $id ?: null;
        $this->filters['village_id'] = null;
        $this->updated('filters.center_id');
    }

    public function resetFilters(): void
    {
        $this->form->fill(SvRequestFilters::normalize([]));
        $this->updated('filters');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('طباعة التقرير')->icon('heroicon-o-printer')->color('gray')
                ->url(fn () => route('site-validity.reports.map', [
                    ...array_filter(SvRequestFilters::normalize($this->filters), fn ($v) => $v !== null && $v !== '' && $v !== [] && $v !== false),
                    'autoprint' => 1,
                ]))
                ->openUrlInNewTab(),
            Action::make('reset')->label('مسح الفلاتر')->icon('heroicon-o-x-mark')->color('gray')->action('resetFilters'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => SvRequestFilters::query($this->filters)->with('serviceType'))
            ->heading('الطلبات المطابقة')
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('رقم الطلب')->weight('bold')->sortable()->copyable(),
                Tables\Columns\TextColumn::make('applicant_name')->label('مقدم الطلب')->limit(26),
                Tables\Columns\TextColumn::make('serviceType.name')->label('الخدمة')->limit(24),
                Tables\Columns\TextColumn::make('center_name')->label('الموقع')
                    ->formatStateUsing(fn ($state, SiteValidityRequest $record) => $record->locationLabel()),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => SvStatus::label($state))
                    ->color(fn (string $state) => SvStatus::color($state)),
                Tables\Columns\IconColumn::make('paid_at')->label('مدفوع')->boolean()
                    ->state(fn (SiteValidityRequest $record) => $record->paid_at !== null),
                Tables\Columns\TextColumn::make('created_at')->label('التقديم')->date('Y-m-d')->sortable(),
            ])
            ->recordUrl(fn (SiteValidityRequest $record) => RequestResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('لا توجد طلبات مطابقة')
            ->emptyStateDescription('غيّر الفلاتر أو امسحها لعرض كل الطلبات.');
    }

    /** بيانات الخريطة والمؤشرات للفلاتر الحالية. */
    public function mapData(): array
    {
        $query = SvRequestFilters::query($this->filters);
        $centres = SvRequestFilters::byCentre($query);

        $selected = (int) ($this->filters['center_id'] ?? 0) ?: null;

        return [
            'centres' => $centres->keyBy('name')->all(),
            'statuses' => SvRequestFilters::byStatus($query),
            'total' => $centres->sum('total'),
            'selected' => $selected,
            // نقاط طلبات المركز المختار (بحد أقصى 500) — بدون إحداثيات تُرسم حول المركز وتُعلَّم «الموقع غير محدد»
            'points' => $selected ? $this->points($query) : [],
        ];
    }

    /** @return list<array<string, mixed>> */
    protected function points($query): array
    {
        return (clone $query)->latest('id')->limit(500)
            ->get(['id', 'number', 'applicant_name', 'status', 'village_name', 'center_name', 'latitude', 'longitude'])
            ->map(fn (SiteValidityRequest $r) => [
                'id' => $r->id,
                'number' => $r->number,
                'name' => $r->applicant_name,
                'village' => $r->village_name,
                'status' => $r->status,
                'label' => SvRequestFilters::STATUS_SHORT[$r->status] ?? $r->status,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'url' => RequestResource::getUrl('view', ['record' => $r]),
            ])->all();
    }

    protected function getViewData(): array
    {
        $centre = (int) ($this->filters['center_id'] ?? 0);
        $query = SvRequestFilters::query($this->filters);

        return [
            'data' => $this->mapData(),
            'villages' => $centre ? SvRequestFilters::byVillage($query) : collect(),
            'centreIds' => Centre::query()->pluck('id', 'name')->all(),
            'statusHex' => SvRequestFilters::STATUS_HEX,
            'statusShort' => SvRequestFilters::STATUS_SHORT,
            'activeStatuses' => (array) ($this->filters['statuses'] ?? []),
            'mapConfig' => [
                'boundaries' => asset((string) config('site-validity.map.boundaries')),
                'center' => config('site-validity.map.center'),
                'zoom' => (int) config('site-validity.map.zoom', 10),
                'points' => (array) config('site-validity.map.points', []),
            ],
        ];
    }
}
