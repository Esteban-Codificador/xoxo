<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\ActivityRecorder;
use App\Domain\Learning\LessonMastery;
use App\Enums\ActivityType;
use App\Enums\ProgressStatus;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * COMPLETED records which published version the learner finished, so a
 * later rewrite of the lesson can be told apart. Completing twice is a
 * no-op, and a MASTERED lesson stays MASTERED. With the quiz already
 * passed at the mastery threshold, it goes straight on to MASTERED.
 */
final class CompleteLesson
{
    public function __construct(
        private readonly ActivityRecorder $activities,
        private readonly LessonMastery $mastery,
    ) {}

    public function handle(User $user, Lesson $lesson): LessonProgress
    {
        return DB::transaction(function () use ($user, $lesson) {
            $progress = LessonProgress::createOrFirst(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                ['status' => ProgressStatus::InProgress, 'started_at' => now()],
            );
            $progress = LessonProgress::query()->lockForUpdate()->findOrFail($progress->id);

            if ($progress->status !== ProgressStatus::InProgress) {
                return $progress;
            }

            $progress->update([
                'status' => ProgressStatus::Completed,
                'completed_at' => now(),
                'completed_version_id' => $lesson->published_version_id,
                'last_viewed_at' => now(),
            ]);

            $this->activities->record($user, ActivityType::LessonCompleted, $lesson, [
                'version_id' => $lesson->published_version_id,
            ]);

            $this->mastery->promote($user, $lesson, $progress);

            return $progress;
        });
    }
}
