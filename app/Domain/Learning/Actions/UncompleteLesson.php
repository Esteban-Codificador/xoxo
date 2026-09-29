<?php

namespace App\Domain\Learning\Actions;

use App\Enums\ProgressStatus;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Undoes a completion marked by mistake: back to IN_PROGRESS. The feed
 * keeps the original LESSON_COMPLETED entry (it is append-only). MASTERED
 * rests on evidence (a passed quiz), so it cannot be undone from here.
 */
final class UncompleteLesson
{
    public function handle(User $user, Lesson $lesson): ?LessonProgress
    {
        return DB::transaction(function () use ($user, $lesson) {
            $progress = LessonProgress::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($lesson)
                ->lockForUpdate()
                ->first();

            if ($progress === null || $progress->status === ProgressStatus::InProgress) {
                return $progress;
            }

            if ($progress->status === ProgressStatus::Mastered) {
                throw ValidationException::withMessages(['lesson' => __('progress.mastered_is_final')]);
            }

            $progress->update([
                'status' => ProgressStatus::InProgress,
                'completed_at' => null,
                'completed_version_id' => null,
            ]);

            return $progress;
        });
    }
}
