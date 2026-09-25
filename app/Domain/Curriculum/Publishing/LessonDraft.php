<?php

namespace App\Domain\Curriculum\Publishing;

use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentStatus;
use App\Models\Lesson;

/**
 * The data the publishing contract needs, independent of where the lesson
 * comes from (database working copy or content package file).
 */
final readonly class LessonDraft
{
    /**
     * @param  list<string>  $objectives
     */
    public function __construct(
        public string $summary,
        public string $whyItMatters,
        public array $objectives,
        public RichContent $body,
        public int $skillCount,
        public int $practiceActivityCount,
        public bool $parentsPublished,
    ) {}

    public static function fromLesson(Lesson $lesson): self
    {
        $lesson->loadMissing('module.track');

        return new self(
            summary: $lesson->summary,
            whyItMatters: $lesson->why_it_matters,
            objectives: $lesson->learning_objectives,
            body: $lesson->body,
            skillCount: $lesson->skills()->count(),
            // Exercises and labs arrive in phase 6; until then practice lives in the body.
            practiceActivityCount: 0,
            parentsPublished: $lesson->module->status === ContentStatus::Published
                && $lesson->module->track->status === ContentStatus::Published,
        );
    }
}
