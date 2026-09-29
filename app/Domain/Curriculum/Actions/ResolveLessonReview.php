<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\ReviewResolution;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Ends an open review without publishing: an editor returns the lesson
 * with a comment, or its author withdraws it. Either way it goes back to
 * the status it had and can be edited again.
 */
final readonly class ResolveLessonReview
{
    public function __construct(private AuditLogger $audit) {}

    public function returnWithComment(Lesson $lesson, User $editor, string $comment): void
    {
        $this->resolve($lesson, $editor, ReviewResolution::Returned, trim($comment));
    }

    public function withdraw(Lesson $lesson, User $user): void
    {
        $this->resolve($lesson, $user, ReviewResolution::Withdrawn, null);
    }

    private function resolve(Lesson $lesson, User $user, ReviewResolution $resolution, ?string $comment): void
    {
        $review = $lesson->openReview()->first() ?? throw new LogicException('The lesson has no open review.');

        DB::transaction(function () use ($lesson, $user, $resolution, $comment, $review): void {
            $review->update([
                'resolution' => $resolution,
                'resolved_by' => $user->id,
                'comment' => $comment,
                'resolved_at' => now(),
            ]);

            $action = $resolution === ReviewResolution::Returned ? AuditAction::Returned : AuditAction::Updated;
            $this->audit->during($action, fn () => $lesson->forceFill(['status' => $review->previous_status])->save());
        });
    }
}
