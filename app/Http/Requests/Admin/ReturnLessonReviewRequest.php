<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * An editor sends the lesson back to its author, saying what to change.
 */
class ReturnLessonReviewRequest extends ResolveLessonReviewRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('returnFromReview', $this->lesson());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['comment' => ['required', 'string', 'max:2000']];
    }
}
