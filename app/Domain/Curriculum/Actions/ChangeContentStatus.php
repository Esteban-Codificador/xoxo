<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Curriculum\Publishing\StatusTransition;
use App\Enums\ContentStatus;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Track;
use InvalidArgumentException;

/**
 * Publishes, unpublishes, archives or restores a track or a module (not
 * versioned: the change is what learners see right away), and archives or
 * restores a lesson (StatusTransition::lessonTargets). Lessons are
 * published only through PublishLesson, which snapshots a version.
 */
final readonly class ChangeContentStatus
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Track|Module|Lesson $subject, ContentStatus $to): void
    {
        $from = $subject->status;

        $targets = $subject instanceof Lesson ? StatusTransition::lessonTargets($from) : StatusTransition::targets($from);

        if (! in_array($to, $targets, true)) {
            throw new InvalidArgumentException("Status transition {$from->value} → {$to->value} is not allowed.");
        }

        $this->audit->during(StatusTransition::auditAction($from, $to), fn () => $subject->forceFill([
            'status' => $to,
            'published_at' => $to === ContentStatus::Published ? now() : $subject->published_at,
        ])->save());
    }
}
