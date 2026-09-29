<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Curriculum\Publishing\LessonTemplate;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\Difficulty;
use App\Models\Lesson;
use App\Models\Module;

/**
 * A new lesson, as a draft at the end of its module, with the body
 * template to write under. Its author (RecordsAuthors) is whoever creates
 * it, so an instructor can keep editing it and send it for review.
 */
final class CreateLesson
{
    /**
     * @param  array{title: string, slug: string, summary: string, why_it_matters: string, content_type: string, difficulty: string, estimated_minutes: int|string}  $data
     */
    public function handle(Module $module, array $data): Lesson
    {
        return $module->lessons()->create([
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            'summary' => trim($data['summary']),
            'why_it_matters' => trim($data['why_it_matters']),
            'learning_objectives' => [],
            'body' => LessonTemplate::body(),
            'content_type' => ContentType::from($data['content_type']),
            'difficulty' => Difficulty::from($data['difficulty']),
            'estimated_minutes' => (int) $data['estimated_minutes'],
            'position' => (int) $module->lessons()->reorder()->max('position') + 1,
            'status' => ContentStatus::Draft,
        ]);
    }
}
