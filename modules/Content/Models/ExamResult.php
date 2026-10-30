<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $fillable = [
        'seat_number', 'student_name', 'school', 'academic_year',
        'total_grade', 'subjects', 'status', 'notes','adminstration',
    ];

    protected $casts = [
        'subjects' => 'array',
        'total_grade' => 'float',
    ];
}
