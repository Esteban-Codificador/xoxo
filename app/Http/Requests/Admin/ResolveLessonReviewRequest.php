<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Shared by returning (with a comment) and withdrawing: both need a lesson
 * that is in review.
 */
abstract class ResolveLessonReviewRequest extends FormRequest
{
    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->lesson()->status !== ContentStatus::Review) {
                $validator->errors()->add('review', __('cms.review.not_in_review'));
            }
        }];
    }

    public function lesson(): Lesson
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson ? $lesson : abort(404);
    }
}
