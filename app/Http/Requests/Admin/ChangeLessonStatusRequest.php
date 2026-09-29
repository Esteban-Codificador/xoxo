<?php

namespace App\Http\Requests\Admin;

use App\Domain\Curriculum\Publishing\StatusTransition;
use App\Models\Lesson;

/**
 * Archive or restore a lesson. Publishing goes through PublishLessonRequest:
 * it snapshots a version.
 */
class ChangeLessonStatusRequest extends ChangeStatusRequest
{
    public function subject(): Lesson
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson ? $lesson : abort(404);
    }

    protected function targets(): array
    {
        return StatusTransition::lessonTargets($this->subject()->status);
    }
}
