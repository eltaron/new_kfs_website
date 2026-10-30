<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shiakha extends Model
{
    protected $fillable = [
        'id',
        'shiakha_num',
        'name',
        'shiakha_g_code',
        'municipality_name',
        'markaz_code',
        'global_id',
        'st_area',
        'st_length'
    ];

    // علاقة الشياخة بالمركز: الشياخة تنتمي لمركز واحد
    public function markaz(): BelongsTo
    {
        return $this->belongsTo(Markaz::class, 'markaz_code', 'g_code');
    }
}
