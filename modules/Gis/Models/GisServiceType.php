<?php

namespace Modules\Gis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GisServiceType extends Model
{
    protected $fillable = ['name', 'icon', 'description'];

    /**
     * فئة الخدمة (مثل الرفع المساحي) تحتوي على عدة خدمات فرعية (رخصة بناء، فصل مساحي..)
     */
    public function subServices(): HasMany
    {
        return $this->hasMany(GisSubService::class);
    }
}
