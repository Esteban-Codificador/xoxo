<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\ActivityRecorder;
use App\Enums\ActivityType;
use App\Enums\ProgressStatus;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Marks a lesson IN_PROGRESS the first time the learner opens it and
 * records the visit afterwards. Never moves a lesson backwards.
 */
final class StartLesson
{
    public function __construct(private readonly ActivityRecorder $activities) {}

    public function handle(User $user, Lesson $lesson): LessonProgress
    {
        return DB::transaction(function () use ($user, $lesson) {
            $progress = LessonProgress::createOrFirst(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                ['status' => ProgressStatus::InProgress, 'started_at' => now(), 'last_viewed_at' => now()],
            );

            if ($progress->wasRecentlyCreated) {
                $this->activities->record($user, ActivityType::LessonStarted, $lesson);
            } else {
                $progress->update(['last_viewed_at' => now()]);
            }

            return $progress;
        });
    }
}
