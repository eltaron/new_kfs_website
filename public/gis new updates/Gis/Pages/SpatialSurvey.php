<?php

namespace App\Filament\Gis\Pages;

use App\Models\RemovalOrder;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class SpatialSurvey extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'التسجيل المكاني';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static string $view = 'filament.gis.pages.spatial-survey';
    protected static ?int $navigationSort = 2;

    public ?RemovalOrder $removalOrder = null;
    public ?string $polygon_geojson = null;
    public ?string $spatial_notes = null;
    public bool $showMap = false;

    // إخفاء الصفحة من القائمة الجانبية لمنع الدخول بدون ID
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'عضو المتغيرات', 'Admin']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin', 'عضو المتغيرات', 'مدير المتغيرات']);
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $url = parent::getUrl([], $isAbsolute, $panel);
        if (!empty($parameters['id'])) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'id=' . $parameters['id'];
        }
        return $url;
    }

    public function mount(): void
    {
        $recordId = request()->query('id');
        $user = auth()->user();

        if (!$recordId) {
            Notification::make()->warning()->title('يجب الدخول من خلال زر التسجيل المكاني في جدول القرارات')->send();
            $this->redirect(WorkflowDashboard::getUrl());
            return;
        }

        $this->removalOrder = RemovalOrder::find($recordId);

        if (!$this->removalOrder) {
            Notification::make()->danger()->title('القرار غير موجود')->send();
            $this->redirect(WorkflowDashboard::getUrl());
            return;
        }

        // فحص الصلاحيات برسائل توضيحية
        if ($user->hasRole('عضو المتغيرات') && !$user->hasRole('super_admin')) {
            // 1. هل هو المكلف؟
            if ($this->removalOrder->spatial_member_id !== $user->id) {
                Notification::make()->danger()->title('عفواً، أنت لست العضو المكلف بهذا القرار')->send();
                $this->redirect(WorkflowDashboard::getUrl());
                return;
            }
            // 2. هل المرحلة صحيحة؟
            if ($this->removalOrder->stage !== RemovalOrder::STAGE_SPATIAL_DIMENSIONED) {
                Notification::make()->warning()->title('هذا القرار ليس في مرحلة التسجيل المكاني حالياً')->send();
                $this->redirect(WorkflowDashboard::getUrl());
                return;
            }
        }

        $spatialData = $this->removalOrder->spatial_data;
        if (is_array($spatialData)) {
            $this->polygon_geojson = $spatialData['polygon_geojson'] ?? null;
            $this->spatial_notes = $spatialData['spatial_notes'] ?? null;
        }
        $this->showMap = true;
    }

    public function save()
    {
        if (!auth()->user()->hasAnyRole(['عضو المتغيرات', 'super_admin'])) {
            Notification::make()->danger()->title('ليس لديك صلاحية الحفظ')->send();
            return;
        }

        // if (empty($this->polygon_geojson)) {
        //     Notification::make()->danger()->title('يرجي رسم مضلع علي الخريطة أولاً')->send();
        //     return;
        // }

        $data = json_decode($this->polygon_geojson, true);

        // استخراج الإحداثيات وحساب المركز (Latitude, Longitude)
        try {
            // if (isset($data['features'][0]['geometry']['coordinates'][0])) {
            //     $coords = $data['features'][0]['geometry']['coordinates'][0];
            // } else {
            //     $coords = $data['geometry']['coordinates'][0] ?? null;
            // }

            // if (!$coords || count($coords) < 3) {
            //     throw new \Exception('إحداثيات غير مكتملة');
            // }

            // $lats = array_map(fn($c) => $c[1], $coords);
            // $lngs = array_map(fn($c) => $c[0], $coords);
            // $centerLat = array_sum($lats) / count($lats);
            // $centerLng = array_sum($lngs) / count($lngs);

            $this->removalOrder->update([
                'stage' => RemovalOrder::STAGE_PDF_READY, // نقل المرحلة للتالي
                // 'latitude' => $centerLat,
                // 'longitude' => $centerLng,
                'spatial_data' => [
                    'polygon_geojson' => $this->polygon_geojson,
                    'spatial_notes' => $this->spatial_notes,
                    // 'latitude' => $centerLat,
                    // 'longitude' => $centerLng,
                ],
            ]);

            Notification::make()->success()->title('تم حفظ التسجيل المكاني بنجاح')->send();
            $this->redirect(WorkflowDashboard::getUrl());
        } catch (\Exception $e) {
            Notification::make()->danger()->title('خطأ في بيانات الخريطة: ' . $e->getMessage())->send();
        }
    }
}
