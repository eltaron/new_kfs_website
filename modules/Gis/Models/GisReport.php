<?php

namespace Modules\Gis\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GisReport extends Model
{
    protected $fillable = [
        'report_title',
        'report_type',
        'filters_applied',
        'file_path',
        'user_id'
    ];

    protected $casts = [
        'filters_applied' => 'array', // مهم جداً لقراءة الفلاتر كمصفوفة
    ];

    /**
     * الموظف المسؤول عن التقرير
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
