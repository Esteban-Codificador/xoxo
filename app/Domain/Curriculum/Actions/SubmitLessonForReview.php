<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sends the working copy for review: the lesson goes to REVIEW and its
 * author stops editing it until an editor publishes or returns it
 * (ADR-032). Learners keep reading the published version, if any.
 */
final readonly class SubmitLessonForReview
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Lesson $lesson, User $user, ?string $note): LessonReview
    {
        return DB::transaction(function () use ($lesson, $user, $note): LessonReview {
            $review = $lesson->reviews()->create([
                'submitted_by' => $user->id,
                'note' => $note === null || trim($note) === '' ? null : trim($note),
                'previous_status' => $lesson->status === ContentStatus::Published ? ContentStatus::Published : ContentStatus::Draft,
                'content_hash' => $lesson->workingCopyHash(),
                'submitted_at' => now(),
            ]);

            $this->audit->during(AuditAction::Submitted, fn () => $lesson->forceFill(['status' => ContentStatus::Review])->save());

            return $review;
        });
    }
}
