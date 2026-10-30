<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;

class MarketSetting extends Model
{
    protected $table = 'market_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];
}
