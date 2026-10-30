<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $guarded = [];
    public function shiakha()
    {
        return $this->belongsTo(Shiakha::class, 'shiakha_code', 'shiakha_g_code');
    }
}
