<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialRole extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function official()
    {
        return $this->belongsTo(Official::class);
    }
}
