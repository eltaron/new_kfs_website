<?php

namespace Modules\Citizen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Service extends Model
{
    use HasFactory, HasUuids;

    // Explicit whitelist (was $guarded = []). id/timestamps are listed because
    // ServiceSeeder upserts fixed UUIDs; services are only written by admins.
    protected $fillable = [
        'id',
        'created_at',
        'updated_at',
        'parent_id',
        'title',
        'title_line_1',
        'title_line_2',
        'description',
        'form_fields',
        'base_price',
        'has_vat',
        'martyr_stamp_fee',
        'sms_fee',
        'pricing_type',
        'category_pricing',
        'insurance_fee',
        'insurance_percentage',
        'link',
        'icon',
        'is_highlighted',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_highlighted' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'form_fields' => 'array',
        'category_pricing' => 'array', // ضروري جداً للتعامل مع الفئات كـ JSON
        'has_vat' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /** الخدمات المفعّلة فقط (الظاهرة للمواطنين). */
    public function scopeActive($query)
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    /** ترتيب العرض المحدد من لوحة التحكم (الأصغر أولاً). */
    public function scopeOrdered($query)
    {
        return $query->orderBy($query->qualifyColumn('sort_order'))->orderBy($query->qualifyColumn('created_at'));
    }

    /**
     * رابط الخدمة: رابط داخلي يبدأ بـ «/» (مثل /gis-portal) يُفتح على نفس الموقع أياً كان
     * الدومين، والرابط الخارجي كما هو، وإلا صفحة تفاصيل الخدمة.
     */
    public function publicUrl(): string
    {
        $link = trim((string) $this->link);
        if ($link === '') {
            return route('services.show', $this);
        }

        return str_starts_with($link, '/') && ! str_starts_with($link, '//') ? url($link) : $link;
    }

    /** الروابط الخارجية فقط تُفتح في تبويب جديد. */
    public function opensExternally(): bool
    {
        $link = trim((string) $this->link);

        return $link !== '' && ! (str_starts_with($link, '/') && ! str_starts_with($link, '//'));
    }

    /** هل الخدمة متاحة للمواطنين؟ (مفعّلة وفئتها الرئيسية مفعّلة) */
    public function isAvailable(): bool
    {
        return $this->is_active && (! $this->parent_id || (bool) $this->parent?->is_active);
    }

    public function parent()
    {
        return $this->belongsTo(Service::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Service::class, 'parent_id');
    }

    /**
     * دالة حساب السعر النهائي ديناميكياً
     * $area: مساحة المتر
     * $categoryName: اسم الفئة المختارة (مثل: حي شرق)
     */
    public function calculateTotal($area = 1, $categoryName = null)
    {
        $base = $this->base_price;

        // 1. منطق التسعير (فئات أو ثابت)
        if ($this->pricing_type === 'category' && $this->category_pricing) {
            foreach ($this->category_pricing as $cat) {
                if ($cat['name'] === $categoryName) {
                    $base = $base * $cat['price_multiplier'];
                    break;
                }
            }
        }

        $total = ($base * $area);

        // 2. إضافة التأمين
        $total += $this->insurance_fee;

        // 3. إضافة الضريبة (14%)
        if ($this->has_vat) {
            $total += ($total * 0.14);
        }

        // 4. الرسوم الثابتة
        $total += $this->martyr_stamp_fee;
        $total += $this->sms_fee;

        return $total;
    }

    // للإبقاء على التوافق مع الكود القديم (سعر افتراضي)
    protected function totalPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateTotal(1)
        );
    }
}