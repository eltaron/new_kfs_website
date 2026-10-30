<?php

namespace App\Services\Lms;

use Modules\Estidama\Models\Badge;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\ExamSubmission;
use Modules\Estidama\Models\LessonProgress;
use App\Models\User;

/**
 * Awards rule-based badges (Badge::RULES). Safe to call repeatedly: a badge is
 * attached at most once per user.
 *
 * @return list<Badge> newly awarded badges
 */
class BadgeService
{
    public function evaluate(User $user): array
    {
        $badges = Badge::whereNotNull('rule_type')->where('rule_type', '!=', 'manual')->get();

        $owned = $user->badges()->pluck('badges.id')->all();
        $completed = Enrollment::where('user_id', $user->getKey())->where('status', 'completed');
        $completedCount = (clone $completed)->count();
        $completedPrograms = (clone $completed)->pluck('training_program_id')->all();
        $bestFinalScore = (int) ExamSubmission::query()
            ->join('training_programs', 'training_programs.exam_id', '=', 'exam_submissions.exam_id')
            ->where('exam_submissions.user_id', $user->getKey())
            ->where('exam_submissions.status', ExamSubmission::STATUS_SUBMITTED)
            ->where('exam_submissions.passed', true)
            ->max('exam_submissions.score');
        $lessonsCount = LessonProgress::where('user_id', $user->getKey())->whereNotNull('completed_at')->count();

        $awarded = [];
        foreach ($badges as $badge) {
            if (in_array($badge->id, $owned)) {
                continue;
            }

            $earned = match ($badge->rule_type) {
                'first_course' => $completedCount >= 1,
                'program_completed' => $badge->training_program_id && in_array($badge->training_program_id, $completedPrograms),
                'programs_count' => $completedCount >= max(1, (int) $badge->rule_value),
                'high_score' => $bestFinalScore > 0 && $bestFinalScore >= max(1, (int) ($badge->rule_value ?: 90)),
                'lessons_count' => $lessonsCount >= max(1, (int) $badge->rule_value),
                default => false,
            };

            if ($earned) {
                $user->badges()->attach($badge->id, ['awarded_at' => now()]);
                $awarded[] = $badge;
            }
        }

        // Badge configured directly on the program ("شارة الإتمام").
        $programBadges = \Modules\Estidama\Models\TrainingProgram::whereIn('id', $completedPrograms ?: [0])
            ->whereNotNull('completion_badge_id')
            ->pluck('completion_badge_id')
            ->unique();
        foreach ($programBadges as $badgeId) {
            if (! in_array($badgeId, $owned) && ! collect($awarded)->contains('id', $badgeId) && Badge::whereKey($badgeId)->exists()) {
                $user->badges()->attach($badgeId, ['awarded_at' => now()]);
                $awarded[] = Badge::find($badgeId);
            }
        }

        return $awarded;
    }
}
