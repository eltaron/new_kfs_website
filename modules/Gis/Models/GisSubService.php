<?php

namespace Modules\Gis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GisSubService extends Model
{
    protected $fillable = [
        'gis_service_type_id',
        'name',
        'slug',
        'video_url',
        'terms_conditions',
        'requirements',
        'dynamic_fields',
        'base_price',
        'description',
        'pricing_settings',
        'pricing_type',
        'has_vat',
        'martyr_stamp_fee',
        'sms_fee',
    ];

    protected $casts = [
        'dynamic_fields' => 'array',
        'pricing_settings' => 'array',
        'has_vat' => 'boolean',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(GisServiceType::class, 'gis_service_type_id');
    }
}
