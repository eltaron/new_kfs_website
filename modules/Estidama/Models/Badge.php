<?php

namespace Modules\Estidama\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Badge extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rule_value' => 'integer',
    ];

    /** Automatic award rules evaluated by App\Services\Lms\BadgeService. */
    public const RULES = [
        'manual' => 'يدوي (يمنحه المشرف)',
        'first_course' => 'إتمام أول برنامج',
        'program_completed' => 'إتمام برنامج محدد',
        'programs_count' => 'إتمام عدد من البرامج',
        'high_score' => 'درجة امتحان نهائي لا تقل عن (%)',
        'lessons_count' => 'إتمام عدد من الدروس',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')->withTimestamps()->withPivot('awarded_at');
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function ruleLabel(): string
    {
        return self::RULES[$this->rule_type ?? 'manual'] ?? 'يدوي';
    }
}
