<?php

namespace Modules\Investment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = [
        'street_name', 'lat', 'lng', 'type', 'height',
        'size', 'description', 'status', 'user_id',
    ];

    /**
     * Advertising-structure types used by the public guide, the add form and the admin panel.
     * `type` stays a free string column (older rows keep whatever they had); unknown values fall back to «أخرى» styling.
     * value => [label, colour (marker / dot), Font Awesome icon]
     */
    public const TYPES = [
        'لافتة'        => ['label' => 'لافتة',                 'color' => '#0E7C86', 'icon' => 'fa-sign-hanging'],
        'لافتة مضيئة'  => ['label' => 'لافتة مضيئة (لايت بوكس)', 'color' => '#B7791F', 'icon' => 'fa-lightbulb'],
        'بيلبورد'      => ['label' => 'بيلبورد',               'color' => '#8B5A2B', 'icon' => 'fa-rectangle-ad'],
        'يوني بول'     => ['label' => 'يوني بول',              'color' => '#4F6B22', 'icon' => 'fa-signs-post'],
        'ميجا كوم'     => ['label' => 'ميجا كوم',              'color' => '#6D4AA8', 'icon' => 'fa-table-columns'],
        'شاشة LED'     => ['label' => 'شاشة LED',              'color' => '#1F63C6', 'icon' => 'fa-display'],
        'إلكتروني'     => ['label' => 'إعلان إلكتروني',         'color' => '#C2410C', 'icon' => 'fa-bolt'],
        'جداري'        => ['label' => 'إعلان جداري',           'color' => '#B23A48', 'icon' => 'fa-building'],
        'كوبري'        => ['label' => 'لوحة كوبري',            'color' => '#3D5A80', 'icon' => 'fa-bridge'],
        'باص'          => ['label' => 'مظلة / أتوبيس',          'color' => '#A21C6B', 'icon' => 'fa-bus'],
        'تليفزيوني'    => ['label' => 'تليفزيوني',              'color' => '#4A5A63', 'icon' => 'fa-tv'],
        'أخرى'         => ['label' => 'أخرى',                  'color' => '#6B7280', 'icon' => 'fa-location-dot'],
    ];

    /** Administrative centres of Kafr El-Sheikh (approximate HQ coordinates) — used to label each point's area. */
    public const AREAS = [
        'كفر الشيخ'  => [31.1107, 30.9388],
        'دسوق'       => [31.1325, 30.6478],
        'فوه'        => [31.2039, 30.5503],
        'مطوبس'      => [31.2894, 30.5156],
        'بيلا'       => [31.1741, 31.2217],
        'الحامول'    => [31.3116, 31.1501],
        'الرياض'     => [31.2369, 30.9466],
        'سيدي سالم'  => [31.2713, 30.7860],
        'قلين'       => [31.0491, 30.8436],
        'بلطيم'      => [31.5530, 31.0899],
        'برج البرلس' => [31.5720, 30.9950],
        'سيدي غازي'  => [31.2190, 30.8520],
        'مسير'       => [31.0870, 30.9050],
    ];

    /** @return array<string,string> value => label (Filament selects / filters). */
    public static function typeOptions(): array
    {
        return array_map(fn (array $t) => $t['label'], self::TYPES);
    }

    /** Nearest administrative centre to a coordinate (equirectangular distance — fine at governorate scale). */
    public static function areaFor(float $lat, float $lng): string
    {
        $best = 'كفر الشيخ';
        $bestD = INF;
        $k = cos(deg2rad(31.2));
        foreach (self::AREAS as $name => [$aLat, $aLng]) {
            $d = ($lat - $aLat) ** 2 + (($lng - $aLng) * $k) ** 2;
            if ($d < $bestD) {
                $bestD = $d;
                $best = $name;
            }
        }

        return $best;
    }

    protected function user()
    {
        return $this->belongsTo(User::class);
    }
}
