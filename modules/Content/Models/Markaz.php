<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Markaz extends Model
{
    protected $fillable = ['id', 'name', 'g_code', 'gov_name', 'global_id', 'st_area', 'st_length'];

    // علاقة المركز بالشياخات: المركز الواحد يحتوي على عدة شياخات
    public function shiakhas(): HasMany
    {
        return $this->hasMany(Shiakha::class, 'markaz_code', 'g_code');
    }
}
