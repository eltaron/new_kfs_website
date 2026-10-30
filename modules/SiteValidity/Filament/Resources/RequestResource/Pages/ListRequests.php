<?php

namespace Modules\SiteValidity\Filament\Resources\RequestResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\SiteValidity\Filament\Resources\RequestResource;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

/** قوائم العمل (Queues) حسب المرحلة — كل دور يبدأ على تبويب مهامه. */
class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    /** المفتاح => [التسمية، الحالات، الأيقونة، لون التبويب المعبّر عن المرحلة] */
    public const QUEUES = [
        'appointment' => ['بانتظار تحديد موعد', [SvStatus::SUBMITTED], 'heroicon-o-calendar-days', '#B7791F'],
        'approval' => ['بانتظار الموافقة', [SvStatus::APPOINTMENT_SET], 'heroicon-o-hand-raised', '#147C92'],
        'inspections' => ['المعاينات', [SvStatus::APPROVED], 'heroicon-o-map-pin', '#5B5BD6'],
        'attachments' => ['رفع المرفقات', [SvStatus::INSPECTED], 'heroicon-o-paper-clip', '#00788A'],
        'decision' => ['قرار المهندس', [SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_SUBMITTED], 'heroicon-o-scale', '#C2570C'],
        'citizen' => ['استيفاء لدى المواطن', [SvStatus::COMPLETION_REQUESTED], 'heroicon-o-user', '#A63A32'],
        'done' => ['منتهية', [SvStatus::COMPLETED], 'heroicon-o-check-badge', '#3D7A50'],
        'rejected' => ['مرفوضة', [SvStatus::REJECTED], 'heroicon-o-x-circle', '#78756A'],
    ];

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->label('إضافة طلب جديد')->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $base = SiteValidityRequest::query()->visibleTo($user);

        // «الكل» أولاً (أول تبويب من اليمين) — شكل التبويبات في public/css/sv-panel.css
        $tabs = ['all' => $this->queueTab('الكل', (clone $base)->count(), 'heroicon-o-squares-2x2', '#26302D')];

        foreach (self::QUEUES as $key => [$label, $statuses, $icon, $color]) {
            if ($key === 'appointment' && SvAccess::isEngineer($user) && ! SvAccess::isGeo($user) && ! SvAccess::isAdmin($user)) {
                continue; // المهندس لا يرى الطلبات قبل تحديد الموعد
            }
            $tabs[$key] = $this->queueTab($label, (clone $base)->whereIn('status', $statuses)->count(), $icon, $color)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses));
        }

        return $tabs;
    }

    protected function queueTab(string $label, int $count, string $icon, string $color): Tab
    {
        return Tab::make($label)
            ->icon($icon)
            ->badge((string) $count)
            ->extraAttributes([
                'class' => 'sv-qtab' . ($count === 0 ? ' sv-qtab--empty' : ''),
                'style' => "--sv-c: {$color}",
            ]);
    }

    public function getDefaultActiveTab(): string|int|null
    {
        $user = auth()->user();

        return match (true) {
            SvAccess::isAdmin($user) => 'all',
            SvAccess::isGeo($user) => 'appointment',
            SvAccess::isEngineer($user) => 'approval',
            default => 'all',
        };
    }
}
