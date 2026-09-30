<?php

namespace App\Domain\Learning;

use App\Enums\ActivityType;
use App\Enums\ProgressStatus;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

/**
 * Lesson MASTERED ⇔ COMPLETED ∧ an attempt of its published quiz passed
 * with Quiz::MASTERY_THRESHOLD or more (architecture §6, ADR-007). The two
 * halves can arrive in any order, so completing the lesson and passing the
 * quiz both ask here. A lesson without a quiz tops out at COMPLETED.
 */
final readonly class LessonMastery
{
    public function __construct(private ActivityRecorder $activities) {}

    public function hasEvidence(User $user, Lesson $lesson): bool
    {
        $quiz = Quiz::query()->published()->whereBelongsTo($lesson)->first();

        return $quiz !== null && QuizAttempt::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($quiz)
            ->where('passed', true)
            ->where('score', '>=', max(Quiz::MASTERY_THRESHOLD, $quiz->pass_threshold))
            ->exists();
    }

    /**
     * Promotes a COMPLETED lesson that has the evidence. The caller holds
     * the lock on the progress row.
     */
    public function promote(User $user, Lesson $lesson, LessonProgress $progress): void
    {
        if ($progress->status !== ProgressStatus::Completed || ! $this->hasEvidence($user, $lesson)) {
            return;
        }

        $progress->update(['status' => ProgressStatus::Mastered, 'mastered_at' => now()]);

        $this->activities->record($user, ActivityType::LessonMastered, $lesson);
    }
}
