<?php

namespace App\Domain\Curriculum\Publishing;

use App\Enums\ContentStatus;
use App\Models\Lesson;

/**
 * Whether a lesson can be sent for review (ADR-032). It must have something
 * to publish and meet the publishing contract, except for its module and
 * track being published: that is not in the author's hands, and the editor
 * sees it in the checklist before publishing.
 */
final readonly class ReviewEligibility
{
    public function __construct(private LessonReadiness $readiness) {}

    /**
     * The reason it cannot be submitted (a code of cms.review.blocked), or
     * null when it can.
     */
    public function blocker(Lesson $lesson): ?string
    {
        return match (true) {
            $lesson->status === ContentStatus::Review => 'in_review',
            $lesson->status === ContentStatus::Archived => 'archived',
            $lesson->published_version_id !== null && ! $lesson->hasUnpublishedChanges() => 'nothing_to_review',
            $this->issues($lesson) !== [] => 'not_ready',
            default => null,
        };
    }

    /**
     * @return list<ReadinessIssue>
     */
    private function issues(Lesson $lesson): array
    {
        return array_values(array_filter(
            $this->readiness->check(LessonDraft::fromLesson($lesson)),
            fn (ReadinessIssue $issue) => $issue->code !== 'parents_not_published',
        ));
    }
}
