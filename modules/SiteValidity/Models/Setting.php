<?php

namespace Modules\SiteValidity\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'sv_settings';

    protected $fillable = ['key', 'value'];
}
