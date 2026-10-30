<?php

namespace Modules\Estidama\Models;

use App\Models\User;
use App\Services\Lms\EnrollmentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingApplication extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'has_taken_previous_courses' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'قيد الانتظار',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
    ];

    protected static function booted(): void
    {
        // Approving an application (from any screen) opens the learner's enrollment.
        static::updated(function (TrainingApplication $application) {
            if ($application->wasChanged('status') && $application->status === 'approved') {
                app(EnrollmentService::class)->enrollFromApplication($application);
            }
        });
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class, 'training_application_id');
    }

    /** The account the application belongs to (linked user, else same national id). */
    public function resolveUser(): ?User
    {
        if ($this->user_id && ($user = User::find($this->user_id))) {
            return $user;
        }

        return filled($this->national_id) ? User::where('national_id', $this->national_id)->first() : null;
    }
}
