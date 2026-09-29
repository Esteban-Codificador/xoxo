<?php

namespace App\Http\Requests\Admin;

use App\Domain\Curriculum\Publishing\ReviewEligibility;
use App\Models\Lesson;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Sends the saved working copy for review, with an optional note for the
 * reviewer.
 */
class SubmitLessonReviewRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('submitForReview', $this->lesson());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:1000']];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $blocker = app(ReviewEligibility::class)->blocker($this->lesson());

            if ($blocker !== null) {
                $validator->errors()->add('review', __("cms.review.blocked.{$blocker}"));
            }
        }];
    }

    public function lesson(): Lesson
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson ? $lesson : abort(404);
    }
}
