<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentType;
use App\Enums\Difficulty;
use App\Models\Lesson;
use App\Models\User;

/**
 * Saves the editable working copy of a lesson. Learners keep reading the
 * published version until PublishLesson snapshots a new one (ADR-006).
 * The Auditable observer records what changed.
 */
final class UpdateLesson
{
    /**
     * @param  array{title: string, summary: string, why_it_matters: string, learning_objectives: list<string>, content_type: string, difficulty: string, estimated_minutes: int|string, body: array<string, mixed>}  $data
     */
    public function handle(Lesson $lesson, User $editor, array $data): Lesson
    {
        $lesson->forceFill([
            'title' => trim($data['title']),
            'summary' => trim($data['summary']),
            'why_it_matters' => trim($data['why_it_matters']),
            'learning_objectives' => array_map('trim', $data['learning_objectives']),
            'content_type' => ContentType::from($data['content_type']),
            'difficulty' => Difficulty::from($data['difficulty']),
            'estimated_minutes' => (int) $data['estimated_minutes'],
            'body' => RichContent::fromArray($data['body']),
            'updated_by' => $editor->id,
        ])->save();

        return $lesson;
    }
}
