<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Official extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function roles()
    {
        return $this->hasMany(OfficialRole::class);
    }
}
