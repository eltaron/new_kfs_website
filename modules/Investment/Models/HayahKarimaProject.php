<?php

namespace Modules\Investment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HayahKarimaProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_name',
        'slug',
        'icon',
        'description',
        'progress',
        'image'
    ];

    // توليد السلج تلقائياً عند الحفظ
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->sector_name, '-', null);
            }
        });
    }
}
